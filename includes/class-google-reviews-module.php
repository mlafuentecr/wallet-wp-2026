<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Google_Reviews_Module {
	private const PLACE_ID = '_loyalty_wallet_place_id';
	private const MAPS_URL = '_loyalty_wallet_maps_url';
	private const REVIEW_URL = '_loyalty_wallet_google_review_url';
	private const CLIENT_ID = '_loyalty_wallet_google_client_id';
	private const CLIENT_SECRET = '_loyalty_wallet_google_client_secret';
	private const ACCOUNT_ID = '_loyalty_wallet_google_account_id';
	private const LOCATION_ID = '_loyalty_wallet_google_location_id';
	private const ACCOUNT_OPTIONS = '_loyalty_wallet_google_account_options';
	private const LOCATION_OPTIONS = '_loyalty_wallet_google_location_options';
	private const REFRESH_TOKEN = '_loyalty_wallet_google_refresh_token';
	private const ACCESS_TOKEN = '_loyalty_wallet_google_access_token';
	private const ACCESS_TOKEN_EXPIRES = '_loyalty_wallet_google_access_token_expires';
	private const CALLBACK_CONFIRMED = '_loyalty_wallet_google_callback_confirmed';
	private const SANDBOX_MODE = '_loyalty_wallet_google_sandbox_mode';
	private const REVIEW_POINTS = '_loyalty_wallet_google_review_points';
	private const DEFAULT_MAPS_URL = 'https://www.google.com/maps/place/Croc%E2%80%99s+Resort+%26+Casino/@9.6228739,-84.6434061,17z/data=!4m12!3m11!1s0x8fa1c71bb9ab4bc5:0xb27e5396a7ab8d54!5m3!1s2026-08-02!4m1!1i2!8m2!3d9.6228739!4d-84.6408258!9m1!1b1!16s%2Fg%2F1hm603k12';

	public static function data( int $user_id ): array {
		$place_id = (string) get_user_meta( $user_id, self::PLACE_ID, true );
		$saved_review_url = (string) get_user_meta( $user_id, self::REVIEW_URL, true );
		$review_url = $saved_review_url ?: ( $place_id ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode( $place_id ) : '' );
		$client_id = (string) get_user_meta( $user_id, self::CLIENT_ID, true );
		$has_secret = '' !== (string) get_user_meta( $user_id, self::CLIENT_SECRET, true );
		$account_id = (string) get_user_meta( $user_id, self::ACCOUNT_ID, true );
		$location_id = (string) get_user_meta( $user_id, self::LOCATION_ID, true );
		$account_options = get_user_meta( $user_id, self::ACCOUNT_OPTIONS, true );
		$location_options = get_user_meta( $user_id, self::LOCATION_OPTIONS, true );
		$is_connected = '' !== (string) get_user_meta( $user_id, self::REFRESH_TOKEN, true )
			|| (
				'' !== (string) get_user_meta( $user_id, self::ACCESS_TOKEN, true )
				&& absint( get_user_meta( $user_id, self::ACCESS_TOKEN_EXPIRES, true ) ) > time()
			);
		$sandbox_mode = '1' === (string) get_user_meta( $user_id, self::SANDBOX_MODE, true );
		$review_points = self::review_points( $user_id );
		return array(
			'place_id' => $place_id,
			'maps_url' => (string) get_user_meta( $user_id, self::MAPS_URL, true ) ?: self::DEFAULT_MAPS_URL,
			'review_url' => $review_url,
			'review_url_input' => $saved_review_url,
			'qr_image_url' => $review_url ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' . rawurlencode( $review_url ) : '',
			'client_id' => $client_id,
			'has_secret' => $has_secret,
			'account_id' => $account_id,
			'location_id' => $location_id,
			'account_options' => is_array( $account_options ) ? $account_options : array(),
			'location_options' => is_array( $location_options ) ? $location_options : array(),
			'is_connected' => $is_connected,
			'callback_confirmed' => '1' === (string) get_user_meta( $user_id, self::CALLBACK_CONFIRMED, true ),
			'is_configured' => (bool) ( $client_id && $has_secret && $is_connected && $account_id && $location_id ),
			'callback_url' => self::callback_url(),
			'sandbox_mode' => $sandbox_mode,
			'review_points' => $review_points,
		);
	}

	public static function review_points( int $user_id ): int {
		$value = get_user_meta( $user_id, self::REVIEW_POINTS, true );
		return '' === $value ? 100 : min( 100000, max( 0, absint( $value ) ) );
	}

	public static function save( int $user_id ): string {
		$place_id = isset( $_POST['place_id'] ) ? sanitize_text_field( wp_unslash( $_POST['place_id'] ) ) : '';
		$maps_url = isset( $_POST['maps_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['maps_url'] ) ) ) : '';
		$review_url = isset( $_POST['google_review_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['google_review_url'] ) ) ) : '';
		$previous_review_points = self::review_points( $user_id );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{10,512}$/', $place_id ) ) {
			return 'invalid_place_id';
		}
		if ( $maps_url && ( ! wp_http_validate_url( $maps_url ) || false === strpos( wp_parse_url( $maps_url, PHP_URL_HOST ) ?: '', 'google.' ) ) ) {
			return 'invalid_url';
		}
		if ( $review_url && ! self::is_google_review_url( $review_url ) ) {
			return 'invalid_google_review_url';
		}
		update_user_meta( $user_id, self::PLACE_ID, $place_id );
		update_user_meta( $user_id, self::MAPS_URL, $maps_url );
		update_user_meta( $user_id, self::REVIEW_URL, $review_url );
		update_user_meta( $user_id, self::CLIENT_ID, isset( $_POST['google_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['google_client_id'] ) ) : '' );
		if ( isset( $_POST['google_account_id'] ) ) {
			$account_id = sanitize_text_field( wp_unslash( $_POST['google_account_id'] ) );
			if ( $account_id && ! preg_match( '#^accounts/[A-Za-z0-9_-]+$#', $account_id ) ) {
				return 'invalid_google_account';
			}
			update_user_meta( $user_id, self::ACCOUNT_ID, $account_id );
		}
		if ( isset( $_POST['google_location_id'] ) ) {
			$location_id = sanitize_text_field( wp_unslash( $_POST['google_location_id'] ) );
			if ( $location_id && ! preg_match( '#^locations/[A-Za-z0-9_-]+$#', $location_id ) ) {
				return 'invalid_google_location';
			}
			update_user_meta( $user_id, self::LOCATION_ID, $location_id );
		}
		update_user_meta( $user_id, self::SANDBOX_MODE, isset( $_POST['google_sandbox_mode'] ) ? '1' : '0' );
		$review_points = isset( $_POST['review_points'] ) ? min( 100000, max( 0, absint( $_POST['review_points'] ) ) ) : 0;
		Loyalty_Wallet_Customers_Module::sync_review_points( $user_id, $previous_review_points, $review_points );
		update_user_meta( $user_id, self::REVIEW_POINTS, $review_points );
		$secret = isset( $_POST['google_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ) ) : '';
		if ( '' !== $secret ) {
			update_user_meta( $user_id, self::CLIENT_SECRET, $secret );
		}
		if ( isset( $_POST['google_callback_confirmed'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['google_callback_confirmed'] ) ) ) {
			update_user_meta( $user_id, self::CALLBACK_CONFIRMED, '1' );
		}
		return 'url_saved';
	}

	public static function callback_url(): string {
		return admin_url( 'admin-post.php?action=loyalty_wallet_google_callback' );
	}

	public static function start_oauth(): void {
		if ( ! current_user_can( 'access_loyalty_wallet' ) ) {
			wp_die( esc_html__( 'No tienes permiso para conectar Google.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_google_connect' );
		self::begin_oauth();
	}

	public static function begin_oauth(): void {
		$user_id = get_current_user_id();
		$client_id = (string) get_user_meta( $user_id, self::CLIENT_ID, true );
		$client_secret = (string) get_user_meta( $user_id, self::CLIENT_SECRET, true );
		if ( ! $client_id || ! $client_secret ) {
			self::redirect( 'google_oauth_credentials_missing' );
		}

		$state = wp_generate_password( 48, false, false );
		set_transient( 'loyalty_wallet_google_oauth_' . $user_id, hash( 'sha256', $state ), 15 * MINUTE_IN_SECONDS );
		$url = add_query_arg(
			array(
				'client_id' => $client_id,
				'redirect_uri' => self::callback_url(),
				'response_type' => 'code',
				'scope' => 'https://www.googleapis.com/auth/business.manage',
				'access_type' => 'offline',
				'prompt' => 'consent',
				'include_granted_scopes' => 'true',
				'state' => $state,
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	public static function oauth_callback(): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'access_loyalty_wallet' ) ) {
			wp_die( esc_html__( 'Inicia sesión en WordPress para terminar la conexión con Google.', 'loyalty-wallet' ) );
		}
		$user_id = get_current_user_id();
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$saved_state = (string) get_transient( 'loyalty_wallet_google_oauth_' . $user_id );
		delete_transient( 'loyalty_wallet_google_oauth_' . $user_id );
		if ( ! $state || ! $saved_state || ! hash_equals( $saved_state, hash( 'sha256', $state ) ) ) {
			self::redirect( 'google_oauth_invalid_state' );
		}
		if ( isset( $_GET['error'] ) ) {
			self::redirect( 'google_oauth_cancelled' );
		}
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		if ( ! $code ) {
			self::redirect( 'google_oauth_failed' );
		}

		$client_id = (string) get_user_meta( $user_id, self::CLIENT_ID, true );
		$client_secret = (string) get_user_meta( $user_id, self::CLIENT_SECRET, true );
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body' => array(
					'code' => $code,
					'client_id' => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri' => self::callback_url(),
					'grant_type' => 'authorization_code',
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			self::redirect( 'google_oauth_token_failed' );
		}
		$tokens = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $tokens ) || empty( $tokens['access_token'] ) ) {
			self::redirect( 'google_oauth_token_failed' );
		}
		update_user_meta( $user_id, self::ACCESS_TOKEN, sanitize_text_field( $tokens['access_token'] ) );
		update_user_meta( $user_id, self::ACCESS_TOKEN_EXPIRES, time() + max( 60, absint( $tokens['expires_in'] ?? 3600 ) - 60 ) );
		if ( ! empty( $tokens['refresh_token'] ) ) {
			update_user_meta( $user_id, self::REFRESH_TOKEN, sanitize_text_field( $tokens['refresh_token'] ) );
		}

		$result = self::refresh_business_resources( $user_id, sanitize_text_field( $tokens['access_token'] ) );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'google_oauth_connected' );
	}

	public static function refresh_resources(): void {
		if ( ! current_user_can( 'access_loyalty_wallet' ) ) {
			wp_die( esc_html__( 'No tienes permiso para actualizar la conexión con Google.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_google_refresh' );
		$user_id = get_current_user_id();
		$token = self::access_token( $user_id );
		if ( is_wp_error( $token ) ) {
			self::redirect( $token->get_error_code() );
		}
		$result = self::refresh_business_resources( $user_id, $token );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'google_resources_refreshed' );
	}

	public static function ajax_confirm_callback(): void {
		if ( ! current_user_can( 'access_loyalty_wallet' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permiso.' ), 403 );
		}
		check_ajax_referer( 'loyalty_wallet_google_setup', 'nonce' );
		update_user_meta( get_current_user_id(), self::CALLBACK_CONFIRMED, '1' );
		wp_send_json_success();
	}

	private static function access_token( int $user_id ) {
		$token = (string) get_user_meta( $user_id, self::ACCESS_TOKEN, true );
		$expires = absint( get_user_meta( $user_id, self::ACCESS_TOKEN_EXPIRES, true ) );
		if ( $token && $expires > time() ) {
			return $token;
		}
		$refresh_token = (string) get_user_meta( $user_id, self::REFRESH_TOKEN, true );
		if ( ! $refresh_token ) {
			return new WP_Error( 'google_oauth_reconnect_required' );
		}
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body' => array(
					'client_id' => (string) get_user_meta( $user_id, self::CLIENT_ID, true ),
					'client_secret' => (string) get_user_meta( $user_id, self::CLIENT_SECRET, true ),
					'refresh_token' => $refresh_token,
					'grant_type' => 'refresh_token',
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'google_oauth_reconnect_required' );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['access_token'] ) ) {
			return new WP_Error( 'google_oauth_reconnect_required' );
		}
		$token = sanitize_text_field( $data['access_token'] );
		update_user_meta( $user_id, self::ACCESS_TOKEN, $token );
		update_user_meta( $user_id, self::ACCESS_TOKEN_EXPIRES, time() + max( 60, absint( $data['expires_in'] ?? 3600 ) - 60 ) );
		return $token;
	}

	private static function refresh_business_resources( int $user_id, string $token ) {
		$accounts_response = self::google_get( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', $token );
		if ( is_wp_error( $accounts_response ) ) {
			return $accounts_response;
		}
		$accounts = array();
		foreach ( (array) ( $accounts_response['accounts'] ?? array() ) as $account ) {
			$name = sanitize_text_field( $account['name'] ?? '' );
			if ( preg_match( '#^accounts/[A-Za-z0-9_-]+$#', $name ) ) {
				$accounts[ $name ] = sanitize_text_field( $account['accountName'] ?? $account['organizationInfo']['registeredDomain'] ?? $name );
			}
		}
		if ( ! $accounts ) {
			return new WP_Error( 'google_no_business_accounts' );
		}
		update_user_meta( $user_id, self::ACCOUNT_OPTIONS, $accounts );
		$saved_account = (string) get_user_meta( $user_id, self::ACCOUNT_ID, true );
		$account_name = isset( $accounts[ $saved_account ] ) ? $saved_account : (string) array_key_first( $accounts );
		update_user_meta( $user_id, self::ACCOUNT_ID, $account_name );

		$locations_response = self::google_get(
			'https://mybusinessbusinessinformation.googleapis.com/v1/' . $account_name . '/locations?readMask=name,title,storeCode',
			$token
		);
		if ( is_wp_error( $locations_response ) ) {
			return $locations_response;
		}
		$locations = array();
		foreach ( (array) ( $locations_response['locations'] ?? array() ) as $location ) {
			$name = sanitize_text_field( $location['name'] ?? '' );
			if ( preg_match( '#^locations/[A-Za-z0-9_-]+$#', $name ) ) {
				$title = sanitize_text_field( $location['title'] ?? $name );
				$store_code = sanitize_text_field( $location['storeCode'] ?? '' );
				$locations[ $name ] = $store_code ? $title . ' — código de tienda ' . $store_code : $title;
			}
		}
		if ( ! $locations ) {
			return new WP_Error( 'google_no_business_locations' );
		}
		update_user_meta( $user_id, self::LOCATION_OPTIONS, $locations );
		$saved_location = (string) get_user_meta( $user_id, self::LOCATION_ID, true );
		update_user_meta( $user_id, self::LOCATION_ID, isset( $locations[ $saved_location ] ) ? $saved_location : (string) array_key_first( $locations ) );
		return true;
	}

	private static function google_get( string $url, string $token ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'google_business_api_failed' );
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $status || ! is_array( $data ) ) {
			return new WP_Error( 403 === $status ? 'google_business_api_permission' : 'google_business_api_failed' );
		}
		return $data;
	}

	private static function redirect( string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'loyalty-wallet',
					'lw_tab' => 'reviews',
					'lw_notice' => sanitize_key( $notice ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function is_google_review_url( string $url ): bool {
		if ( ! wp_http_validate_url( $url ) || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return false;
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return 'g.page' === $host || (bool) preg_match( '/(^|\.)google\.[a-z.]{2,}$/', $host );
	}

	public static function render_settings( array $google ): void {
		require LOYALTY_WALLET_DIR . 'views/google-reviews-settings.php';
	}

	public static function render_preview( array $google ): void {
		require LOYALTY_WALLET_DIR . 'views/google-reviews-preview.php';
	}
}
