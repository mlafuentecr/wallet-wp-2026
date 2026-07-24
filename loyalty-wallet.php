<?php
/**
 * Plugin Name: Loyalty Wallet
 * Description: Adds a Loyalty area and a restricted client role, including a safe "See as client" preview.
 * Version: 1.0.0
 * Author: Wallet Project
 * Text Domain: loyalty-wallet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOYALTY_WALLET_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOYALTY_WALLET_URL', plugin_dir_url( __FILE__ ) );
require_once LOYALTY_WALLET_DIR . 'includes/class-google-reviews-module.php';
require_once LOYALTY_WALLET_DIR . 'includes/class-google-wallet-module.php';
require_once LOYALTY_WALLET_DIR . 'includes/class-customers-module.php';
require_once LOYALTY_WALLET_DIR . 'includes/class-google-identity-module.php';
require_once LOYALTY_WALLET_DIR . 'includes/class-engagement-module.php';
require_once LOYALTY_WALLET_DIR . 'includes/class-businesses-module.php';

final class Loyalty_Wallet_Plugin {
	private const CAPABILITY   = 'access_loyalty_wallet';
	private const ROLE         = 'loyalty_wallet_client';
	private const PREVIEW_META = '_loyalty_wallet_see_as_client';
	private const NAME_META     = '_loyalty_wallet_name';
	private const LOGO_META     = '_loyalty_wallet_logo_id';
	private const EMAIL_META    = '_loyalty_wallet_email';
	private const WEBSITE_META  = '_loyalty_wallet_website';
	private const WHATSAPP_META = '_loyalty_wallet_business_whatsapp';
	private const MENU_SLUG    = 'loyalty-wallet';

	public static function init(): void {
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		Loyalty_Wallet_Google_Wallet_Module::init();
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_menu', array( __CLASS__, 'limit_admin_menu' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'guard_admin_pages' ) );
		add_action( 'admin_post_loyalty_wallet_toggle_preview', array( __CLASS__, 'toggle_preview' ) );
		add_action( 'admin_post_loyalty_wallet_create_client', array( __CLASS__, 'create_client' ) );
		add_action( 'admin_post_loyalty_wallet_save_url', array( __CLASS__, 'save_client_url' ) );
		add_action( 'admin_post_loyalty_wallet_add_customer', array( __CLASS__, 'add_customer' ) );
		add_action( 'admin_post_loyalty_wallet_update_customer', array( __CLASS__, 'update_customer' ) );
		add_action( 'admin_post_loyalty_wallet_delete_customer', array( __CLASS__, 'delete_customer' ) );
		add_action( 'admin_post_loyalty_wallet_add_visit', array( __CLASS__, 'add_visit' ) );
		add_action( 'admin_post_loyalty_wallet_save_reminder', array( __CLASS__, 'save_reminder' ) );
		add_action( 'admin_post_loyalty_wallet_mark_reminder_sent', array( __CLASS__, 'mark_reminder_sent' ) );
		add_action( 'admin_post_loyalty_wallet_export_business_customers', array( 'Loyalty_Wallet_Businesses_Module', 'export_csv' ) );
		add_action( 'loyalty_wallet_run_engagement_reminder', array( 'Loyalty_Wallet_Engagement_Module', 'run' ), 10, 2 );
		add_action( 'admin_head', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
		add_action( 'init', array( __CLASS__, 'hide_admin_bar_for_client' ), 99 );
	}

	public static function enqueue_assets(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::MENU_SLUG === $page ) {
			if ( current_user_can( 'upload_files' ) ) {
				wp_enqueue_media();
			}
			wp_enqueue_style( 'loyalty-wallet-google-reviews', LOYALTY_WALLET_URL . 'assets/google-reviews.css', array(), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/google-reviews.css' ) );
			wp_enqueue_script( 'loyalty-wallet-google-reviews', LOYALTY_WALLET_URL . 'assets/google-reviews.js', array(), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/google-reviews.js' ), true );
		}
		if ( 'loyalty-wallet-businesses' === $page && current_user_can( 'manage_options' ) ) {
			wp_enqueue_style( 'loyalty-wallet-businesses', LOYALTY_WALLET_URL . 'assets/businesses.css', array(), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/businesses.css' ) );
			wp_enqueue_script( 'loyalty-wallet-businesses', LOYALTY_WALLET_URL . 'assets/businesses.js', array(), (string) filemtime( LOYALTY_WALLET_DIR . 'assets/businesses.js' ), true );
		}
	}

	public static function activate(): void {
		add_role(
			self::ROLE,
			'Loyalty Wallet Client',
			array(
				'read'                   => true,
				self::CAPABILITY         => true,
			)
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			$administrator->add_cap( self::CAPABILITY );
		}
	}

	public static function register_menu(): void {
		add_menu_page(
			'Loyalty Wallet',
			'Loyalty',
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-tickets-alt',
			2
		);
		add_submenu_page(
			self::MENU_SLUG,
			'Businesses',
			'Businesses',
			'manage_options',
			'loyalty-wallet-businesses',
			array( 'Loyalty_Wallet_Businesses_Module', 'render_page' )
		);
	}

	private static function is_previewing(): bool {
		return current_user_can( 'manage_options' ) && '1' === get_user_meta( get_current_user_id(), self::PREVIEW_META, true );
	}

	private static function is_client_view(): bool {
		$user = wp_get_current_user();
		return self::is_previewing() || in_array( self::ROLE, (array) $user->roles, true );
	}

	public static function admin_body_class( string $classes ): string {
		if ( self::is_client_view() ) {
			$classes .= ' loyalty-wallet-client-view';
		}
		return $classes;
	}

	public static function hide_admin_bar_for_client(): void {
		if ( self::is_client_view() ) {
			show_admin_bar( false );
		}
	}

	public static function limit_admin_menu(): void {
		if ( ! self::is_client_view() ) {
			return;
		}

		global $menu;
		$allowed = array( self::MENU_SLUG );
		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( ! in_array( $slug, $allowed, true ) ) {
				remove_menu_page( $slug );
			}
		}
	}

	public static function guard_admin_pages(): void {
		if ( ! self::is_client_view() || wp_doing_ajax() ) {
			return;
		}

		global $pagenow;
		$is_loyalty_page = 'admin.php' === $pagenow && isset( $_GET['page'] ) && self::MENU_SLUG === sanitize_key( wp_unslash( $_GET['page'] ) );
		$action          = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$client_actions  = array( 'loyalty_wallet_save_url', 'loyalty_wallet_add_customer', 'loyalty_wallet_update_customer', 'loyalty_wallet_delete_customer', 'loyalty_wallet_add_visit', 'loyalty_wallet_save_reminder', 'loyalty_wallet_mark_reminder_sent' );
		if ( self::is_previewing() ) {
			$client_actions[] = 'loyalty_wallet_toggle_preview';
		}
		$allowed_action = 'admin-post.php' === $pagenow && in_array( $action, $client_actions, true );

		if ( ! $is_loyalty_page && ! $allowed_action ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
			exit;
		}
	}

	public static function toggle_preview(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to use client preview.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_toggle_preview' );
		$enabled = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
		update_user_meta( get_current_user_id(), self::PREVIEW_META, $enabled ? '1' : '0' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	public static function save_client_url(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this code.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_save_url' );
		$section = isset( $_POST['settings_section'] ) ? sanitize_key( wp_unslash( $_POST['settings_section'] ) ) : 'qr';
		if ( 'name' === $section ) {
			$name = isset( $_POST['wallet_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wallet_name'] ) ) : '';
			if ( '' === $name ) {
				self::redirect_with_notice( 'invalid_name' );
			}
			update_user_meta( get_current_user_id(), self::NAME_META, $name );
			$wallet_email = isset( $_POST['wallet_email'] ) ? sanitize_email( wp_unslash( $_POST['wallet_email'] ) ) : '';
			if ( $wallet_email && ! is_email( $wallet_email ) ) {
				self::redirect_with_notice( 'invalid_wallet_email' );
			}
			update_user_meta( get_current_user_id(), self::EMAIL_META, $wallet_email );
			$website = isset( $_POST['wallet_website'] ) ? esc_url_raw( trim( wp_unslash( $_POST['wallet_website'] ) ) ) : '';
			if ( $website && ! wp_http_validate_url( $website ) ) {
				self::redirect_with_notice( 'invalid_business_website' );
			}
			$business_whatsapp = isset( $_POST['wallet_business_whatsapp'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['wallet_business_whatsapp'] ) ) : '';
			if ( $business_whatsapp && ( strlen( $business_whatsapp ) < 8 || strlen( $business_whatsapp ) > 15 ) ) {
				self::redirect_with_notice( 'invalid_business_whatsapp' );
			}
			update_user_meta( get_current_user_id(), self::WEBSITE_META, $website );
			update_user_meta( get_current_user_id(), self::WHATSAPP_META, $business_whatsapp );
			$media_logo_id = isset( $_POST['wallet_logo_media_id'] ) ? absint( $_POST['wallet_logo_media_id'] ) : 0;
			if ( $media_logo_id ) {
				$media_mime = (string) get_post_mime_type( $media_logo_id );
				$media_path = get_attached_file( $media_logo_id );
				if (
					! wp_attachment_is_image( $media_logo_id )
					|| ! in_array( $media_mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true )
					|| ! $media_path
					|| ! is_file( $media_path )
					|| filesize( $media_path ) > 5 * MB_IN_BYTES
				) {
					self::redirect_with_notice( 'invalid_logo' );
				}
				update_user_meta( get_current_user_id(), self::LOGO_META, $media_logo_id );
			}
			if ( ! empty( $_FILES['wallet_logo']['name'] ) ) {
				if ( (int) $_FILES['wallet_logo']['size'] > 5 * MB_IN_BYTES ) {
					self::redirect_with_notice( 'invalid_logo' );
				}
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$logo_id = media_handle_upload(
					'wallet_logo',
					0,
					array( 'post_title' => $name . ' logo' ),
					array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) )
				);
				if ( is_wp_error( $logo_id ) ) {
					self::redirect_with_notice( 'invalid_logo' );
				}
				update_user_meta( get_current_user_id(), self::LOGO_META, (int) $logo_id );
			}
			$wallet = Loyalty_Wallet_Google_Wallet_Module::data( get_current_user_id() );
			if ( $wallet['is_configured'] ) {
				Loyalty_Wallet_Google_Wallet_Module::sync_loyalty_points(
					get_current_user_id(),
					Loyalty_Wallet_Google_Reviews_Module::review_points( get_current_user_id() )
				);
			}
			self::redirect_with_notice( 'name_saved' );
		}

		$user_id = get_current_user_id();
		if ( 'reviews' === $section ) {
			$review_result = Loyalty_Wallet_Google_Reviews_Module::save( $user_id );
			if ( 'url_saved' !== $review_result ) {
				self::redirect_with_notice( $review_result, 'reviews' );
			}
			$wallet = Loyalty_Wallet_Google_Wallet_Module::data( $user_id );
			if ( $wallet['is_configured'] && ! Loyalty_Wallet_Google_Wallet_Module::sync_loyalty_points( $user_id, Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id ) ) ) {
				self::redirect_with_notice( 'wallet_points_sync_failed', 'reviews' );
			}
			self::redirect_with_notice( 'url_saved', 'reviews' );
		}

		if ( 'google_loyalty' === $section ) {
			$wallet_result = Loyalty_Wallet_Google_Wallet_Module::save( $user_id );
			if ( 'url_saved' !== $wallet_result ) {
				self::redirect_with_notice( $wallet_result, 'google-loyalty' );
			}
			$wallet = Loyalty_Wallet_Google_Wallet_Module::data( $user_id );
			if ( $wallet['is_configured'] && ! Loyalty_Wallet_Google_Wallet_Module::sync_loyalty_points( $user_id, Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id ) ) ) {
				self::redirect_with_notice( 'wallet_points_sync_failed', 'google-loyalty' );
			}
			self::redirect_with_notice( 'url_saved', 'google-loyalty' );
		}

		self::redirect_with_notice( 'invalid' );
	}

	public static function add_customer(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to add customers.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_add_customer' );
		self::redirect_with_notice( Loyalty_Wallet_Customers_Module::add( get_current_user_id() ) );
	}

	public static function update_customer(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to edit customers.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_update_customer' );
		self::redirect_with_notice( Loyalty_Wallet_Customers_Module::update( get_current_user_id() ) );
	}

	public static function delete_customer(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to delete customers.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_delete_customer', '_wpnonce_delete' );
		self::redirect_with_notice( Loyalty_Wallet_Customers_Module::delete( get_current_user_id() ) );
	}

	public static function add_visit(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) wp_die( esc_html__( 'You do not have permission to add visits.', 'loyalty-wallet' ) );
		check_admin_referer( 'loyalty_wallet_add_visit' );
		self::redirect_with_notice( Loyalty_Wallet_Customers_Module::add_visit( get_current_user_id() ) );
	}

	public static function save_reminder(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) wp_die( esc_html__( 'You do not have permission to schedule reminders.', 'loyalty-wallet' ) );
		check_admin_referer( 'loyalty_wallet_save_reminder' );
		self::redirect_with_notice( Loyalty_Wallet_Engagement_Module::schedule( get_current_user_id() ) );
	}

	public static function mark_reminder_sent(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) wp_die( esc_html__( 'You do not have permission to update reminders.', 'loyalty-wallet' ) );
		check_admin_referer( 'loyalty_wallet_mark_reminder_sent' );
		self::redirect_with_notice( Loyalty_Wallet_Engagement_Module::mark_sent( get_current_user_id() ) );
	}

	public static function create_client(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to create clients.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_create_client' );
		$username = isset( $_POST['client_username'] ) ? sanitize_user( wp_unslash( $_POST['client_username'] ), true ) : '';
		$email    = isset( $_POST['client_email'] ) ? sanitize_email( wp_unslash( $_POST['client_email'] ) ) : '';

		if ( ! $username || ! is_email( $email ) ) {
			self::redirect_with_notice( 'invalid', 'loyalty' );
		}
		if ( username_exists( $username ) || email_exists( $email ) ) {
			self::redirect_with_notice( 'exists', 'loyalty' );
		}

		$password = wp_generate_password( 16, true );
		$user_id  = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			self::redirect_with_notice( 'error', 'loyalty' );
		}

		$user = new WP_User( $user_id );
		$user->set_role( self::ROLE );
		set_transient( 'loyalty_wallet_password_' . get_current_user_id(), $password, 120 );
		self::redirect_with_notice( 'created', 'loyalty' );
	}

	private static function redirect_with_notice( string $notice, string $tab = '' ): void {
		$args = array( 'lw_notice' => $notice );
		if ( $tab ) {
			$args['lw_tab'] = $tab;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	public static function render_page(): void {
		$is_admin   = current_user_can( 'manage_options' );
		$is_preview = self::is_previewing();
		$password   = $is_admin ? get_transient( 'loyalty_wallet_password_' . get_current_user_id() ) : false;
		$google = Loyalty_Wallet_Google_Reviews_Module::data( get_current_user_id() );
		$google = Loyalty_Wallet_Google_Wallet_Module::augment_review_data( get_current_user_id(), $google );
		$customers = Loyalty_Wallet_Customers_Module::all( get_current_user_id() );
		$wallet_name = (string) get_user_meta( get_current_user_id(), self::NAME_META, true );
		$wallet_name = $wallet_name ?: ( $is_preview || ! $is_admin ? 'My Loyalty Wallet' : 'Loyalty Wallet' );
		$logo_id     = absint( get_user_meta( get_current_user_id(), self::LOGO_META, true ) );
		$logo_url    = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$wallet_email = (string) get_user_meta( get_current_user_id(), self::EMAIL_META, true );
		$wallet_website = (string) get_user_meta( get_current_user_id(), self::WEBSITE_META, true );
		$wallet_business_whatsapp = (string) get_user_meta( get_current_user_id(), self::WHATSAPP_META, true );
		if ( $password ) {
			delete_transient( 'loyalty_wallet_password_' . get_current_user_id() );
		}
		?>
		<div class="wrap loyalty-wallet-wrap">
			<div class="lw-header">
				<div class="lw-header-identity">
					<div class="lw-header-logo <?php echo $logo_url ? 'has-logo' : ''; ?>">
						<?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $wallet_name ); ?> logo"><?php else : ?><span class="dashicons dashicons-store" aria-hidden="true"></span><?php endif; ?>
					</div>
					<div>
					<span class="lw-eyebrow">LOYALTY WALLET</span>
					<h1><?php echo esc_html( $wallet_name ); ?></h1>
					</div>
				</div>
				<?php if ( $is_admin ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-preview-form">
						<input type="hidden" name="action" value="loyalty_wallet_toggle_preview">
						<input type="hidden" name="enabled" value="<?php echo $is_preview ? '0' : '1'; ?>">
						<?php wp_nonce_field( 'loyalty_wallet_toggle_preview' ); ?>
						<label class="lw-switch-label">
							<span><strong>See as client</strong><small><?php echo $is_preview ? 'Client view is active' : 'Preview the restricted experience'; ?></small></span>
							<button type="submit" class="lw-switch <?php echo $is_preview ? 'is-on' : ''; ?>" role="switch" aria-checked="<?php echo $is_preview ? 'true' : 'false'; ?>"><span></span></button>
						</label>
					</form>
				<?php endif; ?>
			</div>

			<?php self::render_notice( $password ); ?>
			<?php Loyalty_Wallet_Google_Reviews_Module::render_preview( $google ); ?>
			<?php Loyalty_Wallet_Engagement_Module::render_alerts( get_current_user_id() ); ?>

			<div class="lw-grid lw-qr-grid">
				<div class="lw-card lw-balance lw-tabs-card">
					<span class="lw-tabs-label">LOYALTY TOOLS</span>
					<div class="lw-vertical-tabs" role="tablist" aria-label="Loyalty tools">
						<button type="button" class="lw-nav-tab" id="lw-customers-tab" role="tab" aria-selected="false" aria-controls="lw-customers-panel"><span class="dashicons dashicons-groups"></span><span><strong>Customers</strong><small><?php echo esc_html( count( $customers ) ); ?> saved</small></span><b aria-hidden="true">→</b></button>
						<button type="button" class="lw-nav-tab" id="lw-activity-tab" role="tab" aria-selected="false" aria-controls="lw-activity-panel"><span class="dashicons dashicons-chart-bar"></span><span><strong>Activity</strong><small>Annual visits</small></span><b aria-hidden="true">→</b></button>
						<?php if ( $is_admin && ! $is_preview ) : ?>
							<button type="button" class="lw-nav-tab lw-configuration-tab is-active" id="lw-configuration-tab" role="tab" aria-selected="true" aria-controls="lw-code-editor"><span class="dashicons dashicons-admin-generic"></span><span><strong>Configuration</strong><small>Business, Google and access</small></span><b aria-hidden="true">⌄</b></button>
							<div class="lw-configuration-nav" aria-label="Configuration sections">
								<button type="button" class="lw-subnav-tab is-active" id="lw-name-tab" role="tab" aria-selected="true" aria-controls="lw-name-settings"><span class="dashicons dashicons-store"></span><span><strong>Negocio</strong><small>Nombre, contacto y logo</small></span></button>
								<button type="button" class="lw-subnav-tab" id="lw-review-tab" role="tab" aria-selected="false" aria-controls="lw-qr-settings"><span class="dashicons dashicons-star-filled"></span><span><strong>Google Reviews</strong><small>Reviews, QR and credentials</small></span></button>
								<button type="button" class="lw-subnav-tab" id="lw-google-loyalty-tab" role="tab" aria-selected="false" aria-controls="lw-google-loyalty-settings"><span class="dashicons dashicons-tickets-alt"></span><span><strong>Google Loyalty</strong><small>Wallet cards and issuer</small></span></button>
								<button type="button" class="lw-subnav-tab" id="lw-loyalty-tab" role="tab" aria-selected="false" aria-controls="lw-loyalty-settings"><span class="dashicons dashicons-admin-users"></span><span><strong>Client Access</strong><small>Restricted WordPress user</small></span></button>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="lw-card lw-code-editor" id="lw-code-editor">
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="loyalty_wallet_save_url">
						<input type="hidden" id="lw-settings-section" name="settings_section" value="name">
						<?php wp_nonce_field( 'loyalty_wallet_save_url' ); ?>
						<?php Loyalty_Wallet_Google_Reviews_Module::render_settings( $google ); ?>
						<?php Loyalty_Wallet_Google_Wallet_Module::render_settings( get_current_user_id() ); ?>
						<div id="lw-name-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-name-tab">
							<div class="lw-settings-heading"><h2>Negocio</h2><p>Edita el nombre, información de contacto y logo que se muestran en Loyalty Wallet.</p></div>
							<div class="lw-wallet-identity-fields">
								<label for="lw-wallet-name">Wallet / business name<input id="lw-wallet-name" name="wallet_name" type="text" value="<?php echo esc_attr( $wallet_name ); ?>" placeholder="Croc's Resort & Casino" maxlength="120" required></label>
								<label for="lw-wallet-email">Wallet notification email<input id="lw-wallet-email" name="wallet_email" type="email" value="<?php echo esc_attr( $wallet_email ); ?>" placeholder="owner@example.com"></label>
								<label for="lw-wallet-website">Business website<input id="lw-wallet-website" name="wallet_website" type="url" value="<?php echo esc_attr( $wallet_website ); ?>" placeholder="https://example.com"></label>
								<label for="lw-wallet-business-whatsapp">Business WhatsApp<input id="lw-wallet-business-whatsapp" name="wallet_business_whatsapp" type="tel" value="<?php echo esc_attr( $wallet_business_whatsapp ); ?>" placeholder="+506 8888 8888"><small>Include the country code.</small></label>
							</div>
							<label for="lw-wallet-logo">Business logo</label>
							<div class="lw-logo-upload">
								<div class="lw-logo-preview" id="lw-logo-preview"><?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="Current business logo"><?php else : ?><span class="dashicons dashicons-format-image"></span><?php endif; ?></div>
								<div>
									<input id="lw-business-logo-media-id" name="wallet_logo_media_id" type="hidden" value="">
									<input id="lw-wallet-logo" name="wallet_logo" type="file" accept="image/jpeg,image/png,image/webp" hidden>
									<div class="lw-logo-actions">
										<label class="button lw-wallet-upload-button" for="lw-wallet-logo"><span class="dashicons dashicons-upload"></span> Upload logo</label>
										<button type="button" class="button lw-media-library-button" data-media-target="lw-business-logo-media-id" data-preview-target="lw-logo-preview" data-file-target="lw-wallet-logo"><span class="dashicons dashicons-admin-media"></span> Add from Media Library</button>
									</div>
									<small>JPG, PNG or WebP. Maximum 5 MB.</small>
								</div>
							</div>
						</div>
						<?php submit_button( 'Save changes', 'primary', 'submit', false ); ?>
					</form>
					<?php if ( $is_admin && ! $is_preview ) : ?>
						<div id="lw-loyalty-settings" class="lw-settings-panel lw-loyalty-admin-settings" role="tabpanel" aria-labelledby="lw-loyalty-tab" hidden>
							<div class="lw-settings-heading">
								<h2>Client access</h2>
								<p>Manage the restricted WordPress access used by your Loyalty client.</p>
							</div>
							<section class="lw-client-access-card">
								<div class="lw-client-access-heading">
									<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
									<div><h3>Create a Loyalty client</h3><p>The new account will only have access to the Loyalty tab in WordPress.</p></div>
								</div>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-create-client-form">
									<input type="hidden" name="action" value="loyalty_wallet_create_client">
									<?php wp_nonce_field( 'loyalty_wallet_create_client' ); ?>
									<label>Username<input name="client_username" type="text" autocomplete="username" required></label>
									<label>Email<input name="client_email" type="email" autocomplete="email" required></label>
									<?php submit_button( 'Create client account', 'primary', 'create_client_submit', false ); ?>
								</form>
							</section>
						</div>
					<?php endif; ?>
					<?php Loyalty_Wallet_Customers_Module::render( $customers ); ?>
					<?php Loyalty_Wallet_Customers_Module::render_activity( $customers ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_notice( $password ): void {
		$notice = isset( $_GET['lw_notice'] ) ? sanitize_key( wp_unslash( $_GET['lw_notice'] ) ) : '';
		$messages = array(
			'invalid' => array( 'error', 'Enter a valid username and email.' ),
			'exists'  => array( 'error', 'That username or email is already in use.' ),
			'error'   => array( 'error', 'The client account could not be created.' ),
			'created' => array( 'success', 'Client created successfully.' ),
			'invalid_url' => array( 'error', 'Enter a valid public URL.' ),
			'invalid_place_id' => array( 'error', 'Enter a valid Google Place ID.' ),
			'invalid_name'     => array( 'error', 'Enter a wallet or business name.' ),
			'invalid_logo'     => array( 'error', 'Choose a valid JPG, PNG or WebP logo under 5 MB.' ),
			'invalid_wallet_email' => array( 'error', 'Enter a valid wallet notification email.' ),
			'invalid_business_website' => array( 'error', 'Enter a valid business website URL.' ),
			'invalid_business_whatsapp' => array( 'error', 'Enter a valid business WhatsApp number including the country code.' ),
			'invalid_wallet_settings' => array( 'error', 'Complete the Google Wallet Issuer ID, class suffix, service account email and public URLs.' ),
			'invalid_wallet_private_key' => array( 'error', 'Enter a valid Google service account private key.' ),
			'invalid_wallet_service_account_json' => array( 'error', 'Upload a valid Google service account credentials JSON file no larger than 1 MB.' ),
			'invalid_wallet_logo' => array( 'error', 'Upload a valid PNG, JPG or WebP image no larger than 5 MB.' ),
			'invalid_wallet_hero' => array( 'error', 'Upload a valid PNG, JPG or WebP banner no larger than 5 MB.' ),
			'invalid_wallet_design' => array( 'error', 'Choose a valid card color and a public HTTPS banner URL.' ),
			'invalid_reminder' => array( 'error', 'Complete the reminder date, channel and message.' ),
			'missing_wallet_email' => array( 'error', 'Add a Wallet notification email before scheduling WhatsApp reminders.' ),
			'missing_birthday' => array( 'error', 'Add the customer birthday before enabling birthday reminders.' ),
			'reminder_saved' => array( 'success', 'Reminder scheduled.' ),
			'name_saved'       => array( 'success', 'Wallet name updated successfully.' ),
			'url_saved'   => array( 'success', 'Client QR code updated successfully.' ),
			'invalid_customer' => array( 'error', 'Enter a valid customer name, email and review.' ),
			'customer_updated' => array( 'success', 'Customer updated successfully.' ),
			'wallet_points_sync_failed' => array( 'error', 'Settings were saved, but existing Google Wallet cards could not be updated.' ),
		);
		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}
		if ( 'wallet_points_sync_failed' === $notice ) {
			$sync_error = sanitize_text_field( (string) get_user_meta( get_current_user_id(), '_loyalty_wallet_google_wallet_sync_error', true ) );
			if ( $sync_error ) {
				$messages[ $notice ][1] .= ' Google response: ' . $sync_error;
			}
		}
		printf( '<div class="notice notice-%1$s inline"><p><strong>%2$s</strong>%3$s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ), $password ? ' Temporary password: <code>' . esc_html( $password ) . '</code> (copy it now)' : '' );
	}

	public static function admin_styles(): void {
		if ( ! isset( $_GET['page'] ) || self::MENU_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}
		?>
		<style>
		.loyalty-wallet-wrap{max-width:1180px;margin:28px 28px 0 8px;color:#172033}.lw-header{display:flex;justify-content:space-between;gap:24px;align-items:center;background:linear-gradient(135deg,#111827,#27354d);color:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 30px #17203322}.lw-header h1{color:#fff;font-size:32px;margin:6px 0}.lw-header p{margin:0;color:#cbd5e1;font-size:15px}.lw-eyebrow{font-size:11px;letter-spacing:2px;color:#7dd3fc;font-weight:700}.lw-preview-form{min-width:300px}.lw-switch-label{display:flex;justify-content:space-between;align-items:center;gap:24px;background:#ffffff12;border:1px solid #ffffff25;border-radius:14px;padding:16px}.lw-switch-label small{display:block;color:#cbd5e1;margin-top:3px}.lw-switch{position:relative;width:50px;height:28px;border:0;border-radius:20px;background:#64748b;cursor:pointer;padding:3px}.lw-switch span{display:block;width:22px;height:22px;border-radius:50%;background:#fff;transition:.2s}.lw-switch.is-on{background:#22c55e}.lw-switch.is-on span{transform:translateX(22px)}.lw-grid{display:grid;grid-template-columns:1.05fr 1.25fr 1fr;gap:18px;margin-top:20px}.lw-card{background:#fff;border:1px solid #dbe2ea;border-radius:16px;padding:24px;box-shadow:0 5px 16px #1720330d}.lw-card h2{margin:0 0 8px}.lw-card>p{margin-top:0;color:#64748b}.lw-balance{background:linear-gradient(145deg,#2563eb,#4f46e5);color:#fff;border:0}.lw-tabs-card{padding:20px;min-height:300px}.lw-tabs-label{display:block;margin:2px 4px 14px;font-size:11px;letter-spacing:1.5px}.lw-vertical-tabs{display:grid;gap:8px}.lw-nav-tab{display:grid;grid-template-columns:24px 1fr auto;gap:11px;align-items:center;width:100%;padding:13px;border:1px solid transparent;border-radius:11px;background:transparent;color:#dbeafe;text-align:left;cursor:pointer;font:inherit}.lw-nav-tab .dashicons{font-size:20px}.lw-nav-tab strong,.lw-nav-tab small{display:block}.lw-nav-tab strong{color:#fff;font-size:14px}.lw-nav-tab small{margin-top:2px;color:#bfdbfe;font-size:11px}.lw-nav-tab b{font-size:20px}.lw-nav-tab.is-active{background:#ffffff1c;border-color:#ffffff30;box-shadow:0 5px 12px #1e1b4b25}.lw-nav-tab.is-active:hover{background:#ffffff29}.lw-nav-tab:disabled{cursor:default;opacity:.55}.lw-code-editor form{display:grid;gap:9px}.lw-code-editor label{font-weight:600}.lw-code-editor input[type=url],.lw-code-editor input[type=text]{width:100%;min-height:42px;border-radius:8px}.lw-help-link{width:max-content;font-size:12px;text-decoration:none}.lw-code-editor .submit{width:max-content;margin-top:4px}.lw-qr-preview{text-align:center}.lw-qr-preview h2{text-align:left}.lw-qr-frame{display:grid;place-items:center;min-height:180px;margin:14px auto 10px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px}.lw-qr-frame img{display:block;width:170px;height:170px}.lw-qr-frame p{color:#64748b;max-width:180px}.lw-qr-preview small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b}.lw-admin-panel{margin-top:20px}.lw-admin-panel form{display:flex;align-items:end;gap:14px;flex-wrap:wrap}.lw-admin-panel label{font-weight:600}.lw-admin-panel input{display:block;margin-top:6px;min-width:240px}.notice.inline{margin:18px 0 0}.folded .lw-preview-form{min-width:260px}@media(max-width:900px){.lw-header{align-items:stretch;flex-direction:column}.lw-preview-form{min-width:0}.lw-grid{grid-template-columns:1fr}.lw-tabs-card{min-height:0}}
		.lw-header-identity{display:flex;align-items:center;gap:20px;min-width:0}.lw-header-logo{display:grid;place-items:center;flex:0 0 82px;width:82px;height:82px;overflow:hidden;background:#ffffff14;border:1px solid #ffffff25;border-radius:14px;color:#7dd3fc}.lw-header-logo .dashicons{width:36px;height:36px;font-size:36px}.lw-header-logo.has-logo{background:#fff;border:0}.lw-header-logo img{display:block;width:100%;height:100%;object-fit:contain}@media(max-width:600px){.lw-header-identity{align-items:flex-start}.lw-header-logo{width:64px;height:64px;flex-basis:64px}.lw-header h1{font-size:26px}}
		<?php if ( self::is_client_view() ) : ?>
		html.wp-toolbar{padding-top:0!important}#wpadminbar{display:none!important}#wpwrap{top:0!important}#adminmenuback,#adminmenuwrap{top:0!important}
		<?php endif; ?>
		</style>
		<?php
	}
}

Loyalty_Wallet_Plugin::init();
