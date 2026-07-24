<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Customers_Module {
	private const META_KEY = '_loyalty_wallet_review_customers';

	public static function all( int $user_id ): array {
		$customers = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $customers ) ) {
			return array();
		}
		$migrated = false;
		foreach ( $customers as &$customer ) {
			if ( ! array_key_exists( 'review_rewarded', $customer ) ) {
				$customer['points'] = Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id );
				$customer['review_rewarded'] = true;
				$migrated = true;
			}
			if ( ! empty( $customer['review_rewarded'] ) && ! array_key_exists( 'review_points_awarded', $customer ) ) {
				$customer['review_points_awarded'] = Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id );
				$customer['points'] = $customer['review_points_awarded'];
				$migrated = true;
			}
		}
		unset( $customer );
		if ( $migrated ) {
			update_user_meta( $user_id, self::META_KEY, $customers );
		}
		return array_values( $customers );
	}

	public static function add( int $user_id ): string {
		$name = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
		$email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
		$phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
		$review = isset( $_POST['customer_review'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_review'] ) ) : '';
		$rating = isset( $_POST['customer_rating'] ) ? min( 5, max( 1, absint( $_POST['customer_rating'] ) ) ) : 0;
		$date = isset( $_POST['customer_review_date'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_review_date'] ) ) : '';
		$birthday = isset( $_POST['customer_birthday'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_birthday'] ) ) : '';
		if ( '' === $name || ! is_email( $email ) || '' === $phone || '' === $review || 0 === $rating ) {
			return 'invalid_customer';
		}
		$customers = self::all( $user_id );
		$visit_date = $date ?: current_time( 'Y-m-d' );
		$review_points = Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id );
		$customers[] = array( 'id' => wp_generate_uuid4(), 'name' => $name, 'email' => $email, 'phone' => $phone, 'contact_preference' => 'whatsapp', 'review' => $review, 'rating' => $rating, 'date' => $visit_date, 'birthday' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birthday ) ? $birthday : '', 'visits' => array( $visit_date ), 'points' => $review_points, 'review_rewarded' => true, 'review_points_awarded' => $review_points );
		update_user_meta( $user_id, self::META_KEY, $customers );
		return 'customer_added';
	}

	public static function sync_review_points( int $user_id, int $previous_points, int $new_points ): void {
		if ( $previous_points === $new_points ) {
			return;
		}

		$customers = self::all( $user_id );
		$updated   = false;

		foreach ( $customers as &$customer ) {
			if ( empty( $customer['review_rewarded'] ) ) {
				continue;
			}

			$awarded_points = absint( $customer['review_points_awarded'] ?? $previous_points );
			$customer['points'] = max( 0, absint( $customer['points'] ?? 0 ) - $awarded_points + $new_points );
			$customer['review_points_awarded'] = $new_points;
			$updated = true;
		}
		unset( $customer );

		if ( $updated ) {
			update_user_meta( $user_id, self::META_KEY, $customers );
		}
	}

	public static function upsert_google_member( int $user_id, array $identity ): array {
		$customers = self::all( $user_id );
		$sub       = sanitize_text_field( (string) ( $identity['google_sub'] ?? '' ) );
		$email     = sanitize_email( (string) ( $identity['email'] ?? '' ) );
		$points    = Loyalty_Wallet_Google_Reviews_Module::review_points( $user_id );
		$match     = null;

		foreach ( $customers as $index => &$customer ) {
			$same_google_user = $sub && isset( $customer['google_sub'] ) && hash_equals( (string) $customer['google_sub'], $sub );
			$same_email       = $email && isset( $customer['email'] ) && strtolower( (string) $customer['email'] ) === strtolower( $email );
			if ( ! $same_google_user && ! $same_email ) {
				continue;
			}

			$customer['google_sub']       = $sub;
			$customer['name']             = sanitize_text_field( (string) ( $identity['name'] ?? $customer['name'] ?? '' ) );
			$customer['email']            = $email;
			$customer['phone']            = sanitize_text_field( (string) ( $identity['phone'] ?? $customer['phone'] ?? '' ) );
			$customer['picture']          = esc_url_raw( (string) ( $identity['picture'] ?? '' ) );
			$customer['wallet_member_id'] = sanitize_text_field( (string) ( $identity['wallet_member_id'] ?? '' ) );
			$customer['wallet_object_id'] = sanitize_text_field( (string) ( $identity['wallet_object_id'] ?? '' ) );
			$customer['source']            = 'google_wallet';
			if ( empty( $customer['review_rewarded'] ) ) {
				$customer['points']                 = $points;
				$customer['review_rewarded']        = true;
				$customer['review_points_awarded'] = $points;
			}
			$match = $index;
			break;
		}
		unset( $customer );

		if ( null === $match ) {
			$customer = array(
				'id'                    => wp_generate_uuid4(),
				'name'                  => sanitize_text_field( (string) ( $identity['name'] ?? '' ) ),
				'email'                 => $email,
				'phone'                 => sanitize_text_field( (string) ( $identity['phone'] ?? '' ) ),
				'contact_preference'    => 'whatsapp',
				'review'                => '',
				'rating'                => 0,
				'date'                  => current_time( 'Y-m-d' ),
				'birthday'              => '',
				'visits'                => array(),
				'points'                => $points,
				'review_rewarded'       => true,
				'review_points_awarded' => $points,
				'google_sub'            => $sub,
				'picture'               => esc_url_raw( (string) ( $identity['picture'] ?? '' ) ),
				'wallet_member_id'      => sanitize_text_field( (string) ( $identity['wallet_member_id'] ?? '' ) ),
				'wallet_object_id'      => sanitize_text_field( (string) ( $identity['wallet_object_id'] ?? '' ) ),
				'source'                => 'google_wallet',
			);
			$customers[] = $customer;
			$match       = count( $customers ) - 1;
		}

		update_user_meta( $user_id, self::META_KEY, $customers );
		return $customers[ $match ];
	}

	public static function update( int $user_id ): string {
		$id = isset( $_POST['customer_id'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_id'] ) ) : '';
		$name = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
		$email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
		$phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
		$review = isset( $_POST['customer_review'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_review'] ) ) : '';
		$rating = isset( $_POST['customer_rating'] ) ? min( 5, max( 1, absint( $_POST['customer_rating'] ) ) ) : 0;
		$date = isset( $_POST['customer_review_date'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_review_date'] ) ) : '';
		$birthday = isset( $_POST['customer_birthday'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_birthday'] ) ) : '';
		$contact_preference = isset( $_POST['customer_contact_preference'] ) ? sanitize_key( wp_unslash( $_POST['customer_contact_preference'] ) ) : 'whatsapp';
		$contact_preference = in_array( $contact_preference, array( 'email', 'phone', 'whatsapp' ), true ) ? $contact_preference : 'whatsapp';
		$source = isset( $_POST['customer_source'] ) ? sanitize_key( wp_unslash( $_POST['customer_source'] ) ) : '';
		$is_google_member = 'google_wallet' === $source;
		if ( ! $id || ! $name || ! is_email( $email ) || ! $phone || ( ! $is_google_member && ( ! $review || ! $rating ) ) ) {
			return 'invalid_customer';
		}
		$customers = self::all( $user_id );
		$updated = false;
		foreach ( $customers as &$customer ) {
			if ( isset( $customer['id'] ) && hash_equals( (string) $customer['id'], $id ) ) {
				$visits = isset( $customer['visits'] ) && is_array( $customer['visits'] ) ? $customer['visits'] : array( $customer['date'] );
				$customer = array(
					'id'                    => $id,
					'name'                  => $name,
					'email'                 => $email,
					'phone'                 => $phone,
					'contact_preference'    => $contact_preference,
					'review'                => $review,
					'rating'                => $rating,
					'date'                  => $date ?: current_time( 'Y-m-d' ),
					'birthday'              => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birthday ) ? $birthday : (string) ( $customer['birthday'] ?? '' ),
					'visits'                => $visits,
					'points'                => absint( $customer['points'] ?? 0 ),
					'review_rewarded'       => ! empty( $customer['review_rewarded'] ),
					'review_points_awarded' => absint( $customer['review_points_awarded'] ?? 0 ),
					'google_sub'            => sanitize_text_field( (string) ( $customer['google_sub'] ?? '' ) ),
					'picture'               => esc_url_raw( (string) ( $customer['picture'] ?? '' ) ),
					'wallet_member_id'      => sanitize_text_field( (string) ( $customer['wallet_member_id'] ?? '' ) ),
					'wallet_object_id'      => sanitize_text_field( (string) ( $customer['wallet_object_id'] ?? '' ) ),
					'source'                => $is_google_member ? 'google_wallet' : sanitize_key( (string) ( $customer['source'] ?? '' ) ),
				);
				$updated = true;
				break;
			}
		}
		unset( $customer );
		if ( ! $updated ) {
			return 'invalid_customer';
		}
		update_user_meta( $user_id, self::META_KEY, $customers );
		return 'customer_updated';
	}

	public static function add_visit( int $user_id ): string {
		$id = isset( $_POST['customer_id'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_id'] ) ) : '';
		$date = isset( $_POST['visit_date'] ) ? sanitize_text_field( wp_unslash( $_POST['visit_date'] ) ) : current_time( 'Y-m-d' );
		if ( ! $id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return 'invalid_customer';
		}
		$customers = self::all( $user_id );
		$updated = false;
		foreach ( $customers as &$customer ) {
			if ( isset( $customer['id'] ) && hash_equals( (string) $customer['id'], $id ) ) {
				$customer['visits'] = isset( $customer['visits'] ) && is_array( $customer['visits'] ) ? $customer['visits'] : array();
				$customer['visits'][] = $date;
				$customer['visits'] = array_values( array_unique( $customer['visits'] ) );
				$customer['date'] = $date;
				$updated = true;
				break;
			}
		}
		unset( $customer );
		if ( ! $updated ) return 'invalid_customer';
		update_user_meta( $user_id, self::META_KEY, $customers );
		return 'visit_added';
	}

	public static function delete( int $user_id ): string {
		$id = isset( $_POST['customer_id'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_id'] ) ) : '';
		if ( ! $id ) {
			return 'invalid_customer';
		}
		$customers = self::all( $user_id );
		$remaining = array_values(
			array_filter(
				$customers,
				static fn( $customer ) => ! isset( $customer['id'] ) || ! hash_equals( (string) $customer['id'], $id )
			)
		);
		if ( count( $remaining ) === count( $customers ) ) {
			return 'invalid_customer';
		}
		update_user_meta( $user_id, self::META_KEY, $remaining );
		return 'customer_deleted';
	}

	public static function render( array $customers ): void {
		require LOYALTY_WALLET_DIR . 'views/customers-panel.php';
	}

	public static function render_activity( array $customers ): void {
		require LOYALTY_WALLET_DIR . 'views/activity-panel.php';
	}
}
