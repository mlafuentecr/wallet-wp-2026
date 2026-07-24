<div id="lw-message-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-messages-tab" hidden>
	<div class="lw-settings-heading">
		<h2>Global messages</h2>
		<p>Define the default messages loaded for every customer. You can still customize a message before scheduling or sending it.</p>
	</div>
	<div class="lw-message-token-help">
		<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
		<div>
			<strong>Available variables</strong>
			<p><code>{{name}}</code> <code>{{points}}</code> <code>{{business_name}}</code> <code>{{appointment_date}}</code> <code>{{last_visit}}</code> <code>{{next_visit}}</code></p>
		</div>
	</div>
	<div class="lw-global-message-grid">
		<label>
			<span><strong>General WhatsApp message</strong><small>Default for the WhatsApp button on each customer.</small></span>
			<textarea name="global_message_general" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['general'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Appointment reminder</strong><small>Default for recurring, one-day and one-week appointment reminders.</small></span>
			<textarea name="global_message_appointment" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['appointment'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Birthday message</strong><small>Default loaded for birthday messages and reminders.</small></span>
			<textarea name="global_message_birthday" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['birthday'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Inactive customer message</strong><small>Default for customers who have not visited recently.</small></span>
			<textarea name="global_message_inactive" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['inactive'] ); ?></textarea>
		</label>
	</div>
	<p class="lw-global-message-note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>Changes update future message defaults for all customers. Messages that are already scheduled keep their existing text.</p>
	<button type="submit" class="button button-primary lw-save-global-messages">Save global messages</button>
</div>
