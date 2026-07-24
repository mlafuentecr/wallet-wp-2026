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
			'is_configured' => (bool) ( $client_id && $has_secret && $account_id && $location_id ),
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
		update_user_meta( $user_id, self::ACCOUNT_ID, isset( $_POST['google_account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['google_account_id'] ) ) : '' );
		update_user_meta( $user_id, self::LOCATION_ID, isset( $_POST['google_location_id'] ) ? sanitize_text_field( wp_unslash( $_POST['google_location_id'] ) ) : '' );
		update_user_meta( $user_id, self::SANDBOX_MODE, isset( $_POST['google_sandbox_mode'] ) ? '1' : '0' );
		$review_points = isset( $_POST['review_points'] ) ? min( 100000, max( 0, absint( $_POST['review_points'] ) ) ) : 0;
		Loyalty_Wallet_Customers_Module::sync_review_points( $user_id, $previous_review_points, $review_points );
		update_user_meta( $user_id, self::REVIEW_POINTS, $review_points );
		$secret = isset( $_POST['google_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ) ) : '';
		if ( '' !== $secret ) {
			update_user_meta( $user_id, self::CLIENT_SECRET, $secret );
		}
		return 'url_saved';
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
