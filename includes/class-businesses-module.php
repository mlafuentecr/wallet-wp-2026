<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Businesses_Module {
	private const NAME_META      = '_loyalty_wallet_name';
	private const EMAIL_META     = '_loyalty_wallet_email';
	private const LOGO_META      = '_loyalty_wallet_logo_id';
	private const WEBSITE_META   = '_loyalty_wallet_website';
	private const WHATSAPP_META  = '_loyalty_wallet_business_whatsapp';
	private const CUSTOMERS_META = '_loyalty_wallet_review_customers';
	private const CLIENT_ROLE    = 'loyalty_wallet_client';
	private const PASSWORD_TRANSIENT_PREFIX = 'loyalty_wallet_business_password_';

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver los negocios.', 'loyalty-wallet' ) );
		}

		$businesses           = self::all();
		$selected_business_id = isset( $_GET['business_id'] ) ? absint( $_GET['business_id'] ) : 0;
		$selected_business    = null;
		$notice               = isset( $_GET['lw_business_notice'] ) ? sanitize_key( wp_unslash( $_GET['lw_business_notice'] ) ) : '';
		$temporary_access     = get_transient( self::PASSWORD_TRANSIENT_PREFIX . get_current_user_id() );
		if ( $temporary_access ) {
			delete_transient( self::PASSWORD_TRANSIENT_PREFIX . get_current_user_id() );
		}

		foreach ( $businesses as $business ) {
			if ( $selected_business_id === $business['id'] ) {
				$selected_business = $business;
				break;
			}
		}

		require LOYALTY_WALLET_DIR . 'views/businesses-page.php';
	}

	public static function create_business(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para crear negocios.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_create_business' );

		$business_name = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
		$owner_name    = isset( $_POST['owner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['owner_name'] ) ) : '';
		$username      = isset( $_POST['owner_username'] ) ? sanitize_user( wp_unslash( $_POST['owner_username'] ), true ) : '';
		$owner_email   = isset( $_POST['owner_email'] ) ? sanitize_email( wp_unslash( $_POST['owner_email'] ) ) : '';
		$business_email = isset( $_POST['business_email'] ) ? sanitize_email( wp_unslash( $_POST['business_email'] ) ) : '';
		$website       = isset( $_POST['business_website'] ) ? esc_url_raw( trim( wp_unslash( $_POST['business_website'] ) ) ) : '';
		$whatsapp      = isset( $_POST['business_whatsapp'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['business_whatsapp'] ) ) : '';

		if ( ! $business_name || ! $owner_name || ! $username || ! is_email( $owner_email ) ) {
			self::redirect_with_notice( 'datos_invalidos' );
		}
		if ( $business_email && ! is_email( $business_email ) ) {
			self::redirect_with_notice( 'datos_invalidos' );
		}
		if ( $website && ! wp_http_validate_url( $website ) ) {
			self::redirect_with_notice( 'datos_invalidos' );
		}
		if ( $whatsapp && ( strlen( $whatsapp ) < 8 || strlen( $whatsapp ) > 15 ) ) {
			self::redirect_with_notice( 'datos_invalidos' );
		}
		if ( username_exists( $username ) || email_exists( $owner_email ) ) {
			self::redirect_with_notice( 'usuario_existente' );
		}

		$password = wp_generate_password( 18, true );
		$user_id  = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $owner_email,
				'user_pass'    => $password,
				'display_name' => $owner_name,
				'first_name'   => $owner_name,
				'role'         => self::CLIENT_ROLE,
			)
		);
		if ( is_wp_error( $user_id ) ) {
			self::redirect_with_notice( 'error_creacion' );
		}

		update_user_meta( $user_id, self::NAME_META, $business_name );
		update_user_meta( $user_id, self::EMAIL_META, $business_email ?: $owner_email );
		update_user_meta( $user_id, self::WEBSITE_META, $website );
		update_user_meta( $user_id, self::WHATSAPP_META, $whatsapp );
		set_transient(
			self::PASSWORD_TRANSIENT_PREFIX . get_current_user_id(),
			array(
				'username' => $username,
				'password' => $password,
				'name'     => $business_name,
			),
			5 * MINUTE_IN_SECONDS
		);

		wp_new_user_notification( $user_id, null, 'user' );
		self::redirect_with_notice( 'negocio_creado', (int) $user_id );
	}

	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para exportar clientes.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_export_business_customers' );
		$business_id = isset( $_GET['business_id'] ) ? absint( $_GET['business_id'] ) : 0;
		$businesses  = self::all();

		if ( $business_id ) {
			$businesses = array_values(
				array_filter(
					$businesses,
					static fn( array $business ): bool => $business_id === $business['id']
				)
			);
		}

		if ( ! $businesses ) {
			wp_die( esc_html__( 'No se encontró el negocio.', 'loyalty-wallet' ) );
		}

		$filename = $business_id
			? sanitize_file_name( $businesses[0]['name'] . '-customers-' . current_time( 'Y-m-d' ) . '.csv' )
			: 'all-business-customers-' . current_time( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'No se pudo crear la exportación.', 'loyalty-wallet' ) );
		}

		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv(
			$output,
			array(
				'ID del negocio',
				'Nombre del negocio',
				'Correo del negocio',
				'Sitio web',
				'WhatsApp del negocio',
				'Propietario en WordPress',
				'ID del cliente',
				'Nombre del cliente',
				'Correo del cliente',
				'Teléfono del cliente',
				'Preferencia de contacto',
				'Calificación',
				'Reseña',
				'Puntos',
				'Cumpleaños',
				'Última visita',
				'Próxima visita',
				'Total de visitas',
				'Origen',
				'ID de miembro de Google Wallet',
			)
		);

		foreach ( $businesses as $business ) {
			foreach ( $business['customers'] as $customer ) {
				$visits = isset( $customer['visits'] ) && is_array( $customer['visits'] )
					? $customer['visits']
					: array_filter( array( $customer['date'] ?? '' ) );
				fputcsv(
					$output,
					array_map(
						array( __CLASS__, 'csv_value' ),
						array(
							$business['id'],
							$business['name'],
							$business['email'],
							$business['website'],
							$business['whatsapp'],
							$business['owner_email'],
							$customer['id'] ?? '',
							$customer['name'] ?? '',
							$customer['email'] ?? '',
							$customer['phone'] ?? '',
							$customer['contact_preference'] ?? '',
							$customer['rating'] ?? 0,
							$customer['review'] ?? '',
							$customer['points'] ?? 0,
							$customer['birthday'] ?? '',
							$customer['date'] ?? '',
							$customer['next_visit'] ?? '',
							count( $visits ),
							$customer['source'] ?? '',
							$customer['wallet_member_id'] ?? '',
						)
					)
				);
			}
		}

		fclose( $output );
		exit;
	}

	public static function all(): array {
		$businesses = array();
		$users      = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );

		foreach ( $users as $user ) {
			$name      = trim( (string) get_user_meta( $user->ID, self::NAME_META, true ) );
			$customers = get_user_meta( $user->ID, self::CUSTOMERS_META, true );
			$customers = is_array( $customers ) ? array_values( $customers ) : array();
			$is_client = in_array( self::CLIENT_ROLE, (array) $user->roles, true );

			if ( ! $is_client && '' === $name && ! $customers ) {
				continue;
			}

			$logo_id = absint( get_user_meta( $user->ID, self::LOGO_META, true ) );
			$businesses[] = array(
				'id'            => (int) $user->ID,
				'name'          => $name ?: $user->display_name,
				'email'         => (string) get_user_meta( $user->ID, self::EMAIL_META, true ) ?: $user->user_email,
				'website'       => (string) get_user_meta( $user->ID, self::WEBSITE_META, true ),
				'whatsapp'      => (string) get_user_meta( $user->ID, self::WHATSAPP_META, true ),
				'owner_name'    => $user->display_name,
				'owner_email'   => $user->user_email,
				'logo_url'      => $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '',
				'customers'     => $customers,
				'customer_count'=> count( $customers ),
				'total_points'  => array_sum( array_map( static fn( array $customer ): int => absint( $customer['points'] ?? 0 ), $customers ) ),
				'total_visits'  => array_sum(
					array_map(
						static fn( array $customer ): int => count(
							isset( $customer['visits'] ) && is_array( $customer['visits'] )
								? $customer['visits']
								: array_filter( array( $customer['date'] ?? '' ) )
						),
						$customers
					)
				),
			);
		}

		return $businesses;
	}

	private static function csv_value( $value ): string {
		$value = (string) $value;
		return preg_match( '/^[=\-+@\t\r]/', $value ) ? "'" . $value : $value;
	}

	private static function redirect_with_notice( string $notice, int $business_id = 0 ): void {
		$args = array(
			'page'               => 'loyalty-wallet-businesses',
			'lw_business_notice' => $notice,
		);
		if ( $business_id ) {
			$args['business_id'] = $business_id;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
