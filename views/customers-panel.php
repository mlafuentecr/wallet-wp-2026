<div id="lw-customers-panel" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-customers-tab" hidden>
	<div class="lw-customers-header"><div><h2>Customers</h2><p>Customers added from Google Reviews and Google Wallet enrollment.</p></div><div class="lw-customers-total"><span class="dashicons dashicons-groups"></span><span><small>Total saved customers</small><strong><?php echo esc_html( count( $customers ) ); ?></strong></span></div></div>
	<?php if ( ! $customers ) : ?>
		<div class="lw-empty-customers"><span class="dashicons dashicons-groups"></span><p>No customers added yet.</p></div>
	<?php else : ?>
		<div class="lw-customers-search" role="search">
			<label for="lw-customer-search"><span class="dashicons dashicons-search" aria-hidden="true"></span><input id="lw-customer-search" type="search" placeholder="Search by name, email or phone" autocomplete="off" aria-label="Search customers" aria-describedby="lw-customer-search-status"></label>
			<span id="lw-customer-search-status" class="lw-customer-search-status" aria-live="polite"><?php echo esc_html( sprintf( 'Showing %d %s', count( $customers ), 1 === count( $customers ) ? 'customer' : 'customers' ) ); ?></span>
		</div>
		<?php $google_business_url = Loyalty_Wallet_Google_Reviews_Module::data( get_current_user_id() )['maps_url']; ?>
		<div class="lw-customers-list">
			<?php foreach ( array_reverse( $customers ) as $customer ) : ?>
				<?php
				$year        = (int) current_time( 'Y' );
				$visits      = isset( $customer['visits'] ) && is_array( $customer['visits'] ) ? $customer['visits'] : array( $customer['date'] );
				$year_visits = count( array_filter( $visits, static fn( $visit ) => (int) substr( (string) $visit, 0, 4 ) === $year ) );
				$current_month = current_time( 'Y-m' );
				$month_visits = count( array_filter( $visits, static fn( $visit ) => substr( (string) $visit, 0, 7 ) === $current_month ) );
				$total_visits = count( $visits );
				$phone_digits = preg_replace( '/\D+/', '', (string) ( $customer['phone'] ?? '' ) );
				if ( 8 === strlen( $phone_digits ) ) { $phone_digits = '506' . $phone_digits; }
				$reminder_message = Loyalty_Wallet_Engagement_Module::render_template( get_current_user_id(), 'general', $customer );
				$birthday_message = Loyalty_Wallet_Engagement_Module::render_template( get_current_user_id(), 'birthday', $customer );
				$appointment_message = Loyalty_Wallet_Engagement_Module::render_template( get_current_user_id(), 'appointment', $customer, array( 'appointment_date' => current_time( 'Y-m-d' ) ) );
				$inactive_message = Loyalty_Wallet_Engagement_Module::render_template( get_current_user_id(), 'inactive', $customer );
				$preferred_reminder_channel = 'email' === ( $customer['contact_preference'] ?? 'whatsapp' ) ? 'email' : 'whatsapp';
				$whatsapp_url = $phone_digits ? 'https://wa.me/' . $phone_digits . '?text=' . rawurlencode( $reminder_message ) : '';
				$customer_search_text = strtolower( remove_accents( implode( ' ', array( (string) $customer['name'], (string) $customer['email'], (string) ( $customer['phone'] ?? '' ) ) ) ) );
				?>
				<article class="lw-customer" id="lw-customer-<?php echo esc_attr( $customer['id'] ); ?>" data-customer-search="<?php echo esc_attr( $customer_search_text ); ?>">
					<div class="lw-customer-view">
						<div class="lw-customer-top">
							<div class="lw-customer-summary"><strong class="lw-customer-name"><?php echo esc_html( $customer['name'] ); ?></strong><?php if ( ! empty( $customer['rating'] ) ) : ?><div class="lw-customer-rating"><span>Nos calificó con</span><span class="lw-customer-stars" aria-label="<?php echo esc_attr( $customer['rating'] ); ?> estrellas"><?php echo esc_html( str_repeat( '★', (int) $customer['rating'] ) ); ?></span><?php if ( $google_business_url ) : ?><i></i><a href="<?php echo esc_url( $google_business_url ); ?>" target="_blank" rel="noopener noreferrer">Ver en Google <span aria-hidden="true">↗</span></a><?php endif; ?></div><?php else : ?><div class="lw-customer-rating"><span>Google Wallet member</span><span class="lw-points-badge">Identity verified</span></div><?php endif; ?></div>
							<div class="lw-customer-actions"><?php if ( $whatsapp_url ) : ?><button type="button" class="button lw-whatsapp-customer lw-message-toggle" data-phone="<?php echo esc_attr( $phone_digits ); ?>" data-message="<?php echo esc_attr( $reminder_message ); ?>"><span class="dashicons dashicons-format-chat"></span>WhatsApp</button><button type="button" class="button lw-birthday-customer lw-message-toggle" data-phone="<?php echo esc_attr( $phone_digits ); ?>" data-message="<?php echo esc_attr( $birthday_message ); ?>"><span class="dashicons dashicons-star-filled"></span>Cumpleaños</button><?php endif; ?><button type="button" class="button lw-reminder-toggle"><span class="dashicons dashicons-bell"></span>Recordatorio</button><button type="button" class="button lw-add-visit-toggle"><span class="dashicons dashicons-calendar-alt"></span>Agregar visita</button><button type="button" class="button lw-edit-customer"><span class="dashicons dashicons-edit"></span>Editar</button></div>
						</div>
						<div class="lw-customer-bottom">
							<div class="lw-customer-contact-row"><a href="mailto:<?php echo esc_attr( $customer['email'] ); ?>"><span class="dashicons dashicons-email"></span><?php echo esc_html( $customer['email'] ); ?></a><?php if ( ! empty( $customer['phone'] ) ) : ?><i></i><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $customer['phone'] ) ); ?>"><span class="dashicons dashicons-phone"></span><?php echo esc_html( $customer['phone'] ); ?></a><?php endif; ?><i></i><span><span class="dashicons dashicons-calendar-alt"></span>Última visita: <b><?php echo esc_html( $customer['date'] ); ?></b></span><i></i><span><span class="dashicons dashicons-clock"></span>Próxima visita: <b><?php echo esc_html( ! empty( $customer['next_visit'] ) ? $customer['next_visit'] : 'No programada' ); ?></b></span><i></i><span><span class="dashicons dashicons-awards"></span>Puntos del cliente: <b class="lw-inline-points">★ <?php echo esc_html( absint( $customer['points'] ?? 0 ) ); ?></b></span><i></i><span><span class="dashicons dashicons-buddicons-activity"></span>Cumpleaños: <b><?php echo esc_html( ! empty( $customer['birthday'] ) ? $customer['birthday'] : 'No registrado' ); ?></b></span></div>
						</div>
						<div class="lw-customer-metrics"><div class="lw-visit-stats"><div><span class="lw-stat-icon is-month"><span class="dashicons dashicons-calendar-alt"></span></span><span><small>Este mes</small><strong><?php echo esc_html( $month_visits ); ?></strong></span></div><div><span class="lw-stat-icon is-year"><span class="dashicons dashicons-calendar"></span></span><span><small>Este año</small><strong><?php echo esc_html( $year_visits ); ?></strong></span></div><div><span class="lw-stat-icon is-total"><span class="dashicons dashicons-star-empty"></span></span><span><small>Total</small><strong><?php echo esc_html( $total_visits ); ?></strong></span></div></div></div>
					</div>
					<form class="lw-add-visit-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
						<input type="hidden" name="action" value="loyalty_wallet_add_visit"><input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer['id'] ); ?>"><?php wp_nonce_field( 'loyalty_wallet_add_visit' ); ?>
						<label>Visit date<input name="visit_date" type="date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label><button type="submit" class="button button-primary">Save visit</button><button type="button" class="button lw-cancel-visit">Cancel</button>
					</form>
					<div class="lw-whatsapp-composer" hidden>
						<label>WhatsApp message<textarea class="lw-whatsapp-message" rows="3"><?php echo esc_textarea( $reminder_message ); ?></textarea></label><a class="button button-primary lw-whatsapp-send" href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer">Send</a><button type="button" class="button lw-whatsapp-cancel">Cancel</button>
					</div>
					<form class="lw-reminder-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
						<input type="hidden" name="action" value="loyalty_wallet_save_reminder"><input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer['id'] ); ?>"><?php wp_nonce_field( 'loyalty_wallet_save_reminder' ); ?>
						<fieldset class="lw-reminder-channels">
							<legend>Send reminders by</legend>
							<label><input type="checkbox" name="reminder_channels[]" value="email" <?php checked( $preferred_reminder_channel, 'email' ); ?>><span class="dashicons dashicons-email"></span>Email</label>
							<label><input type="checkbox" name="reminder_channels[]" value="whatsapp" <?php checked( $preferred_reminder_channel, 'whatsapp' ); ?>><span class="dashicons dashicons-format-chat"></span>WhatsApp</label>
						</fieldset>
						<fieldset class="lw-reminder-section lw-appointment-reminders">
							<legend>Appointment reminders</legend>
							<label class="lw-reminder-date">Appointment date<input name="appointment_date" type="date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"></label>
							<div class="lw-reminder-options">
								<label><input type="checkbox" name="reminder_recurring" value="1"><span><strong>Recurring appointment</strong><small>Keep an appointment reminder active</small></span></label>
								<label><input type="checkbox" name="reminder_one_day" value="1"><span><strong>1 day before</strong><small>Remind one day before the appointment</small></span></label>
								<label><input type="checkbox" name="reminder_one_week" value="1"><span><strong>1 week before</strong><small>Remind one week before the appointment</small></span></label>
							</div>
							<label class="lw-reminder-message-field">Appointment message<textarea name="appointment_message" rows="3"><?php echo esc_textarea( $appointment_message ); ?></textarea></label>
						</fieldset>
						<fieldset class="lw-reminder-section">
							<legend>Birthday reminder</legend>
							<label class="lw-reminder-rule"><input type="checkbox" name="reminder_birthday" value="1"><span><strong>Birthday</strong><small>Use the birthday saved on the customer</small></span></label>
							<label class="lw-reminder-message-field">Birthday message<textarea name="birthday_message" rows="3"><?php echo esc_textarea( $birthday_message ); ?></textarea></label>
						</fieldset>
						<fieldset class="lw-reminder-section">
							<legend>Inactive customer reminder</legend>
							<label class="lw-reminder-rule"><input type="checkbox" name="reminder_inactive" value="1"><span><strong>Has not visited</strong><small>Remind after this many days without a visit</small></span><input class="lw-inactive-days" type="number" name="inactive_days" value="30" min="1" max="3650" aria-label="Inactive days"></label>
							<label class="lw-reminder-message-field">Inactive customer message<textarea name="inactive_message" rows="3"><?php echo esc_textarea( $inactive_message ); ?></textarea></label>
						</fieldset>
						<div class="lw-reminder-actions"><button type="submit" class="button button-primary">Schedule reminders</button><button type="button" class="button lw-reminder-cancel">Cancel</button></div>
					</form>
					<form class="lw-customer-edit" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
						<input type="hidden" name="action" value="loyalty_wallet_update_customer"><input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer['id'] ); ?>"><?php wp_nonce_field( 'loyalty_wallet_update_customer' ); ?>
						<?php wp_nonce_field( 'loyalty_wallet_delete_customer', '_wpnonce_delete', false ); ?>
						<input type="hidden" name="customer_review" value="<?php echo esc_attr( $customer['review'] ); ?>"><input type="hidden" name="customer_rating" value="<?php echo esc_attr( $customer['rating'] ); ?>"><input type="hidden" name="customer_source" value="<?php echo esc_attr( $customer['source'] ?? '' ); ?>">
						<div class="lw-edit-header"><span class="dashicons dashicons-edit"></span><span><strong>Edit customer details</strong><small>Update the customer information below.</small></span></div>
						<label>Name<input name="customer_name" type="text" value="<?php echo esc_attr( $customer['name'] ); ?>" required></label>
						<label>Email<input name="customer_email" type="email" value="<?php echo esc_attr( $customer['email'] ); ?>" required></label>
						<label>Phone<input name="customer_phone" type="tel" value="<?php echo esc_attr( $customer['phone'] ?? '' ); ?>" required></label>
						<label>Last visit date<input name="customer_review_date" type="date" value="<?php echo esc_attr( $customer['date'] ); ?>" required></label>
						<label>Next visit date<input name="customer_next_visit" type="date" value="<?php echo esc_attr( $customer['next_visit'] ?? '' ); ?>"></label>
						<label>Birthday<input name="customer_birthday" type="date" value="<?php echo esc_attr( $customer['birthday'] ?? '' ); ?>"></label>
						<label>Contact preference<select name="customer_contact_preference"><option value="email" <?php selected( $customer['contact_preference'] ?? 'whatsapp', 'email' ); ?>>Email</option><option value="phone" <?php selected( $customer['contact_preference'] ?? 'whatsapp', 'phone' ); ?>>Phone</option><option value="whatsapp" <?php selected( $customer['contact_preference'] ?? 'whatsapp', 'whatsapp' ); ?>>WhatsApp</option></select></label>
						<div class="lw-edit-actions"><button type="submit" class="button button-primary"><span class="dashicons dashicons-saved"></span>Save changes</button><button type="button" class="button lw-cancel-customer"><span class="dashicons dashicons-no-alt"></span>Cancel</button><button type="submit" name="action" value="loyalty_wallet_delete_customer" class="button lw-delete-customer"><span class="dashicons dashicons-trash"></span>Delete customer</button></div>
					</form>
				</article>
			<?php endforeach; ?>
		</div>
		<div id="lw-customer-search-empty" class="lw-empty-customers lw-customer-search-empty" hidden><span class="dashicons dashicons-search"></span><p>No customers match your search.</p></div>
	<?php endif; ?>
</div>
