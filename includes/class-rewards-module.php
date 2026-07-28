<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Rewards_Module {
	private const META_KEY = '_loyalty_wallet_rewards';

	public static function all( int $user_id ): array {
		$rewards = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $rewards ) ) {
			$rewards = array(
				array(
					'id'     => wp_generate_uuid4(),
					'name'   => 'Café gratis',
					'points' => 100,
					'active' => true,
				),
			);
			update_user_meta( $user_id, self::META_KEY, $rewards );
		}
		return array_values(
			array_filter(
				$rewards,
				static fn( $reward ) => ! empty( $reward['id'] ) && ! empty( $reward['name'] ) && ! empty( $reward['points'] )
			)
		);
	}

	public static function save( int $user_id ): string {
		$id     = isset( $_POST['reward_id'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_id'] ) ) : '';
		$name   = isset( $_POST['reward_name'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_name'] ) ) : '';
		$points = isset( $_POST['reward_points'] ) ? absint( $_POST['reward_points'] ) : 0;
		if ( ! $name || $points < 1 || $points > 100000 ) {
			return 'invalid_reward';
		}

		$rewards = self::all( $user_id );
		$found   = false;
		foreach ( $rewards as &$reward ) {
			if ( $id && isset( $reward['id'] ) && hash_equals( (string) $reward['id'], $id ) ) {
				$reward['name']   = $name;
				$reward['points'] = $points;
				$reward['active'] = true;
				$found = true;
				break;
			}
		}
		unset( $reward );
		if ( ! $found ) {
			$rewards[] = array( 'id' => wp_generate_uuid4(), 'name' => $name, 'points' => $points, 'active' => true );
		}
		update_user_meta( $user_id, self::META_KEY, $rewards );
		return 'reward_saved';
	}

	public static function delete( int $user_id ): string {
		$id = isset( $_POST['reward_id'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_id'] ) ) : '';
		if ( ! $id ) {
			return 'invalid_reward';
		}
		$rewards = self::all( $user_id );
		$filtered = array_values(
			array_filter(
				$rewards,
				static fn( $reward ) => empty( $reward['id'] ) || ! hash_equals( (string) $reward['id'], $id )
			)
		);
		if ( count( $filtered ) === count( $rewards ) ) {
			return 'invalid_reward';
		}
		update_user_meta( $user_id, self::META_KEY, $filtered );
		return 'reward_deleted';
	}

	public static function barcode_payload( int $user_id, array $customer ): string {
		$payload = array(
			'v' => 1,
			'b' => $user_id,
			'c' => sanitize_text_field( (string) ( $customer['id'] ?? '' ) ),
			'm' => sanitize_text_field( (string) ( $customer['wallet_member_id'] ?? '' ) ),
			'n' => sanitize_text_field( (string) ( $customer['name'] ?? 'Miembro de lealtad' ) ),
			'p' => absint( $customer['points'] ?? 0 ),
		);
		$encoded = self::base64_url_encode( wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		return 'LW1.' . $encoded . '.' . substr( hash_hmac( 'sha256', $encoded, self::signing_key( $user_id ) ), 0, 32 );
	}

	public static function customer_from_code( int $user_id, string $code ) {
		$code = trim( $code );
		if ( preg_match( '/^LW[A-Z0-9]{12}$/', $code ) ) {
			$legacy = Loyalty_Wallet_Customers_Module::find( $user_id, '', $code );
			return $legacy ?: new WP_Error( 'customer_not_found', 'No se encontró el cliente escaneado.' );
		}

		$parts = explode( '.', $code );
		if ( 3 !== count( $parts ) || 'LW1' !== $parts[0] ) {
			return new WP_Error( 'invalid_customer_qr', 'Este no es un código QR válido de un cliente de Loyalty Wallet.' );
		}
		$expected = substr( hash_hmac( 'sha256', $parts[1], self::signing_key( $user_id ) ), 0, 32 );
		if ( ! hash_equals( $expected, $parts[2] ) ) {
			return new WP_Error( 'invalid_customer_qr', 'La firma del código QR del cliente no es válida.' );
		}
		$payload = json_decode( self::base64_url_decode( $parts[1] ), true );
		if ( ! is_array( $payload ) || 1 !== absint( $payload['v'] ?? 0 ) || $user_id !== absint( $payload['b'] ?? 0 ) ) {
			return new WP_Error( 'invalid_customer_qr', 'Este código QR pertenece a otro negocio o no es válido.' );
		}
		$customer = Loyalty_Wallet_Customers_Module::find(
			$user_id,
			sanitize_text_field( (string) ( $payload['c'] ?? '' ) ),
			sanitize_text_field( (string) ( $payload['m'] ?? '' ) )
		);
		return $customer ?: new WP_Error( 'customer_not_found', 'El cliente escaneado ya no existe.' );
	}

	public static function ajax_lookup(): void {
		self::verify_ajax_request();
		$user_id  = get_current_user_id();
		$code     = isset( $_POST['code'] ) ? (string) wp_unslash( $_POST['code'] ) : '';
		$customer = self::customer_from_code( $user_id, $code );
		if ( is_wp_error( $customer ) ) {
			wp_send_json_error( array( 'message' => $customer->get_error_message() ), 400 );
		}
		wp_send_json_success(
			array(
				'customer' => self::public_customer( $customer ),
				'rewards'  => self::available_rewards( $user_id ),
			)
		);
	}

	public static function ajax_redeem(): void {
		self::verify_ajax_request();
		$user_id   = get_current_user_id();
		$code      = isset( $_POST['code'] ) ? (string) wp_unslash( $_POST['code'] ) : '';
		$reward_id = isset( $_POST['reward_id'] ) ? sanitize_text_field( wp_unslash( $_POST['reward_id'] ) ) : '';
		$request_id = isset( $_POST['request_id'] ) ? sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : '';
		$customer  = self::customer_from_code( $user_id, $code );
		if ( is_wp_error( $customer ) ) {
			wp_send_json_error( array( 'message' => $customer->get_error_message() ), 400 );
		}
		$reward = null;
		foreach ( self::available_rewards( $user_id ) as $candidate ) {
			if ( hash_equals( (string) $candidate['id'], $reward_id ) ) {
				$reward = $candidate;
				break;
			}
		}
		if ( ! $reward ) {
			wp_send_json_error( array( 'message' => 'La recompensa seleccionada ya no está disponible.' ), 400 );
		}
		$result = Loyalty_Wallet_Customers_Module::redeem( $user_id, (string) $customer['id'], $reward, $request_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success(
			array(
				'customer'      => self::public_customer( $result['customer'] ),
				'reward'        => $reward,
				'walletSynced'  => (bool) $result['wallet_synced'],
				'message'       => sprintf( '%s canjeado por %d puntos.', $reward['name'], $reward['points'] ),
			)
		);
	}

	public static function render( int $user_id ): void {
		$rewards = self::all( $user_id );
		require LOYALTY_WALLET_DIR . 'views/rewards-panel.php';
	}

	private static function available_rewards( int $user_id ): array {
		return array_values( array_filter( self::all( $user_id ), static fn( $reward ) => ! isset( $reward['active'] ) || $reward['active'] ) );
	}

	private static function public_customer( array $customer ): array {
		return array(
			'id'     => sanitize_text_field( (string) ( $customer['id'] ?? '' ) ),
			'name'   => sanitize_text_field( (string) ( $customer['name'] ?? '' ) ),
			'email'  => sanitize_email( (string) ( $customer['email'] ?? '' ) ),
			'points' => absint( $customer['points'] ?? 0 ),
		);
	}

	private static function verify_ajax_request(): void {
		if ( ! current_user_can( 'access_loyalty_wallet' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permiso para canjear puntos.' ), 403 );
		}
		check_ajax_referer( 'loyalty_wallet_redemption', 'nonce' );
	}

	private static function signing_key( int $user_id ): string {
		return hash_hmac( 'sha256', 'loyalty-wallet-redemption|' . $user_id, wp_salt( 'auth' ) );
	}

	private static function base64_url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private static function base64_url_decode( string $value ): string {
		$padding = strlen( $value ) % 4;
		if ( $padding ) {
			$value .= str_repeat( '=', 4 - $padding );
		}
		return (string) base64_decode( strtr( $value, '-_', '+/' ), true );
	}
}
