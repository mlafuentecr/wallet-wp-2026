<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loyalty_Wallet_Engagement_Module {
	private const META_KEY = '_loyalty_wallet_engagement_reminders';
	private const EMAIL_META = '_loyalty_wallet_email';

	public static function all( int $user_id ): array {
		$items = get_user_meta( $user_id, self::META_KEY, true );
		return is_array( $items ) ? array_values( $items ) : array();
	}

	public static function schedule( int $user_id ): string {
		$customer_id = isset( $_POST['customer_id'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_id'] ) ) : '';
		$appointment = isset( $_POST['appointment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_date'] ) ) : '';
		$raw_channels = isset( $_POST['reminder_channels'] ) ? (array) wp_unslash( $_POST['reminder_channels'] ) : array();
		$channels = array_values( array_unique( array_intersect( array_map( 'sanitize_key', $raw_channels ), array( 'email', 'whatsapp' ) ) ) );
		$appointment_message = isset( $_POST['appointment_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['appointment_message'] ) ) : '';
		$birthday_message = isset( $_POST['birthday_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['birthday_message'] ) ) : '';
		$inactive_message = isset( $_POST['inactive_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['inactive_message'] ) ) : '';
		$recurring = isset( $_POST['reminder_recurring'] );
		$one_day = isset( $_POST['reminder_one_day'] );
		$one_week = isset( $_POST['reminder_one_week'] );
		$birthday = isset( $_POST['reminder_birthday'] );
		$inactive = isset( $_POST['reminder_inactive'] );
		$inactive_days = isset( $_POST['inactive_days'] ) ? min( 3650, max( 1, absint( $_POST['inactive_days'] ) ) ) : 30;
		if ( ! $customer_id || ! $channels || ! ( $recurring || $one_day || $one_week || $birthday || $inactive ) ) {
			return 'invalid_reminder';
		}
		$customer = self::customer( $user_id, $customer_id );
		if ( ! $customer || ( in_array( 'email', $channels, true ) && ! is_email( $customer['email'] ?? '' ) ) || ( in_array( 'whatsapp', $channels, true ) && empty( $customer['phone'] ) ) ) {
			return 'invalid_reminder';
		}
		if ( in_array( 'whatsapp', $channels, true ) && ! is_email( get_user_meta( $user_id, self::EMAIL_META, true ) ) ) {
			return 'missing_wallet_email';
		}
		if ( ( ( $recurring || $one_day || $one_week ) && ! $appointment_message ) || ( $birthday && ! $birthday_message ) || ( $inactive && ! $inactive_message ) ) {
			return 'invalid_reminder';
		}
		if ( ( $recurring || $one_day || $one_week ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $appointment ) ) {
			return 'invalid_reminder';
		}
		$dates = array();
		$appointment_date = $appointment ? new DateTimeImmutable( $appointment, wp_timezone() ) : null;
		if ( $recurring ) {
			$dates['appointment_recurring'] = $appointment;
		}
		if ( $one_day && $appointment_date ) {
			$dates['appointment_one_day'] = $appointment_date->modify( '-1 day' )->format( 'Y-m-d' );
		}
		if ( $one_week && $appointment_date ) {
			$dates['appointment_one_week'] = $appointment_date->modify( '-1 week' )->format( 'Y-m-d' );
		}
		if ( $birthday ) {
			if ( empty( $customer['birthday'] ) ) return 'missing_birthday';
			$month_day = substr( (string) $customer['birthday'], 5 );
			$birthday_date = new DateTimeImmutable( current_time( 'Y' ) . '-' . $month_day, wp_timezone() );
			if ( $birthday_date->format( 'Y-m-d' ) < current_time( 'Y-m-d' ) ) $birthday_date = $birthday_date->modify( '+1 year' );
			$dates['birthday'] = $birthday_date->format( 'Y-m-d' );
		}
		if ( $inactive ) {
			$last_visit = new DateTimeImmutable( (string) ( $customer['date'] ?? current_time( 'Y-m-d' ) ), wp_timezone() );
			$dates['inactive'] = $last_visit->modify( '+' . $inactive_days . ' days' )->format( 'Y-m-d' );
		}
		$items = self::all( $user_id );
		foreach ( $dates as $type => $date ) {
			$message = 'birthday' === $type ? $birthday_message : ( 'inactive' === $type ? $inactive_message : $appointment_message );
			foreach ( $channels as $channel ) {
				$id = wp_generate_uuid4();
				$items[] = array( 'id' => $id, 'type' => $type, 'customer_id' => $customer_id, 'appointment_date' => $appointment, 'reminder_date' => $date, 'channel' => $channel, 'message' => $message, 'status' => 'pending', 'notified' => false, 'inactive_days' => $inactive_days );
				$run = new DateTimeImmutable( $date . ' 09:00:00', wp_timezone() );
				wp_schedule_single_event( max( time() + 30, $run->getTimestamp() ), 'loyalty_wallet_run_engagement_reminder', array( $user_id, $id ) );
			}
		}
		update_user_meta( $user_id, self::META_KEY, $items );
		return 'reminder_saved';
	}

	public static function run( int $user_id, string $reminder_id ): void {
		$items = self::all( $user_id );
		$repeat_item = null;
		foreach ( $items as &$item ) {
			if ( ! isset( $item['id'] ) || ! hash_equals( (string) $item['id'], $reminder_id ) || ! empty( $item['notified'] ) ) {
				continue;
			}
			$customer = self::customer( $user_id, (string) $item['customer_id'] );
			if ( ! $customer ) {
				return;
			}
			if ( 'email' === $item['channel'] ) {
				wp_mail( $customer['email'], 'Appointment reminder', $item['message'] );
				$item['status'] = 'sent';
				$repeat_item = $item;
			} else {
				$wallet_email = (string) get_user_meta( $user_id, self::EMAIL_META, true );
				if ( is_email( $wallet_email ) ) {
					$link = admin_url( 'admin.php?page=loyalty-wallet&lw_tab=customers#lw-customer-' . rawurlencode( (string) $item['customer_id'] ) );
					wp_mail( $wallet_email, 'WhatsApp reminder pending: ' . $customer['name'], "A WhatsApp reminder is ready to send.\n\nCustomer: {$customer['name']}\nAppointment: {$item['appointment_date']}\nOpen Loyalty Wallet: {$link}" );
				}
			}
			$item['notified'] = true;
			$item['notified_at'] = current_time( 'mysql' );
			break;
		}
		unset( $item );
		if ( $repeat_item ) self::append_next_recurring( $items, $repeat_item, $user_id );
		update_user_meta( $user_id, self::META_KEY, $items );
	}

	public static function mark_sent( int $user_id ): string {
		$id = isset( $_POST['reminder_id'] ) ? sanitize_text_field( wp_unslash( $_POST['reminder_id'] ) ) : '';
		$items = self::all( $user_id );
		foreach ( $items as &$item ) {
			if ( isset( $item['id'] ) && hash_equals( (string) $item['id'], $id ) ) {
				$item['status'] = 'sent';
				$item['sent_at'] = current_time( 'mysql' );
				$repeat_item = $item;
				unset( $item );
				self::append_next_recurring( $items, $repeat_item, $user_id );
				update_user_meta( $user_id, self::META_KEY, $items );
				return 'reminder_sent';
			}
		}
		return 'invalid_reminder';
	}

	public static function due( int $user_id ): array {
		$today = current_time( 'Y-m-d' );
		return array_values( array_filter( self::all( $user_id ), static fn( $item ) => 'whatsapp' === ( $item['channel'] ?? '' ) && 'pending' === ( $item['status'] ?? '' ) && ( $item['reminder_date'] ?? '9999-12-31' ) <= $today ) );
	}

	public static function render_alerts( int $user_id ): void {
		$reminders = self::due( $user_id );
		$customers = Loyalty_Wallet_Customers_Module::all( $user_id );
		require LOYALTY_WALLET_DIR . 'views/engagement-alerts.php';
	}

	private static function customer( int $user_id, string $customer_id ): ?array {
		foreach ( Loyalty_Wallet_Customers_Module::all( $user_id ) as $customer ) {
			if ( isset( $customer['id'] ) && hash_equals( (string) $customer['id'], $customer_id ) ) {
				return $customer;
			}
		}
		return null;
	}

	private static function append_next_recurring( array &$items, array $item, int $user_id ): void {
		$type = $item['type'] ?? '';
		$modifier = 'appointment_recurring' === $type ? '+1 month' : ( 'birthday' === $type ? '+1 year' : '' );
		if ( ! $modifier ) return;
		$next = new DateTimeImmutable( (string) $item['reminder_date'], wp_timezone() );
		$item['id'] = wp_generate_uuid4();
		$item['reminder_date'] = $next->modify( $modifier )->format( 'Y-m-d' );
		if ( 'appointment_recurring' === $type && ! empty( $item['appointment_date'] ) ) {
			$item['appointment_date'] = ( new DateTimeImmutable( $item['appointment_date'], wp_timezone() ) )->modify( '+1 month' )->format( 'Y-m-d' );
		}
		$item['status'] = 'pending';
		$item['notified'] = false;
		unset( $item['notified_at'], $item['sent_at'] );
		$items[] = $item;
		$run = new DateTimeImmutable( $item['reminder_date'] . ' 09:00:00', wp_timezone() );
		wp_schedule_single_event( max( time() + 30, $run->getTimestamp() ), 'loyalty_wallet_run_engagement_reminder', array( $user_id, $item['id'] ) );
	}
}
