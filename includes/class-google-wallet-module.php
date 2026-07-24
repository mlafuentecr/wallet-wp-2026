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
	private const HERO_URL      = '_loyalty_wallet_google_wallet_hero_url';
	private const HERO_ID       = '_loyalty_wallet_google_wallet_hero_id';
	private const HERO_RANDOM_SEED = '_loyalty_wallet_google_wallet_hero_random_seed';
	private const BACKGROUND_COLOR = '_loyalty_wallet_google_wallet_background_color';
	private const PROMO_ENABLED = '_loyalty_wallet_google_wallet_promo_enabled';
	private const PROMO_TITLE   = '_loyalty_wallet_google_wallet_promo_title';
	private const PROMO_BODY    = '_loyalty_wallet_google_wallet_promo_body';
	private const PROMO_URL     = '_loyalty_wallet_google_wallet_promo_url';
	private const PROMO_IMAGE_URL = '_loyalty_wallet_google_wallet_promo_image_url';
	private const PROMO_IMAGE_ID  = '_loyalty_wallet_google_wallet_promo_image_id';
	private const APPOINTMENT_ENABLED = '_loyalty_wallet_google_wallet_appointment_enabled';
	private const APPOINTMENT_URL     = '_loyalty_wallet_google_wallet_appointment_url';
	private const APPOINTMENT_LABEL   = '_loyalty_wallet_google_wallet_appointment_label';
	private const PROMOTION_HISTORY   = '_loyalty_wallet_google_wallet_promotion_history';
	private const PUBLIC_TOKEN  = '_loyalty_wallet_google_wallet_public_token';
	private const NAME_META     = '_loyalty_wallet_name';
	private const PROGRAM_NAME_META = '_loyalty_wallet_program_name';
	private const CONTACT_HELP_META = '_loyalty_wallet_google_wallet_contact_help';
	private const LOGO_META     = '_loyalty_wallet_logo_id';
	private const WEBSITE_META  = '_loyalty_wallet_website';
	private const WHATSAPP_META = '_loyalty_wallet_business_whatsapp';
	private const TEMPLATE_VERSION_META = '_loyalty_wallet_google_wallet_template_version';
	private const TEMPLATE_VERSION = '11';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_landing' ), 0 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_template' ) );
	}

	public static function maybe_upgrade_template(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'loyalty-wallet' !== $page || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( self::TEMPLATE_VERSION === (string) get_user_meta( $user_id, self::TEMPLATE_VERSION_META, true ) ) {
			return;
		}

		$wallet = self::data( $user_id );
		if (
			$wallet['is_configured']
			&& self::sync_loyalty_points( $user_id, Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id ) )
		) {
			update_user_meta( $user_id, self::TEMPLATE_VERSION_META, self::TEMPLATE_VERSION );
		}
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
		$hero_url_input = (string) get_user_meta( $user_id, self::HERO_URL, true );
		$hero_id        = absint( get_user_meta( $user_id, self::HERO_ID, true ) );
		$hero_random_seed = sanitize_key( (string) get_user_meta( $user_id, self::HERO_RANDOM_SEED, true ) );
		$hero_random_seed = $hero_random_seed ?: 'loyalty-wallet-' . $user_id;
		$hero_mode      = ( $hero_url_input || $hero_id ) ? 'custom' : 'random';
		$hero_url       = $hero_url_input;
		if ( ! $hero_url && $hero_id ) {
			$hero_url = $hero_id ? (string) wp_get_attachment_image_url( $hero_id, 'full' ) : '';
		}
		if ( ! $hero_url ) {
			$hero_url = self::random_hero_url( $hero_random_seed );
		}
		$hero_url = self::public_asset_url( $hero_url, $public_url );
		$background_color = sanitize_hex_color( (string) get_user_meta( $user_id, self::BACKGROUND_COLOR, true ) );
		$wallet_name      = (string) get_user_meta( $user_id, self::NAME_META, true ) ?: 'Loyalty Wallet';
		$program_name     = (string) get_user_meta( $user_id, self::PROGRAM_NAME_META, true );
		$program_name     = $program_name ?: $wallet_name . ' Loyalty';
		$contact_help     = self::contact_help( $user_id );
		$promo_image_url_input = (string) get_user_meta( $user_id, self::PROMO_IMAGE_URL, true );
		$promo_image_id        = absint( get_user_meta( $user_id, self::PROMO_IMAGE_ID, true ) );
		$promo_image_url       = $promo_image_url_input;
		if ( ! $promo_image_url && $promo_image_id ) {
			$promo_image_url = (string) wp_get_attachment_image_url( $promo_image_id, 'medium' );
		}
		$promo_image_url = self::public_asset_url( $promo_image_url, $public_url );
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
			'hero_url'        => $hero_url,
			'hero_url_input'  => $hero_url_input,
			'hero_id'         => $hero_id,
			'hero_mode'       => $hero_mode,
			'hero_random_seed' => $hero_random_seed,
			'background_color' => $background_color,
			'background_color_input' => $background_color ?: '#1a1a1a',
			'program_name'     => $program_name,
			'contact_help'     => $contact_help,
			'promo_enabled'    => '1' === (string) get_user_meta( $user_id, self::PROMO_ENABLED, true ),
			'promo_title'      => (string) get_user_meta( $user_id, self::PROMO_TITLE, true ) ?: 'Promociones',
			'promo_body'       => (string) get_user_meta( $user_id, self::PROMO_BODY, true ) ?: 'Revisa promociones disponibles',
			'promo_url'        => (string) get_user_meta( $user_id, self::PROMO_URL, true ),
			'promo_image_url'  => $promo_image_url,
			'promo_image_url_input' => $promo_image_url_input,
			'promo_image_id'   => $promo_image_id,
			'appointment_enabled' => '1' === (string) get_user_meta( $user_id, self::APPOINTMENT_ENABLED, true ),
			'appointment_url'     => (string) get_user_meta( $user_id, self::APPOINTMENT_URL, true ),
			'appointment_label'   => (string) get_user_meta( $user_id, self::APPOINTMENT_LABEL, true ) ?: 'Hacer cita',
			'promotion_history_count' => count( self::promotion_history( $user_id ) ),
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
		$subsection = isset( $_POST['wallet_subsection'] ) ? sanitize_key( wp_unslash( $_POST['wallet_subsection'] ) ) : 'configuration';
		if ( ! in_array( $subsection, array( 'configuration', 'design', 'promotions' ), true ) ) {
			$subsection = 'configuration';
		}
		$issuer_id      = isset( $_POST['wallet_issuer_id'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['wallet_issuer_id'] ) ) : '';
		$class_suffix   = isset( $_POST['wallet_class_suffix'] ) ? sanitize_key( wp_unslash( $_POST['wallet_class_suffix'] ) ) : '';
		$service_email  = isset( $_POST['wallet_service_email'] ) ? sanitize_email( wp_unslash( $_POST['wallet_service_email'] ) ) : '';
		$public_url     = isset( $_POST['wallet_public_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_public_url'] ) ) ) : '';
		$logo_url       = isset( $_POST['wallet_logo_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_logo_url'] ) ) ) : '';
		$hero_url       = isset( $_POST['wallet_hero_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_hero_url'] ) ) ) : '';
		$hero_mode      = isset( $_POST['wallet_hero_mode'] ) && 'random' === sanitize_key( wp_unslash( $_POST['wallet_hero_mode'] ) ) ? 'random' : 'custom';
		$hero_random_seed = isset( $_POST['wallet_hero_random_seed'] ) ? sanitize_key( wp_unslash( $_POST['wallet_hero_random_seed'] ) ) : '';
		$hero_random_seed = $hero_random_seed ?: 'loyalty-wallet-' . $user_id;
		if ( $hero_url || ! empty( $_FILES['wallet_hero_upload']['name'] ) || ! empty( $_POST['wallet_hero_media_id'] ) ) {
			$hero_mode = 'custom';
		}
		$background_color = isset( $_POST['wallet_background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['wallet_background_color'] ) ) : '';
		$program_name     = isset( $_POST['wallet_program_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wallet_program_name'] ) ) : '';
		$contact_help_posted = isset( $_POST['wallet_contact_help'] );
		$contact_help     = $contact_help_posted ? sanitize_textarea_field( wp_unslash( $_POST['wallet_contact_help'] ) ) : '';
		$promo_enabled    = isset( $_POST['wallet_promo_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['wallet_promo_enabled'] ) );
		$promo_title      = isset( $_POST['wallet_promo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wallet_promo_title'] ) ) : '';
		$promo_body       = isset( $_POST['wallet_promo_body'] ) ? sanitize_text_field( wp_unslash( $_POST['wallet_promo_body'] ) ) : '';
		$promo_url        = isset( $_POST['wallet_promo_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_promo_url'] ) ) ) : '';
		$promo_image_url  = isset( $_POST['wallet_promo_image_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_promo_image_url'] ) ) ) : '';
		$appointment_enabled = isset( $_POST['wallet_appointment_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['wallet_appointment_enabled'] ) );
		$appointment_url     = isset( $_POST['wallet_appointment_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_appointment_url'] ) ) ) : '';
		$appointment_label   = isset( $_POST['wallet_appointment_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wallet_appointment_label'] ) ) : '';
		$new_private_key = '';
		$existing        = self::data( $user_id );
		$program_name    = $program_name ?: $existing['program_name'];
		$contact_help    = $contact_help_posted ? $contact_help : $existing['contact_help'];
		$promo_title     = $promo_title ?: $existing['promo_title'];
		$promo_body      = $promo_body ?: $existing['promo_body'];
		$appointment_label = $appointment_label ?: $existing['appointment_label'];
		if ( 'configuration' !== $subsection ) {
			$issuer_id     = $existing['issuer_id'];
			$class_suffix  = $existing['class_suffix'];
			$service_email = $existing['service_email'];
			$public_url    = $existing['public_url'];
		}
		if ( 'design' !== $subsection ) {
			$logo_url        = $existing['logo_url_input'];
			$hero_url        = $existing['hero_url_input'];
			$hero_mode       = $existing['hero_mode'];
			$hero_random_seed = $existing['hero_random_seed'];
			$background_color = $existing['background_color_input'];
			$program_name     = $existing['program_name'];
			$contact_help     = $existing['contact_help'];
		}
		if ( 'promotions' !== $subsection ) {
			$promo_enabled       = $existing['promo_enabled'];
			$promo_title         = $existing['promo_title'];
			$promo_body          = $existing['promo_body'];
			$promo_url           = $existing['promo_url'];
			$promo_image_url     = $existing['promo_image_url_input'];
			$appointment_enabled = $existing['appointment_enabled'];
			$appointment_url     = $existing['appointment_url'];
			$appointment_label   = $existing['appointment_label'];
		}
		if ( 'promotions' === $subsection && $appointment_enabled && ! $appointment_url ) {
			$business_whatsapp = preg_replace( '/\D+/', '', (string) get_user_meta( $user_id, self::WHATSAPP_META, true ) );
			if ( strlen( $business_whatsapp ) >= 8 && strlen( $business_whatsapp ) <= 15 ) {
				$appointment_url = 'https://wa.me/' . $business_whatsapp;
			}
		}
		if ( 'configuration' === $subsection && ! $existing['uses_constants'] && ! empty( $_FILES['wallet_service_account_json']['name'] ) ) {
			$credentials = self::service_account_credentials_from_upload();
			if ( is_wp_error( $credentials ) ) {
				return 'invalid_wallet_service_account_json';
			}
			$service_email  = $credentials['client_email'];
			$new_private_key = $credentials['private_key'];
		}
		if ( $existing['uses_constants'] ) {
			$issuer_id     = $existing['issuer_id'];
			$service_email = $existing['service_email'];
		}
		$has_any         = $issuer_id || $service_email || $new_private_key || $existing['has_private_key'];

		if ( 'configuration' === $subsection && $public_url && ! wp_http_validate_url( $public_url ) ) {
			return 'invalid_wallet_settings';
		}
		if ( 'design' === $subsection && $logo_url && ! self::is_public_https_url( $logo_url ) ) {
			return 'invalid_wallet_settings';
		}
		if ( 'design' === $subsection && $hero_url && ! self::is_public_https_url( $hero_url ) ) {
			return 'invalid_wallet_design';
		}
		if ( 'design' === $subsection && ! $background_color ) {
			return 'invalid_wallet_design';
		}
		if ( 'design' === $subsection && '' === $program_name ) {
			return 'invalid_program_name';
		}
		if ( 'promotions' === $subsection && $promo_image_url && ! self::is_public_https_url( $promo_image_url ) ) {
			return 'invalid_wallet_promotion';
		}
		if ( 'promotions' === $subsection && $promo_enabled && ( ! $promo_title || ! self::is_public_https_url( $promo_url ) ) ) {
			return 'invalid_wallet_promotion';
		}
		if ( 'promotions' === $subsection && $appointment_enabled && ( ! $appointment_label || ! self::is_public_https_url( $appointment_url ) ) ) {
			return 'invalid_wallet_appointment';
		}
		if ( 'configuration' === $subsection && $has_any && ( ! preg_match( '/^\d{5,30}$/', $issuer_id ) || ! preg_match( '/^[a-z0-9_-]{3,60}$/', $class_suffix ) || ! is_email( $service_email ) ) ) {
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
		if ( 'random' === $hero_mode ) {
			delete_user_meta( $user_id, self::HERO_ID );
			$hero_url = '';
		} else {
			$hero_media_id = isset( $_POST['wallet_hero_media_id'] ) ? absint( $_POST['wallet_hero_media_id'] ) : 0;
			if ( $hero_media_id ) {
				if ( ! self::valid_wallet_image( $hero_media_id ) ) {
					return 'invalid_wallet_hero';
				}
				update_user_meta( $user_id, self::HERO_ID, $hero_media_id );
				$hero_url = '';
			}
			if ( ! empty( $_FILES['wallet_hero_upload']['name'] ) ) {
				$hero_id = self::upload_hero();
				if ( is_wp_error( $hero_id ) ) {
					return 'invalid_wallet_hero';
				}
				update_user_meta( $user_id, self::HERO_ID, (int) $hero_id );
				$hero_url = '';
			}
		}
		$promo_media_id = isset( $_POST['wallet_promo_image_media_id'] ) ? absint( $_POST['wallet_promo_image_media_id'] ) : 0;
		if ( $promo_media_id ) {
			if ( ! self::valid_wallet_image( $promo_media_id ) ) {
				return 'invalid_wallet_promotion';
			}
			update_user_meta( $user_id, self::PROMO_IMAGE_ID, $promo_media_id );
			$promo_image_url = '';
		}
		if ( ! empty( $_FILES['wallet_promo_image_upload']['name'] ) ) {
			$promo_image_id = self::upload_promo_image();
			if ( is_wp_error( $promo_image_id ) ) {
				return 'invalid_wallet_promotion';
			}
			update_user_meta( $user_id, self::PROMO_IMAGE_ID, (int) $promo_image_id );
			$promo_image_url = '';
		}

		if ( 'configuration' === $subsection ) {
			update_user_meta( $user_id, self::ISSUER_ID, $issuer_id );
			update_user_meta( $user_id, self::CLASS_SUFFIX, $class_suffix ?: 'loyalty_wallet_' . $user_id );
			update_user_meta( $user_id, self::SERVICE_EMAIL, $service_email );
			update_user_meta( $user_id, self::PUBLIC_URL, $public_url );
			if ( $new_private_key ) {
				update_user_meta( $user_id, self::PRIVATE_KEY, $new_private_key );
			}
		}
		if ( 'design' === $subsection ) {
			update_user_meta( $user_id, self::LOGO_URL, $logo_url );
			update_user_meta( $user_id, self::HERO_URL, $hero_url );
			update_user_meta( $user_id, self::HERO_RANDOM_SEED, $hero_random_seed );
			update_user_meta( $user_id, self::BACKGROUND_COLOR, $background_color );
			update_user_meta( $user_id, self::PROGRAM_NAME_META, $program_name );
			update_user_meta( $user_id, self::CONTACT_HELP_META, mb_substr( $contact_help, 0, 160 ) );
		}
		if ( 'promotions' === $subsection ) {
			self::backup_promotion_settings( $user_id, $existing );
			update_user_meta( $user_id, self::PROMO_ENABLED, $promo_enabled ? '1' : '0' );
			update_user_meta( $user_id, self::PROMO_TITLE, mb_substr( $promo_title, 0, 60 ) );
			update_user_meta( $user_id, self::PROMO_BODY, mb_substr( $promo_body, 0, 50 ) );
			update_user_meta( $user_id, self::PROMO_URL, $promo_url );
			update_user_meta( $user_id, self::PROMO_IMAGE_URL, $promo_image_url );
			update_user_meta( $user_id, self::APPOINTMENT_ENABLED, $appointment_enabled ? '1' : '0' );
			update_user_meta( $user_id, self::APPOINTMENT_URL, $appointment_url );
			update_user_meta( $user_id, self::APPOINTMENT_LABEL, mb_substr( $appointment_label, 0, 30 ) );
		}
		return 'url_saved';
	}

	public static function restore_promotion_settings( int $user_id ): string {
		$history = self::promotion_history( $user_id );
		$backup  = array_pop( $history );
		if ( ! is_array( $backup ) ) {
			return 'wallet_no_promotion_backup';
		}

		update_user_meta( $user_id, self::PROMOTION_HISTORY, $history );
		update_user_meta( $user_id, self::PROMO_ENABLED, ! empty( $backup['promo_enabled'] ) ? '1' : '0' );
		update_user_meta( $user_id, self::PROMO_TITLE, sanitize_text_field( (string) ( $backup['promo_title'] ?? '' ) ) );
		update_user_meta( $user_id, self::PROMO_BODY, sanitize_text_field( (string) ( $backup['promo_body'] ?? '' ) ) );
		update_user_meta( $user_id, self::PROMO_URL, esc_url_raw( (string) ( $backup['promo_url'] ?? '' ) ) );
		update_user_meta( $user_id, self::PROMO_IMAGE_URL, esc_url_raw( (string) ( $backup['promo_image_url_input'] ?? '' ) ) );
		update_user_meta( $user_id, self::PROMO_IMAGE_ID, absint( $backup['promo_image_id'] ?? 0 ) );
		update_user_meta( $user_id, self::APPOINTMENT_ENABLED, ! empty( $backup['appointment_enabled'] ) ? '1' : '0' );
		update_user_meta( $user_id, self::APPOINTMENT_URL, esc_url_raw( (string) ( $backup['appointment_url'] ?? '' ) ) );
		update_user_meta( $user_id, self::APPOINTMENT_LABEL, sanitize_text_field( (string) ( $backup['appointment_label'] ?? '' ) ) );
		return 'wallet_promotions_restored';
	}

	private static function backup_promotion_settings( int $user_id, array $wallet ): void {
		$history   = self::promotion_history( $user_id );
		$snapshot  = array(
			'promo_enabled'       => ! empty( $wallet['promo_enabled'] ),
			'promo_title'         => (string) ( $wallet['promo_title'] ?? '' ),
			'promo_body'          => (string) ( $wallet['promo_body'] ?? '' ),
			'promo_url'           => (string) ( $wallet['promo_url'] ?? '' ),
			'promo_image_url_input' => (string) ( $wallet['promo_image_url_input'] ?? '' ),
			'promo_image_id'      => absint( $wallet['promo_image_id'] ?? 0 ),
			'appointment_enabled' => ! empty( $wallet['appointment_enabled'] ),
			'appointment_url'     => (string) ( $wallet['appointment_url'] ?? '' ),
			'appointment_label'   => (string) ( $wallet['appointment_label'] ?? '' ),
			'saved_at'            => current_time( 'mysql' ),
		);
		$last = $history ? end( $history ) : null;
		if ( is_array( $last ) ) {
			$comparison_last = $last;
			unset( $comparison_last['saved_at'] );
			$comparison_snapshot = $snapshot;
			unset( $comparison_snapshot['saved_at'] );
			if ( $comparison_last === $comparison_snapshot ) {
				return;
			}
		}
		$history[] = $snapshot;
		update_user_meta( $user_id, self::PROMOTION_HISTORY, array_slice( $history, -10 ) );
	}

	private static function promotion_history( int $user_id ): array {
		$history = get_user_meta( $user_id, self::PROMOTION_HISTORY, true );
		return is_array( $history ) ? array_values( array_filter( $history, 'is_array' ) ) : array();
	}

	private static function service_account_credentials_from_upload() {
		$file = $_FILES['wallet_service_account_json'] ?? array();
		if (
			! is_array( $file )
			|| UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE )
			|| empty( $file['tmp_name'] )
			|| empty( $file['name'] )
			|| 'json' !== strtolower( (string) pathinfo( sanitize_file_name( (string) $file['name'] ), PATHINFO_EXTENSION ) )
			|| (int) ( $file['size'] ?? 0 ) < 1
			|| (int) ( $file['size'] ?? 0 ) > MB_IN_BYTES
			|| ! is_uploaded_file( (string) $file['tmp_name'] )
		) {
			return new WP_Error( 'invalid_wallet_service_account_json' );
		}

		$contents = file_get_contents( (string) $file['tmp_name'] );
		if ( false === $contents || strlen( $contents ) > MB_IN_BYTES ) {
			return new WP_Error( 'invalid_wallet_service_account_json' );
		}

		$credentials = json_decode( $contents, true, 16 );
		$type         = is_array( $credentials ) ? (string) ( $credentials['type'] ?? '' ) : '';
		$project_id   = is_array( $credentials ) ? sanitize_text_field( (string) ( $credentials['project_id'] ?? '' ) ) : '';
		$client_email = is_array( $credentials ) ? sanitize_email( (string) ( $credentials['client_email'] ?? '' ) ) : '';
		$private_key  = is_array( $credentials ) ? trim( (string) ( $credentials['private_key'] ?? '' ) ) : '';
		$token_uri    = is_array( $credentials ) ? esc_url_raw( (string) ( $credentials['token_uri'] ?? '' ) ) : '';
		$email_suffix = $project_id ? '@' . $project_id . '.iam.gserviceaccount.com' : '';
		if (
			'service_account' !== $type
			|| ! preg_match( '/^[a-z][a-z0-9-]{4,28}[a-z0-9]$/', $project_id )
			|| ! is_email( $client_email )
			|| $email_suffix !== substr( $client_email, -strlen( $email_suffix ) )
			|| 'https://oauth2.googleapis.com/token' !== $token_uri
			|| ! self::valid_private_key( $private_key )
		) {
			return new WP_Error( 'invalid_wallet_service_account_json' );
		}

		return array(
			'client_email' => $client_email,
			'private_key'  => $private_key,
		);
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
		$class_patch = wp_remote_request(
			'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyClass/' . rawurlencode( $class_id ),
			array(
				'method'  => 'PATCH',
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( self::loyalty_class_fields( $user_id, $wallet, true ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			)
		);
		$class_status = wp_remote_retrieve_response_code( $class_patch );
		if ( is_wp_error( $class_patch ) || ( 404 !== $class_status && ( $class_status < 200 || $class_status >= 300 ) ) ) {
			$message = is_wp_error( $class_patch )
				? $class_patch->get_error_message()
				: 'HTTP ' . $class_status . ': ' . wp_remote_retrieve_body( $class_patch );
			update_user_meta( $user_id, '_loyalty_wallet_google_wallet_sync_error', sanitize_text_field( substr( $message, 0, 1000 ) ) );
			return false;
		}

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
		$customers = Loyalty_Wallet_Customers_Module::all( $user_id );
		$customers_by_object = array();
		foreach ( $customers as $customer ) {
			if ( ! empty( $customer['wallet_object_id'] ) ) {
				$customers_by_object[ (string) $customer['wallet_object_id'] ] = $customer;
			}
		}

		foreach ( $resources as $resource ) {
			$object_id = sanitize_text_field( (string) ( $resource['id'] ?? '' ) );
			if ( ! $object_id || $class_id !== (string) ( $resource['classId'] ?? '' ) ) {
				continue;
			}
			$customer = $customers_by_object[ $object_id ] ?? array(
				'name'             => sanitize_text_field( (string) ( $resource['accountName'] ?? 'Loyalty member' ) ),
				'points'           => absint( $resource['loyaltyPoints']['balance']['int'] ?? $points ),
				'wallet_member_id' => sanitize_text_field( (string) ( $resource['accountId'] ?? $resource['barcode']['value'] ?? '' ) ),
				'next_visit'       => '',
			);
			$payload  = self::customer_object_fields( $user_id, $customer );

			$patch = wp_remote_request(
				'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject/' . rawurlencode( $object_id ),
				array(
					'method'  => 'PATCH',
					'timeout' => 20,
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
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

	public static function sync_customer_pass( int $user_id, array $customer ): bool {
		$object_id = sanitize_text_field( (string) ( $customer['wallet_object_id'] ?? '' ) );
		$wallet    = self::data( $user_id );
		if ( ! $object_id || ! $wallet['is_configured'] ) {
			return false;
		}

		$access_token = self::access_token( $user_id, $wallet );
		if ( ! $access_token ) {
			return false;
		}

		$response = wp_remote_request(
			'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject/' . rawurlencode( $object_id ),
			array(
				'method'  => 'PATCH',
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( self::customer_object_fields( $user_id, $customer ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			)
		);
		$status = wp_remote_retrieve_response_code( $response );
		return ! is_wp_error( $response ) && $status >= 200 && $status < 300;
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
		$points      = absint( $google['review_points'] ?? 0 );
		$next_visit  = 'No programada';
		$business_links = self::business_links( $user_id );
		$existing_customer = null;
		foreach ( Loyalty_Wallet_Customers_Module::all( $user_id ) as $candidate ) {
			if ( $member_id && hash_equals( (string) ( $candidate['wallet_member_id'] ?? '' ), $member_id ) ) {
				$existing_customer = $candidate;
				break;
			}
		}

		$loyalty_class = array_merge(
			array(
				'id'           => $class_id,
				'reviewStatus' => 'UNDER_REVIEW',
			),
			self::loyalty_class_fields( $user_id, $wallet )
		);
		$loyalty_object = array(
			'id'            => $full_object,
			'classId'       => $class_id,
			'state'         => 'ACTIVE',
			'accountId'     => $member_id,
			'accountName'   => $member_name ?: 'Loyalty member',
			'loyaltyPoints' => array( 'label' => 'Puntos', 'balance' => array( 'int' => $points ) ),
			'barcode'       => array( 'type' => 'QR_CODE', 'value' => $member_id, 'alternateText' => ' ' ),
			'textModulesData' => self::business_text_modules( $user_id, $next_visit ),
		);
		if ( $business_links ) {
			$loyalty_object['linksModuleData'] = array( 'uris' => $business_links );
		}
		if ( $existing_customer ) {
			$loyalty_object = array_merge( $loyalty_object, self::customer_object_fields( $user_id, $existing_customer ) );
		}
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

	private static function customer_object_fields( int $user_id, array $customer ): array {
		$points       = min( 100000, max( 0, absint( $customer['points'] ?? 0 ) ) );
		$member_id    = sanitize_text_field( (string) ( $customer['wallet_member_id'] ?? $customer['id'] ?? '' ) );
		$next_visit   = sanitize_text_field( (string) ( $customer['next_visit'] ?? '' ) );
		$next_display = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $next_visit )
			? wp_date( 'd/m/Y', strtotime( $next_visit ) )
			: 'No programada';
		$fields = array(
			'accountName'   => sanitize_text_field( (string) ( $customer['name'] ?? 'Loyalty member' ) ),
			'loyaltyPoints' => array( 'label' => 'Puntos', 'balance' => array( 'int' => $points ) ),
			'barcode'       => array(
				'type'          => 'QR_CODE',
				'value'         => Loyalty_Wallet_Rewards_Module::barcode_payload( $user_id, $customer ),
				// Google displays the encoded value when alternateText is omitted.
				'alternateText' => ' ',
			),
			'textModulesData' => self::business_text_modules( $user_id, $next_display ),
		);
		$links = self::business_links( $user_id );
		if ( $links ) {
			$fields['linksModuleData'] = array( 'uris' => $links );
		}
		return $fields;
	}

	private static function loyalty_class_fields( int $user_id, array $wallet, bool $include_disabled_actions = false ): array {
		$wallet_name = (string) get_user_meta( $user_id, self::NAME_META, true ) ?: 'Loyalty Wallet';
		$program_name = (string) get_user_meta( $user_id, self::PROGRAM_NAME_META, true );
		$program_name = $program_name ?: $wallet_name . ' Loyalty';
		$fields = array(
			'issuerName'       => $wallet_name,
			'programName'      => $program_name,
			'reviewStatus'     => 'UNDER_REVIEW',
			'accountNameLabel' => 'Nombre',
			'accountIdLabel'   => 'Cliente',
			'programLogo'      => array(
				'sourceUri'          => array( 'uri' => $wallet['logo_url'] ),
				'contentDescription' => array( 'defaultValue' => array( 'language' => 'es', 'value' => $wallet_name . ' logo' ) ),
			),
			'classTemplateInfo' => array(
				'cardTemplateOverride' => array(
					'cardRowTemplateInfos' => array(
						array(
							'threeItems' => array(
								'startItem' => self::template_item( 'object.accountName' ),
								'middleItem' => self::template_item( "object.textModulesData['next_visit']" ),
								'endItem'    => self::template_item( 'object.loyaltyPoints.balance' ),
							),
						),
						array(
							'oneItem' => array(
								'item' => self::template_item( "object.textModulesData['contact_help']" ),
							),
						),
					),
				),
			),
		);
		if ( ! empty( $wallet['background_color'] ) ) {
			$fields['hexBackgroundColor'] = $wallet['background_color'];
		}
		if ( ! empty( $wallet['hero_url'] ) && self::is_public_https_url( $wallet['hero_url'] ) ) {
			$fields['heroImage'] = array(
				'sourceUri'          => array( 'uri' => $wallet['hero_url'] ),
				'contentDescription' => array( 'defaultValue' => array( 'language' => 'es', 'value' => $wallet_name . ' banner' ) ),
			);
		}
		if ( $include_disabled_actions ) {
			$fields['valueAddedModuleData'] = array();
		}
		if ( ! empty( $wallet['promo_enabled'] ) && ! empty( $wallet['promo_title'] ) && self::is_public_https_url( (string) $wallet['promo_url'] ) ) {
			$promotion = array(
				'header'    => array( 'defaultValue' => array( 'language' => 'es', 'value' => $wallet['promo_title'] ) ),
				'body'      => array( 'defaultValue' => array( 'language' => 'es', 'value' => $wallet['promo_body'] ) ),
				'uri'       => $wallet['promo_url'],
				'sortIndex' => 0,
			);
			if ( ! empty( $wallet['promo_image_url'] ) && self::is_public_https_url( (string) $wallet['promo_image_url'] ) ) {
				$promotion['image'] = array( 'sourceUri' => array( 'uri' => $wallet['promo_image_url'] ) );
			}
			$fields['valueAddedModuleData'][] = $promotion;
		}
		if ( $include_disabled_actions ) {
			$fields['appLinkData'] = null;
		}
		if ( ! empty( $wallet['appointment_enabled'] ) && self::is_public_https_url( (string) $wallet['appointment_url'] ) ) {
			$fields['appLinkData'] = array(
				'webAppLinkInfo' => array(
					'appTarget' => array(
						'targetUri' => array(
							'uri'         => $wallet['appointment_url'],
							'description' => $wallet['appointment_label'],
						),
					),
				),
				'displayText' => array(
					'defaultValue' => array(
						'language' => 'es',
						'value'    => $wallet['appointment_label'],
					),
				),
			);
		}
		return $fields;
	}

	private static function template_item( string $field_path ): array {
		return array(
			'firstValue' => array(
				'fields' => array(
					array( 'fieldPath' => $field_path ),
				),
			),
		);
	}

	private static function business_text_modules( int $user_id, string $next_visit ): array {
		$modules = array(
			array( 'id' => 'next_visit', 'header' => 'Próxima visita', 'body' => $next_visit ),
		);
		$contact_help = self::contact_help( $user_id );
		if ( '' !== $contact_help ) {
			$modules[] = array( 'id' => 'contact_help', 'body' => $contact_help );
		}
		return $modules;
	}

	private static function contact_help( int $user_id ): string {
		if ( metadata_exists( 'user', $user_id, self::CONTACT_HELP_META ) ) {
			return trim( (string) get_user_meta( $user_id, self::CONTACT_HELP_META, true ) );
		}
		return 'Toca los tres puntos para llamar o escribir por WhatsApp.';
	}

	private static function business_links( int $user_id ): array {
		$links    = array();
		$website  = (string) get_user_meta( $user_id, self::WEBSITE_META, true );
		$whatsapp = preg_replace( '/\D+/', '', (string) get_user_meta( $user_id, self::WHATSAPP_META, true ) );
		if ( $website && wp_http_validate_url( $website ) ) {
			$links[] = array( 'id' => 'business_website', 'uri' => $website, 'description' => 'Website del negocio' );
		}
		if ( strlen( $whatsapp ) >= 8 && strlen( $whatsapp ) <= 15 ) {
			$links[] = array( 'id' => 'business_whatsapp', 'uri' => 'https://wa.me/' . $whatsapp, 'description' => 'WhatsApp del negocio' );
		}
		return $links;
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
		if ( self::is_public_https_url( $asset_url ) ) {
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

	private static function random_hero_url( string $seed ): string {
		$seed = sanitize_key( $seed ) ?: 'loyalty-wallet';
		return 'https://picsum.photos/seed/' . rawurlencode( $seed ) . '/1032/812.jpg';
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

	private static function valid_wallet_image( int $attachment_id ): bool {
		$path = get_attached_file( $attachment_id );
		$mime = (string) get_post_mime_type( $attachment_id );
		return wp_attachment_is_image( $attachment_id )
			&& in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true )
			&& $path
			&& is_file( $path )
			&& filesize( $path ) <= 5 * MB_IN_BYTES;
	}

	private static function upload_hero() {
		if (
			empty( $_FILES['wallet_hero_upload']['tmp_name'] )
			|| empty( $_FILES['wallet_hero_upload']['name'] )
			|| (int) ( $_FILES['wallet_hero_upload']['size'] ?? 0 ) > 5 * MB_IN_BYTES
		) {
			return new WP_Error( 'invalid_wallet_hero' );
		}
		$dimensions = wp_getimagesize( (string) $_FILES['wallet_hero_upload']['tmp_name'] );
		$mime       = (string) ( $dimensions['mime'] ?? '' );
		if (
			! $dimensions
			|| ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true )
			|| $dimensions[0] < 1
			|| $dimensions[1] < 1
			|| $dimensions[0] > 10000
			|| $dimensions[1] > 10000
		) {
			return new WP_Error( 'invalid_wallet_hero' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		return media_handle_upload(
			'wallet_hero_upload',
			0,
			array( 'post_title' => 'Google Wallet banner' ),
			array(
				'test_form' => false,
				'mimes'     => array( 'png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'webp' => 'image/webp' ),
			)
		);
	}

	private static function upload_promo_image() {
		if (
			empty( $_FILES['wallet_promo_image_upload']['tmp_name'] )
			|| empty( $_FILES['wallet_promo_image_upload']['name'] )
			|| (int) ( $_FILES['wallet_promo_image_upload']['size'] ?? 0 ) > 5 * MB_IN_BYTES
		) {
			return new WP_Error( 'invalid_wallet_promotion' );
		}
		$dimensions = wp_getimagesize( (string) $_FILES['wallet_promo_image_upload']['tmp_name'] );
		$mime       = (string) ( $dimensions['mime'] ?? '' );
		if (
			! $dimensions
			|| ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true )
			|| $dimensions[0] < 1
			|| $dimensions[1] < 1
			|| $dimensions[0] > 8000
			|| $dimensions[1] > 8000
		) {
			return new WP_Error( 'invalid_wallet_promotion' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		return media_handle_upload(
			'wallet_promo_image_upload',
			0,
			array( 'post_title' => 'Google Wallet promotion image' ),
			array(
				'test_form' => false,
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
