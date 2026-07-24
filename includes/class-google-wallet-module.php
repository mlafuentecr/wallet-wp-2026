<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Google_Wallet_Module {
	private const ISSUER_ID     = '_loyalty_wallet_google_wallet_issuer_id';
	private const CLASS_SUFFIX  = '_loyalty_wallet_google_wallet_class_suffix';
	private const SERVICE_EMAIL = '_loyalty_wallet_google_wallet_service_email';
	private const PRIVATE_KEY   = '_loyalty_wallet_google_wallet_private_key';
	private const PUBLIC_URL    = '_loyalty_wallet_google_wallet_public_url';
	private const LOGO_URL      = '_loyalty_wallet_google_wallet_logo_url';
	private const WALLET_LOGO_ID = '_loyalty_wallet_google_wallet_logo_id';
	private const PUBLIC_TOKEN  = '_loyalty_wallet_google_wallet_public_token';
	private const NAME_META     = '_loyalty_wallet_name';
	private const LOGO_META     = '_loyalty_wallet_logo_id';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_landing' ), 0 );
	}

	public static function data( int $user_id ): array {
		$issuer_id     = self::credential( 'LOYALTY_WALLET_GOOGLE_ISSUER_ID', self::ISSUER_ID, $user_id );
		$service_email = self::credential( 'LOYALTY_WALLET_GOOGLE_SERVICE_ACCOUNT_EMAIL', self::SERVICE_EMAIL, $user_id );
		$private_key   = self::credential( 'LOYALTY_WALLET_GOOGLE_PRIVATE_KEY', self::PRIVATE_KEY, $user_id );
		$class_suffix  = (string) get_user_meta( $user_id, self::CLASS_SUFFIX, true );
		$class_suffix  = $class_suffix ?: 'loyalty_wallet_' . $user_id;
		$public_url    = (string) get_user_meta( $user_id, self::PUBLIC_URL, true );
		$public_url    = $public_url ?: home_url( '/' );
		$logo_url_input = (string) get_user_meta( $user_id, self::LOGO_URL, true );
		$logo_url       = $logo_url_input;
		if ( ! $logo_url ) {
			$logo_id  = absint( get_user_meta( $user_id, self::WALLET_LOGO_ID, true ) );
			$logo_id  = $logo_id ?: absint( get_user_meta( $user_id, self::LOGO_META, true ) );
			$logo_url = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		}
		$logo_url = self::public_asset_url( $logo_url, $public_url );
		$has_credentials = (bool) ( $issuer_id && $class_suffix && is_email( $service_email ) && $private_key );
		$public_url_ready = self::is_public_https_url( $public_url );
		$logo_url_ready   = self::is_public_https_url( $logo_url );
		$configuration_error = '';
		if ( ! $has_credentials ) {
			$configuration_error = 'Complete the Issuer ID and service account credentials.';
		} elseif ( ! $public_url_ready ) {
			$configuration_error = 'Use a public HTTPS landing URL. Local and .local addresses cannot be reached by Google Wallet.';
		} elseif ( ! $logo_url_ready ) {
			$configuration_error = 'Use a logo hosted on a public HTTPS URL that Google Wallet can access.';
		}

		return array(
			'issuer_id'       => $issuer_id,
			'class_suffix'    => $class_suffix,
			'service_email'   => $service_email,
			'has_private_key' => '' !== $private_key,
			'public_url'      => $public_url,
			'logo_url'        => $logo_url,
			'logo_url_input'  => $logo_url_input,
			'logo_id'         => absint( get_user_meta( $user_id, self::WALLET_LOGO_ID, true ) ),
			'is_configured'   => $has_credentials && $public_url_ready && $logo_url_ready,
			'configuration_error' => $configuration_error,
			'uses_constants'  => defined( 'LOYALTY_WALLET_GOOGLE_ISSUER_ID' ) || defined( 'LOYALTY_WALLET_GOOGLE_SERVICE_ACCOUNT_EMAIL' ) || defined( 'LOYALTY_WALLET_GOOGLE_PRIVATE_KEY' ),
		);
	}

	public static function augment_review_data( int $user_id, array $google ): array {
		$flow_url                = self::landing_url( $user_id );
		$google['flow_url']      = $flow_url;
		$google['qr_target_url'] = $flow_url;
		$google['qr_image_url']  = ! empty( $google['review_url'] ) ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' . rawurlencode( $flow_url ) : '';
		$google['wallet']        = self::data( $user_id );
		return $google;
	}

	public static function save( int $user_id ): string {
		$issuer_id      = isset( $_POST['wallet_issuer_id'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['wallet_issuer_id'] ) ) : '';
		$class_suffix   = isset( $_POST['wallet_class_suffix'] ) ? sanitize_key( wp_unslash( $_POST['wallet_class_suffix'] ) ) : '';
		$service_email  = isset( $_POST['wallet_service_email'] ) ? sanitize_email( wp_unslash( $_POST['wallet_service_email'] ) ) : '';
		$public_url     = isset( $_POST['wallet_public_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_public_url'] ) ) ) : '';
		$logo_url       = isset( $_POST['wallet_logo_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_logo_url'] ) ) ) : '';
		$new_private_key = isset( $_POST['wallet_private_key'] ) ? trim( (string) wp_unslash( $_POST['wallet_private_key'] ) ) : '';
		$existing        = self::data( $user_id );
		if ( $existing['uses_constants'] ) {
			$issuer_id     = $existing['issuer_id'];
			$service_email = $existing['service_email'];
		}
		$has_any         = $issuer_id || $service_email || $new_private_key || $existing['has_private_key'];

		if ( $public_url && ! wp_http_validate_url( $public_url ) ) {
			return 'invalid_wallet_settings';
		}
		if ( $logo_url && ! self::is_public_https_url( $logo_url ) ) {
			return 'invalid_wallet_settings';
		}
		if ( $has_any && ( ! preg_match( '/^\d{5,30}$/', $issuer_id ) || ! preg_match( '/^[a-z0-9_-]{3,60}$/', $class_suffix ) || ! is_email( $service_email ) ) ) {
			return 'invalid_wallet_settings';
		}
		if ( $new_private_key && ! self::valid_private_key( $new_private_key ) ) {
			return 'invalid_wallet_private_key';
		}
		$media_logo_id = isset( $_POST['wallet_logo_media_id'] ) ? absint( $_POST['wallet_logo_media_id'] ) : 0;
		if ( $media_logo_id ) {
			$media_path = get_attached_file( $media_logo_id );
			$media_mime = (string) get_post_mime_type( $media_logo_id );
			if (
				! wp_attachment_is_image( $media_logo_id )
				|| ! in_array( $media_mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true )
				|| ! $media_path
				|| ! is_file( $media_path )
				|| filesize( $media_path ) > 5 * MB_IN_BYTES
			) {
				return 'invalid_wallet_logo';
			}
			update_user_meta( $user_id, self::WALLET_LOGO_ID, $media_logo_id );
			$logo_url = '';
		}
		if ( ! empty( $_FILES['wallet_logo_upload']['name'] ) ) {
			$logo_id = self::upload_logo();
			if ( is_wp_error( $logo_id ) ) {
				return 'invalid_wallet_logo';
			}
			update_user_meta( $user_id, self::WALLET_LOGO_ID, (int) $logo_id );
			$logo_url = '';
		}

		update_user_meta( $user_id, self::ISSUER_ID, $issuer_id );
		update_user_meta( $user_id, self::CLASS_SUFFIX, $class_suffix ?: 'loyalty_wallet_' . $user_id );
		update_user_meta( $user_id, self::SERVICE_EMAIL, $service_email );
		update_user_meta( $user_id, self::PUBLIC_URL, $public_url );
		update_user_meta( $user_id, self::LOGO_URL, $logo_url );
		if ( $new_private_key ) {
			update_user_meta( $user_id, self::PRIVATE_KEY, $new_private_key );
		}
		return 'url_saved';
	}

	public static function render_settings( int $user_id ): void {
		$wallet = self::data( $user_id );
		require LOYALTY_WALLET_DIR . 'views/google-wallet-settings.php';
	}

	public static function sync_loyalty_points( int $user_id, int $points ): bool {
		$wallet = self::data( $user_id );
		if ( ! $wallet['is_configured'] ) {
			update_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error', 'Google Wallet is not fully configured.' );
			return false;
		}

		$access_token = self::access_token( $user_id, $wallet );
		if ( ! $access_token ) {
			update_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error', 'Google OAuth access token request failed.' );
			return false;
		}

		$class_id  = $wallet['issuer_id'] . '.' . $wallet['class_suffix'];
		$list_url  = add_query_arg(
			array(
				'classId'    => $class_id,
				'maxResults' => 1000,
			),
			'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject'
		);
		$response  = wp_remote_get(
			$list_url,
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = is_wp_error( $response )
				? $response->get_error_message()
				: 'HTTP ' . wp_remote_retrieve_response_code( $response ) . ': ' . wp_remote_retrieve_body( $response );
			update_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error', sanitize_text_field( substr( $message, 0, 1000 ) ) );
			return false;
		}

		$payload   = json_decode( wp_remote_retrieve_body( $response ), true );
		$resources = is_array( $payload['resources'] ?? null ) ? $payload['resources'] : array();
		foreach ( $resources as $resource ) {
			$object_id = sanitize_text_field( (string) ( $resource['id'] ?? '' ) );
			if ( ! $object_id || $class_id !== (string) ( $resource['classId'] ?? '' ) ) {
				continue;
			}

			$patch = wp_remote_request(
				'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject/' . rawurlencode( $object_id ),
				array(
					'method'  => 'PATCH',
					'timeout' => 20,
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'loyaltyPoints' => array(
								'label'   => 'Points',
								'balance' => array( 'int' => min( 100000, max( 0, $points ) ) ),
							),
						)
					),
				)
			);
			$status = wp_remote_retrieve_response_code( $patch );
			if ( is_wp_error( $patch ) || $status < 200 || $status >= 300 ) {
				$message = is_wp_error( $patch )
					? $patch->get_error_message()
					: 'HTTP ' . $status . ': ' . wp_remote_retrieve_body( $patch );
				update_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error', sanitize_text_field( substr( $message, 0, 1000 ) ) );
				return false;
			}
		}

		delete_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error' );
		return true;
	}

	public static function landing_url( int $user_id ): string {
		$wallet = self::data( $user_id );
		return add_query_arg(
			array(
				'lw_review_wallet' => '1',
				'wallet'           => $user_id,
				'key'              => self::public_token( $user_id ),
			),
			$wallet['public_url']
		);
	}

	public static function maybe_render_landing(): void {
		if ( ! isset( $_GET['lw_review_wallet'], $_GET['wallet'], $_GET['key'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['lw_review_wallet'] ) ) ) {
			return;
		}

		$user_id = absint( $_GET['wallet'] );
		$key     = sanitize_text_field( wp_unslash( $_GET['key'] ) );
		if ( ! $user_id || ! hash_equals( self::public_token( $user_id ), $key ) ) {
			status_header( 404 );
			exit;
		}

		$google      = Loyalty_Wallet_Google_Reviews_Module::data( $user_id );
		$wallet      = self::data( $user_id );
		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) && isset( $_POST['lw_identity_action'] ) ) {
			Loyalty_Wallet_Google_Identity_Module::handle_public_request( $user_id, $google, $wallet );
		}
		$wallet_name = (string) get_user_meta( $user_id, self::NAME_META, true );
		$wallet_name = $wallet_name ?: 'Loyalty Wallet';
		$logo_id     = absint( get_user_meta( $user_id, self::LOGO_META, true ) );
		$logo_url    = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : $wallet['logo_url'];
		$logo_url    = self::public_asset_url( $logo_url, $wallet['public_url'] );
		$wallet_url  = '';
		$error       = '';
		$member_name = 'Loyalty member';
		$cookie_name = 'lw_wallet_member_' . $user_id;
		$member_id   = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

		if ( ! preg_match( '/^LW[A-Z0-9]{12}$/', $member_id ) ) {
			$member_id = 'LW' . strtoupper( substr( preg_replace( '/[^a-f0-9]/i', '', wp_generate_uuid4() ), 0, 12 ) );
			setcookie(
				$cookie_name,
				$member_id,
				array(
					'expires'  => time() + YEAR_IN_SECONDS,
					'path'     => COOKIEPATH ?: '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		$google_signin_enabled = ! empty( $google['client_id'] );
		if ( ! $wallet['is_configured'] ) {
			$error = $wallet['configuration_error'] ?: 'Google Wallet is not configured for this business yet.';
		} elseif ( ! $google_signin_enabled ) {
			$wallet_url = self::create_save_url( $user_id, $member_name, $member_id, $google, $wallet );
			if ( ! $wallet_url ) {
				$error = 'The Google Wallet pass could not be signed. Please contact the business.';
			}
		}

		nocache_headers();
		status_header( 200 );
		$public_style_url = self::public_asset_url(
			LOYALTY_WALLET_URL . 'assets/google-wallet-public.css',
			$wallet['public_url']
		);
		wp_enqueue_style( 'loyalty-wallet-google-wallet-public', $public_style_url, array(), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/google-wallet-public.css' ) );
		if ( $google_signin_enabled ) {
			wp_enqueue_script( 'loyalty-wallet-google-identity', 'https://accounts.google.com/gsi/client', array(), null, true );
			$public_script_url = self::public_asset_url(
				LOYALTY_WALLET_URL . 'assets/google-wallet-public.js',
				$wallet['public_url']
			);
			wp_enqueue_script( 'loyalty-wallet-google-wallet-public', $public_script_url, array( 'loyalty-wallet-google-identity' ), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/google-wallet-public.js' ), true );
		}
		require LOYALTY_WALLET_DIR . 'views/google-wallet-landing.php';
		exit;
	}

	public static function member_id_for_google_user( int $user_id, string $google_sub ): string {
		return 'LW' . strtoupper( substr( hash_hmac( 'sha256', $user_id . '|' . $google_sub, wp_salt( 'auth' ) ), 0, 12 ) );
	}

	public static function member_object_id( int $user_id, string $member_id ): string {
		return self::data( $user_id )['issuer_id'] . '.lw_' . substr( hash( 'sha256', self::public_token( $user_id ) . '|' . strtolower( $member_id ) ), 0, 40 );
	}

	public static function create_member_save_url( int $user_id, string $member_name, string $member_id, array $google, array $wallet ): string {
		return self::create_save_url( $user_id, $member_name, $member_id, $google, $wallet );
	}

	private static function create_save_url( int $user_id, string $member_name, string $member_id, array $google, array $wallet ): string {
		$private_key = self::credential( 'LOYALTY_WALLET_GOOGLE_PRIVATE_KEY', self::PRIVATE_KEY, $user_id );
		$object_id   = 'lw_' . substr( hash( 'sha256', self::public_token( $user_id ) . '|' . strtolower( $member_id ) ), 0, 40 );
		$class_id    = $wallet['issuer_id'] . '.' . $wallet['class_suffix'];
		$full_object = $wallet['issuer_id'] . '.' . $object_id;
		$origin      = (string) wp_parse_url( self::landing_url( $user_id ), PHP_URL_HOST );
		$logo_url    = $wallet['logo_url'];
		$points      = absint( $google['review_points'] ?? 0 );

		$loyalty_class = array(
			'id'           => $class_id,
			'issuerName'   => $wallet_name = (string) get_user_meta( $user_id, self::NAME_META, true ) ?: 'Loyalty Wallet',
			'reviewStatus' => 'UNDER_REVIEW',
			'programName'  => $wallet_name . ' Loyalty',
			'programLogo'  => array(
				'sourceUri'         => array( 'uri' => $logo_url ),
				'contentDescription' => array( 'defaultValue' => array( 'language' => 'en-US', 'value' => $wallet_name . ' logo' ) ),
			),
		);
		$loyalty_object = array(
			'id'            => $full_object,
			'classId'       => $class_id,
			'state'         => 'ACTIVE',
			'accountId'     => $member_id,
			'accountName'   => $member_name ?: 'Loyalty member',
			'loyaltyPoints' => array( 'label' => 'Points', 'balance' => array( 'int' => $points ) ),
			'barcode'       => array( 'type' => 'QR_CODE', 'value' => $member_id ),
		);
		$claims = array(
			'iss'     => $wallet['service_email'],
			'aud'     => 'google',
			'typ'     => 'savetowallet',
			'iat'     => time(),
			'origins' => array( $origin ),
			'payload' => array(
				'loyaltyClasses' => array( $loyalty_class ),
				'loyaltyObjects' => array( $loyalty_object ),
			),
		);
		$jwt = self::sign_jwt( $claims, $private_key );
		return $jwt ? 'https://pay.google.com/gp/v/save/' . $jwt : '';
	}

	private static function sign_jwt( array $claims, string $private_key ): string {
		$header  = self::base64_url_encode( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
		$payload = self::base64_url_encode( wp_json_encode( $claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		$input   = $header . '.' . $payload;
		$signature = '';
		if ( ! openssl_sign( $input, $signature, $private_key, OPENSSL_ALGO_SHA256 ) ) {
			return '';
		}
		return $input . '.' . self::base64_url_encode( $signature );
	}

	private static function access_token( int $user_id, array $wallet ): string {
		$cached = get_transient( 'lw_google_wallet_token_' . $user_id );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$private_key = self::credential( 'LOYALTY_WALLET_GOOGLE_PRIVATE_KEY', self::PRIVATE_KEY, $user_id );
		$issued_at   = time();
		$assertion   = self::sign_jwt(
			array(
				'iss'   => $wallet['service_email'],
				'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
				'aud'   => 'https://oauth2.googleapis.com/token',
				'iat'   => $issued_at,
				'exp'   => $issued_at + 3600,
			),
			$private_key
		);
		if ( ! $assertion ) {
			return '';
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $assertion,
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		$token   = sanitize_text_field( (string) ( $payload['access_token'] ?? '' ) );
		if ( $token ) {
			$expires_in = max( 60, absint( $payload['expires_in'] ?? 3600 ) - 120 );
			set_transient( 'lw_google_wallet_token_' . $user_id, $token, $expires_in );
		}
		return $token;
	}

	private static function base64_url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	private static function valid_private_key( string $private_key ): bool {
		$key = openssl_pkey_get_private( $private_key );
		if ( false === $key ) {
			return false;
		}
		$details = openssl_pkey_get_details( $key );
		if ( is_resource( $key ) ) {
			openssl_free_key( $key );
		}
		return is_array( $details ) && OPENSSL_KEYTYPE_RSA === ( $details['type'] ?? null );
	}

	private static function credential( string $constant, string $meta_key, int $user_id ): string {
		return defined( $constant ) ? trim( (string) constant( $constant ) ) : trim( (string) get_user_meta( $user_id, $meta_key, true ) );
	}

	private static function is_public_https_url( string $url ): bool {
		if ( ! wp_http_validate_url( $url ) || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return false;
		}
		$host = strtolower( rtrim( (string) wp_parse_url( $url, PHP_URL_HOST ), '.' ) );
		if ( ! $host || preg_match( '/(?:^|\\.)(?:localhost|local|test|invalid)$/', $host ) ) {
			return false;
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return (bool) filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}
		return true;
	}

	private static function public_asset_url( string $asset_url, string $public_url ): string {
		if ( ! $asset_url || ! self::is_public_https_url( $public_url ) ) {
			return $asset_url;
		}
		$path = (string) wp_parse_url( $asset_url, PHP_URL_PATH );
		if ( ! $path ) {
			return $asset_url;
		}
		$scheme = (string) wp_parse_url( $public_url, PHP_URL_SCHEME );
		$host   = (string) wp_parse_url( $public_url, PHP_URL_HOST );
		$port   = wp_parse_url( $public_url, PHP_URL_PORT );
		return $scheme . '://' . $host . ( $port ? ':' . absint( $port ) : '' ) . $path;
	}

	private static function upload_logo() {
		if ( empty( $_FILES['wallet_logo_upload']['tmp_name'] ) || (int) $_FILES['wallet_logo_upload']['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'invalid_wallet_logo' );
		}
		$source_path = (string) $_FILES['wallet_logo_upload']['tmp_name'];
		$dimensions  = wp_getimagesize( $source_path );
		if ( ! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 8000 || $dimensions[1] > 8000 ) {
			return new WP_Error( 'invalid_wallet_logo' );
		}

		$source_bytes = file_get_contents( $source_path );
		$source_image = $source_bytes ? imagecreatefromstring( $source_bytes ) : false;
		if ( ! $source_image ) {
			return new WP_Error( 'invalid_wallet_logo' );
		}

		$canvas_size = 660;
		$safe_size   = 462; // Google recommends keeping the logo inside a 15% safe margin.
		$canvas      = imagecreatetruecolor( $canvas_size, $canvas_size );
		imagealphablending( $canvas, false );
		imagesavealpha( $canvas, true );
		$transparent = imagecolorallocatealpha( $canvas, 255, 255, 255, 127 );
		imagefilledrectangle( $canvas, 0, 0, $canvas_size, $canvas_size, $transparent );

		$scale         = min( $safe_size / $dimensions[0], $safe_size / $dimensions[1] );
		$target_width  = max( 1, (int) round( $dimensions[0] * $scale ) );
		$target_height = max( 1, (int) round( $dimensions[1] * $scale ) );
		$target_x      = (int) floor( ( $canvas_size - $target_width ) / 2 );
		$target_y      = (int) floor( ( $canvas_size - $target_height ) / 2 );
		imagecopyresampled( $canvas, $source_image, $target_x, $target_y, 0, 0, $target_width, $target_height, $dimensions[0], $dimensions[1] );

		$normalized_path = wp_tempnam( 'loyalty-wallet-logo.png' );
		$normalized      = $normalized_path && imagepng( $canvas, $normalized_path, 7 );
		imagedestroy( $source_image );
		imagedestroy( $canvas );
		if ( ! $normalized ) {
			return new WP_Error( 'invalid_wallet_logo' );
		}

		$original_name = sanitize_file_name( (string) $_FILES['wallet_logo_upload']['name'] );
		$base_name     = pathinfo( $original_name, PATHINFO_FILENAME );
		$_FILES['wallet_logo_upload']['tmp_name'] = $normalized_path;
		$_FILES['wallet_logo_upload']['name']     = ( $base_name ?: 'google-wallet-logo' ) . '-wallet.png';
		$_FILES['wallet_logo_upload']['type']     = 'image/png';
		$_FILES['wallet_logo_upload']['size']     = filesize( $normalized_path );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		return media_handle_upload(
			'wallet_logo_upload',
			0,
			array( 'post_title' => 'Google Wallet logo' ),
			array(
				'test_form' => false,
				// The browser upload has already been validated above. Its normalized
				// PNG lives in a new temporary file, so it is no longer recognized by
				// PHP as the original HTTP-uploaded file.
				'action'    => 'wp_handle_sideload',
				'mimes'     => array( 'png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'webp' => 'image/webp' ),
			)
		);
	}

	private static function public_token( int $user_id ): string {
		$token = (string) get_user_meta( $user_id, self::PUBLIC_TOKEN, true );
		if ( ! preg_match( '/^[a-f0-9]{40}$/', $token ) ) {
			$token = wp_generate_password( 40, false, false );
			$token = substr( hash( 'sha256', $token . '|' . $user_id . '|' . wp_salt( 'auth' ) ), 0, 40 );
			update_user_meta( $user_id, self::PUBLIC_TOKEN, $token );
		}
		return $token;
	}
}
