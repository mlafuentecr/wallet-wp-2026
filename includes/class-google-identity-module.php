<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Google_Identity_Module {
	private const TOKEN_LIFETIME = 15 * MINUTE_IN_SECONDS;

	public static function handle_public_request( int $user_id, array $google, array $wallet ): void {
		$action = isset( $_POST['lw_identity_action'] ) ? sanitize_key( wp_unslash( $_POST['lw_identity_action'] ) ) : '';
		if ( ! $action ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

		if ( 'verify' === $action ) {
			$credential = isset( $_POST['credential'] ) ? trim( (string) wp_unslash( $_POST['credential'] ) ) : '';
			$result     = self::verify_google_credential( $user_id, $credential, $google );
		} elseif ( 'complete' === $action ) {
			$enrollment_token = isset( $_POST['enrollment_token'] ) ? trim( (string) wp_unslash( $_POST['enrollment_token'] ) ) : '';
			$phone            = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			$result           = self::complete_enrollment( $user_id, $enrollment_token, $phone, $google, $wallet );
		} else {
			$result = new WP_Error( 'invalid_identity_action', 'Invalid identity request.' );
		}

		if ( is_wp_error( $result ) ) {
			status_header( 400 );
			echo wp_json_encode( array( 'success' => false, 'message' => $result->get_error_message() ) );
			exit;
		}

		status_header( 200 );
		echo wp_json_encode( array( 'success' => true, 'data' => $result ) );
		exit;
	}

	private static function verify_google_credential( int $user_id, string $credential, array $google ) {
		$client_id = trim( (string) ( $google['client_id'] ?? '' ) );
		if ( ! $client_id || ! $credential || strlen( $credential ) > 10000 ) {
			return new WP_Error( 'google_signin_not_configured', 'Google Sign-In is not configured for this business.' );
		}

		$response = wp_remote_get(
			add_query_arg( 'id_token', $credential, 'https://oauth2.googleapis.com/tokeninfo' ),
			array( 'timeout' => 15 )
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'invalid_google_identity', 'Google could not verify this sign-in. Please try again.' );
		}

		$claims = json_decode( wp_remote_retrieve_body( $response ), true );
		$issuer = (string) ( $claims['iss'] ?? '' );
		if (
			! is_array( $claims )
			|| ! hash_equals( $client_id, (string) ( $claims['aud'] ?? '' ) )
			|| ! in_array( $issuer, array( 'accounts.google.com', 'https://accounts.google.com' ), true )
			|| time() >= absint( $claims['exp'] ?? 0 )
			|| ! in_array( $claims['email_verified'] ?? false, array( true, 'true', '1', 1 ), true )
		) {
			return new WP_Error( 'invalid_google_identity', 'Google returned an invalid identity token.' );
		}

		$sub     = sanitize_text_field( (string) ( $claims['sub'] ?? '' ) );
		$name    = sanitize_text_field( (string) ( $claims['name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $claims['email'] ?? '' ) );
		$picture = esc_url_raw( (string) ( $claims['picture'] ?? '' ) );
		if ( ! $sub || ! $name || ! is_email( $email ) ) {
			return new WP_Error( 'incomplete_google_identity', 'Google did not provide the required name and email.' );
		}

		$member_id = Loyalty_Wallet_Google_Wallet_Module::member_id_for_google_user( $user_id, $sub );
		$payload   = array(
			'user_id'   => $user_id,
			'sub'       => $sub,
			'name'      => $name,
			'email'     => $email,
			'picture'   => $picture,
			'member_id' => $member_id,
			'exp'       => time() + self::TOKEN_LIFETIME,
		);

		return array(
			'name'             => $name,
			'email'            => $email,
			'picture'          => $picture,
			'enrollment_token' => self::sign_enrollment_payload( $payload ),
		);
	}

	private static function complete_enrollment( int $user_id, string $enrollment_token, string $phone, array $google, array $wallet ) {
		$identity = self::verify_enrollment_token( $enrollment_token, $user_id );
		$digits   = preg_replace( '/\D+/', '', $phone );
		if ( is_wp_error( $identity ) ) {
			return $identity;
		}
		if ( strlen( $digits ) < 8 || strlen( $digits ) > 15 ) {
			return new WP_Error( 'invalid_phone', 'Enter a valid phone number including the country code.' );
		}
		if ( empty( $wallet['is_configured'] ) ) {
			return new WP_Error( 'wallet_not_configured', 'Google Wallet is not configured for this business.' );
		}

		$member_id = (string) $identity['member_id'];
		$object_id = Loyalty_Wallet_Google_Wallet_Module::member_object_id( $user_id, $member_id );
		$customer  = Loyalty_Wallet_Customers_Module::upsert_google_member(
			$user_id,
			array(
				'google_sub'       => (string) $identity['sub'],
				'name'             => (string) $identity['name'],
				'email'            => (string) $identity['email'],
				'picture'          => (string) $identity['picture'],
				'phone'            => '+' . $digits,
				'wallet_member_id' => $member_id,
				'wallet_object_id' => $object_id,
			)
		);
		$wallet_url = Loyalty_Wallet_Google_Wallet_Module::create_member_save_url(
			$user_id,
			(string) $identity['name'],
			$member_id,
			$google,
			$wallet
		);
		if ( ! $wallet_url ) {
			return new WP_Error( 'wallet_link_failed', 'The Google Wallet card could not be created.' );
		}

		setcookie(
			'lw_wallet_member_' . $user_id,
			$member_id,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'secure'   => true,
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		return array(
			'name'       => (string) $identity['name'],
			'email'      => (string) $identity['email'],
			'customerId' => (string) ( $customer['id'] ?? '' ),
			'walletUrl'  => $wallet_url,
		);
	}

	private static function sign_enrollment_payload( array $payload ): string {
		$encoded   = self::base64_url_encode( wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
		$signature = hash_hmac( 'sha256', $encoded, wp_salt( 'auth' ) );
		return $encoded . '.' . $signature;
	}

	private static function verify_enrollment_token( string $token, int $user_id ) {
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || ! hash_equals( hash_hmac( 'sha256', $parts[0], wp_salt( 'auth' ) ), $parts[1] ) ) {
			return new WP_Error( 'invalid_enrollment', 'Your Google sign-in expired. Please sign in again.' );
		}

		$json    = self::base64_url_decode( $parts[0] );
		$payload = json_decode( $json, true );
		if (
			! is_array( $payload )
			|| $user_id !== absint( $payload['user_id'] ?? 0 )
			|| time() >= absint( $payload['exp'] ?? 0 )
			|| empty( $payload['sub'] )
			|| empty( $payload['name'] )
			|| ! is_email( $payload['email'] ?? '' )
			|| ! preg_match( '/^LW[A-Z0-9]{12}$/', (string) ( $payload['member_id'] ?? '' ) )
		) {
			return new WP_Error( 'invalid_enrollment', 'Your Google sign-in expired. Please sign in again.' );
		}
		return $payload;
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
