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
		$customers[] = array( 'id' => wp_generate_uuid4(), 'name' => $name, 'email' => $email, 'phone' => $phone, 'contact_preference' => 'whatsapp', 'review' => $review, 'rating' => $rating, 'date' => $visit_date, 'next_visit' => '', 'birthday' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birthday ) ? $birthday : '', 'visits' => array( $visit_date ), 'points' => $review_points, 'review_rewarded' => true, 'review_points_awarded' => $review_points );
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
				'next_visit'            => '',
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
		$next_visit = isset( $_POST['customer_next_visit'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_next_visit'] ) ) : '';
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
		$updated_customer = null;
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
					'next_visit'            => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $next_visit ) ? $next_visit : '',
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
					'redemptions'           => isset( $customer['redemptions'] ) && is_array( $customer['redemptions'] ) ? $customer['redemptions'] : array(),
					'point_transactions'    => isset( $customer['point_transactions'] ) && is_array( $customer['point_transactions'] ) ? $customer['point_transactions'] : array(),
				);
				$updated = true;
				$updated_customer = $customer;
				break;
			}
		}
		unset( $customer );
		if ( ! $updated ) {
			return 'invalid_customer';
		}
		update_user_meta( $user_id, self::META_KEY, $customers );
		if ( $updated_customer && ! empty( $updated_customer['wallet_object_id'] ) ) {
			Loyalty_Wallet_Google_Wallet_Module::sync_customer_pass( $user_id, $updated_customer );
		}
		return 'customer_updated';
	}

	public static function set_next_visit( int $user_id, string $customer_id, string $next_visit ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $next_visit ) ) {
			return false;
		}

		$customers = self::all( $user_id );
		$updated_customer = null;
		foreach ( $customers as &$customer ) {
			if ( isset( $customer['id'] ) && hash_equals( (string) $customer['id'], $customer_id ) ) {
				$customer['next_visit'] = $next_visit;
				$updated_customer = $customer;
				break;
			}
		}
		unset( $customer );
		if ( ! $updated_customer ) {
			return false;
		}

		update_user_meta( $user_id, self::META_KEY, $customers );
		if ( ! empty( $updated_customer['wallet_object_id'] ) ) {
			Loyalty_Wallet_Google_Wallet_Module::sync_customer_pass( $user_id, $updated_customer );
		}
		return true;
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

	public static function find( int $user_id, string $customer_id = '', string $member_id = '' ): ?array {
		foreach ( self::all( $user_id ) as $customer ) {
			$id_matches     = $customer_id && isset( $customer['id'] ) && hash_equals( (string) $customer['id'], $customer_id );
			$member_matches = $member_id && isset( $customer['wallet_member_id'] ) && hash_equals( (string) $customer['wallet_member_id'], $member_id );
			if ( $id_matches || $member_matches ) {
				return $customer;
			}
		}
		return null;
	}

	public static function redeem( int $user_id, string $customer_id, array $reward, string $request_id ) {
		$customers = self::all( $user_id );
		$updated   = null;
		$cost      = absint( $reward['points'] ?? 0 );

		if ( ! $customer_id || ! $request_id || ! $cost ) {
			return new WP_Error( 'invalid_redemption', 'The redemption request is incomplete.' );
		}

		foreach ( $customers as &$customer ) {
			if ( empty( $customer['id'] ) || ! hash_equals( (string) $customer['id'], $customer_id ) ) {
				continue;
			}

			$customer['redemptions'] = isset( $customer['redemptions'] ) && is_array( $customer['redemptions'] ) ? $customer['redemptions'] : array();
			foreach ( $customer['redemptions'] as $redemption ) {
				if ( isset( $redemption['request_id'] ) && hash_equals( (string) $redemption['request_id'], $request_id ) ) {
					return new WP_Error( 'duplicate_redemption', 'This redemption was already processed.' );
				}
			}

			$balance = absint( $customer['points'] ?? 0 );
			if ( $balance < $cost ) {
				return new WP_Error( 'insufficient_points', 'The customer does not have enough points for this reward.' );
			}

			$redemption = array(
				'id'          => wp_generate_uuid4(),
				'request_id'  => $request_id,
				'reward_id'   => sanitize_text_field( (string) ( $reward['id'] ?? '' ) ),
				'reward_name' => sanitize_text_field( (string) ( $reward['name'] ?? 'Reward' ) ),
				'points'      => $cost,
				'created_at'  => current_time( 'mysql' ),
				'created_by'  => get_current_user_id(),
			);
			$customer['points'] = $balance - $cost;
			$customer['redemptions'][] = $redemption;
			$customer['point_transactions'] = isset( $customer['point_transactions'] ) && is_array( $customer['point_transactions'] ) ? $customer['point_transactions'] : array();
			$customer['point_transactions'][] = array(
				'id'         => $redemption['id'],
				'type'       => 'redemption',
				'points'     => -$cost,
				'label'      => $redemption['reward_name'],
				'created_at' => $redemption['created_at'],
			);
			$updated = $customer;
			break;
		}
		unset( $customer );

		if ( ! $updated ) {
			return new WP_Error( 'customer_not_found', 'The scanned customer could not be found.' );
		}

		update_user_meta( $user_id, self::META_KEY, $customers );
		$wallet_synced = empty( $updated['wallet_object_id'] )
			? true
			: Loyalty_Wallet_Google_Wallet_Module::sync_customer_pass( $user_id, $updated );
		$redemptions = $updated['redemptions'];

		return array(
			'customer'      => $updated,
			'redemption'    => end( $redemptions ),
			'wallet_synced' => $wallet_synced,
		);
	}

	public static function render( array $customers ): void {
		require LOYALTY_WALLET_DIR . 'views/customers-panel.php';
	}

	public static function render_activity( array $customers ): void {
		require LOYALTY_WALLET_DIR . 'views/activity-panel.php';
	}
}
