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
	private const MANAGER_PHONE_META = '_loyalty_wallet_manager_phone';
	private const STARTED_AT_META    = '_loyalty_wallet_business_started_at';
	private const NEXT_PAYMENT_META  = '_loyalty_wallet_business_next_payment';
	private const STATUS_META        = '_loyalty_wallet_business_status';
	private const PLAN_META          = '_loyalty_wallet_business_plan';
	private const MONTHLY_PRICE_META = '_loyalty_wallet_business_monthly_price';
	private const LAST_PAYMENT_META  = '_loyalty_wallet_business_last_payment';
	private const CUSTOMERS_META = '_loyalty_wallet_review_customers';
	private const CLIENT_ROLE    = 'loyalty_wallet_client';
	private const PASSWORD_TRANSIENT_PREFIX = 'loyalty_wallet_business_password_';

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver los negocios.', 'loyalty-wallet' ) );
		}

		$businesses           = self::all();
		$notice               = isset( $_GET['lw_business_notice'] ) ? sanitize_key( wp_unslash( $_GET['lw_business_notice'] ) ) : '';
		$temporary_access     = get_transient( self::PASSWORD_TRANSIENT_PREFIX . get_current_user_id() );
		if ( $temporary_access ) {
			delete_transient( self::PASSWORD_TRANSIENT_PREFIX . get_current_user_id() );
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
		$manager_phone = isset( $_POST['manager_phone'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['manager_phone'] ) ) : '';
		$business_email = isset( $_POST['business_email'] ) ? sanitize_email( wp_unslash( $_POST['business_email'] ) ) : '';
		$website       = isset( $_POST['business_website'] ) ? esc_url_raw( trim( wp_unslash( $_POST['business_website'] ) ) ) : '';
		$whatsapp      = isset( $_POST['business_whatsapp'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['business_whatsapp'] ) ) : '';
		$started_at    = isset( $_POST['business_started_at'] ) ? sanitize_text_field( wp_unslash( $_POST['business_started_at'] ) ) : '';
		$next_payment  = isset( $_POST['business_next_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['business_next_payment'] ) ) : '';
		$plan          = isset( $_POST['business_plan'] ) ? sanitize_key( wp_unslash( $_POST['business_plan'] ) ) : '';
		$monthly_price = isset( $_POST['business_monthly_price'] ) ? (float) wp_unslash( $_POST['business_monthly_price'] ) : 0.0;
		$last_payment  = isset( $_POST['business_last_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['business_last_payment'] ) ) : '';

		if ( ! $business_name || ! $owner_name || ! $username || ! is_email( $owner_email ) || ! self::valid_phone( $manager_phone ) ) {
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
		if ( ! self::valid_date( $started_at ) || ! self::valid_date( $next_payment ) || ( $last_payment && ! self::valid_date( $last_payment ) ) || ! self::valid_plan( $plan ) || $monthly_price < 0 || $monthly_price > 1000000 ) {
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
		update_user_meta( $user_id, self::MANAGER_PHONE_META, $manager_phone );
		update_user_meta( $user_id, self::STARTED_AT_META, $started_at );
		update_user_meta( $user_id, self::NEXT_PAYMENT_META, $next_payment );
		update_user_meta( $user_id, self::STATUS_META, 'active' );
		update_user_meta( $user_id, self::PLAN_META, $plan );
		update_user_meta( $user_id, self::MONTHLY_PRICE_META, number_format( $monthly_price, 2, '.', '' ) );
		update_user_meta( $user_id, self::LAST_PAYMENT_META, $last_payment );
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

	public static function update_status(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para cambiar el estado del negocio.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_update_business_status' );
		$business_id = isset( $_POST['business_id'] ) ? absint( $_POST['business_id'] ) : 0;
		$status      = isset( $_POST['business_status'] ) ? sanitize_key( wp_unslash( $_POST['business_status'] ) ) : '';
		$user        = $business_id ? get_userdata( $business_id ) : false;

		$is_business = $user && (
			in_array( self::CLIENT_ROLE, (array) $user->roles, true )
			|| '' !== trim( (string) get_user_meta( $business_id, self::NAME_META, true ) )
		);
		if ( ! $is_business || ! in_array( $status, array( 'active', 'suspended', 'archived' ), true ) ) {
			self::redirect_with_notice( 'estado_invalido' );
		}

		update_user_meta( $business_id, self::STATUS_META, $status );
		$notices = array(
			'active'    => 'negocio_activado',
			'suspended' => 'negocio_suspendido',
			'archived'  => 'negocio_archivado',
		);
		self::redirect_with_notice( $notices[ $status ] );
	}

	public static function update_business(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para editar negocios.', 'loyalty-wallet' ) );
		}

		check_admin_referer( 'loyalty_wallet_update_business' );
		$business_id  = isset( $_POST['business_id'] ) ? absint( $_POST['business_id'] ) : 0;
		$business_name = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
		$owner_name   = isset( $_POST['owner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['owner_name'] ) ) : '';
		$manager_phone = isset( $_POST['manager_phone'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['manager_phone'] ) ) : '';
		$started_at   = isset( $_POST['business_started_at'] ) ? sanitize_text_field( wp_unslash( $_POST['business_started_at'] ) ) : '';
		$next_payment = isset( $_POST['business_next_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['business_next_payment'] ) ) : '';
		$plan         = isset( $_POST['business_plan'] ) ? sanitize_key( wp_unslash( $_POST['business_plan'] ) ) : '';
		$monthly_price = isset( $_POST['business_monthly_price'] ) ? (float) wp_unslash( $_POST['business_monthly_price'] ) : 0.0;
		$last_payment = isset( $_POST['business_last_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['business_last_payment'] ) ) : '';
		$user         = $business_id ? get_userdata( $business_id ) : false;
		$is_business  = $user && (
			in_array( self::CLIENT_ROLE, (array) $user->roles, true )
			|| '' !== trim( (string) get_user_meta( $business_id, self::NAME_META, true ) )
		);

		if ( ! $is_business || ! $business_name || ! $owner_name || ! self::valid_phone( $manager_phone ) || ! self::valid_date( $started_at ) || ! self::valid_date( $next_payment ) || ( $last_payment && ! self::valid_date( $last_payment ) ) || ! self::valid_plan( $plan ) || $monthly_price < 0 || $monthly_price > 1000000 ) {
			self::redirect_with_notice( 'datos_invalidos' );
		}

		wp_update_user( array( 'ID' => $business_id, 'display_name' => $owner_name ) );
		update_user_meta( $business_id, self::NAME_META, $business_name );
		update_user_meta( $business_id, self::MANAGER_PHONE_META, $manager_phone );
		update_user_meta( $business_id, self::STARTED_AT_META, $started_at );
		update_user_meta( $business_id, self::NEXT_PAYMENT_META, $next_payment );
		update_user_meta( $business_id, self::PLAN_META, $plan );
		update_user_meta( $business_id, self::MONTHLY_PRICE_META, number_format( $monthly_price, 2, '.', '' ) );
		update_user_meta( $business_id, self::LAST_PAYMENT_META, $last_payment );
		self::redirect_with_notice( 'negocio_actualizado' );
	}

	public static function export_accounts_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para exportar negocios.', 'loyalty-wallet' ) );
		}
		check_admin_referer( 'loyalty_wallet_export_business_accounts' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="negocios-loyalty-wallet-' . current_time( 'Y-m-d' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'No se pudo crear la exportación.', 'loyalty-wallet' ) );
		}
		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv( $output, array( 'Negocio', 'Encargado', 'Correo', 'Teléfono', 'Plan', 'Precio mensual', 'Inicio', 'Siguiente pago', 'Último pago', 'Estado', 'Wallet' ) );
		foreach ( self::all() as $business ) {
			fputcsv(
				$output,
				array_map(
					array( __CLASS__, 'csv_value' ),
					array(
						$business['name'],
						$business['owner_name'],
						$business['owner_email'],
						$business['manager_phone'],
						$business['plan_label'],
						number_format( $business['monthly_price'], 2, '.', '' ),
						$business['started_at'],
						$business['next_payment'],
						$business['last_payment'],
						$business['billing_status'],
						$business['wallet_active'] ? 'Activo' : 'Inactivo',
					)
				)
			);
		}
		fclose( $output );
		exit;
	}

	public static function status_form( int $business_id, string $status, string $icon, string $label, string $class_name = '' ): void {
		if ( ! current_user_can( 'manage_options' ) || ! in_array( $status, array( 'active', 'suspended', 'archived' ), true ) ) {
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="loyalty_wallet_update_business_status">
			<input type="hidden" name="business_id" value="<?php echo esc_attr( $business_id ); ?>">
			<input type="hidden" name="business_status" value="<?php echo esc_attr( $status ); ?>">
			<?php wp_nonce_field( 'loyalty_wallet_update_business_status' ); ?>
			<button type="submit" class="<?php echo esc_attr( $class_name ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
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
			$started_at = (string) get_user_meta( $user->ID, self::STARTED_AT_META, true );
			$status = (string) get_user_meta( $user->ID, self::STATUS_META, true );
			$next_payment = (string) get_user_meta( $user->ID, self::NEXT_PAYMENT_META, true );
			$plan = (string) get_user_meta( $user->ID, self::PLAN_META, true );
			$plan = self::valid_plan( $plan ) ? $plan : 'starter';
			$monthly_price = (float) get_user_meta( $user->ID, self::MONTHLY_PRICE_META, true );
			$days_until_payment = $next_payment ? (int) floor( ( strtotime( $next_payment . ' 00:00:00' ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) : null;
			$billing_status = 'active';
			if ( 'archived' === $status ) {
				$billing_status = 'archived';
			} elseif ( 'suspended' === $status ) {
				$billing_status = 'suspended';
			} elseif ( null !== $days_until_payment && $days_until_payment < 0 ) {
				$billing_status = 'overdue';
			} elseif ( null !== $days_until_payment && $days_until_payment <= 7 ) {
				$billing_status = 'due';
			}
			$businesses[] = array(
				'id'            => (int) $user->ID,
				'name'          => $name ?: $user->display_name,
				'email'         => (string) get_user_meta( $user->ID, self::EMAIL_META, true ) ?: $user->user_email,
				'website'       => (string) get_user_meta( $user->ID, self::WEBSITE_META, true ),
				'whatsapp'      => (string) get_user_meta( $user->ID, self::WHATSAPP_META, true ),
				'owner_name'    => $user->display_name,
				'owner_email'   => $user->user_email,
				'manager_phone' => (string) get_user_meta( $user->ID, self::MANAGER_PHONE_META, true ),
				'started_at'    => $started_at ?: mysql2date( 'Y-m-d', $user->user_registered ),
				'next_payment'  => $next_payment,
				'days_until_payment' => $days_until_payment,
				'last_payment'  => (string) get_user_meta( $user->ID, self::LAST_PAYMENT_META, true ),
				'plan'          => $plan,
				'plan_label'    => 'pro' === $plan ? 'Pro' : ( 'enterprise' === $plan ? 'Empresa' : 'Starter' ),
				'monthly_price' => max( 0, $monthly_price ),
				'status'        => in_array( $status, array( 'active', 'suspended', 'archived' ), true ) ? $status : 'active',
				'billing_status'=> $billing_status,
				'wallet_active' => ! in_array( $billing_status, array( 'suspended', 'archived' ), true ),
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

	private static function valid_phone( string $phone ): bool {
		return strlen( $phone ) >= 8 && strlen( $phone ) <= 15;
	}

	private static function valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}

	private static function valid_plan( string $plan ): bool {
		return in_array( $plan, array( 'starter', 'pro', 'enterprise' ), true );
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
