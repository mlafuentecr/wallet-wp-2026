<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Businesses_Module {
	private const NAME_META      = '_loyalty_wallet_name';
	private const EMAIL_META     = '_loyalty_wallet_email';
	private const LOGO_META      = '_loyalty_wallet_logo_id';
	private const CUSTOMERS_META = '_loyalty_wallet_review_customers';
	private const CLIENT_ROLE    = 'loyalty_wallet_client';

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view businesses.', 'loyalty-wallet' ) );
		}

		$businesses           = self::all();
		$selected_business_id = isset( $_GET['business_id'] ) ? absint( $_GET['business_id'] ) : 0;
		$selected_business    = null;

		foreach ( $businesses as $business ) {
			if ( $selected_business_id === $business['id'] ) {
				$selected_business = $business;
				break;
			}
		}

		require LOYALTY_WALLET_DIR . 'views/businesses-page.php';
	}

	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export customer data.', 'loyalty-wallet' ) );
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
			wp_die( esc_html__( 'Business not found.', 'loyalty-wallet' ) );
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
			wp_die( esc_html__( 'The export could not be created.', 'loyalty-wallet' ) );
		}

		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv(
			$output,
			array(
				'Business ID',
				'Business name',
				'Business email',
				'WordPress owner',
				'Customer ID',
				'Customer name',
				'Customer email',
				'Customer phone',
				'Contact preference',
				'Rating',
				'Review',
				'Points',
				'Birthday',
				'Last visit',
				'Total visits',
				'Source',
				'Google Wallet member ID',
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
}
