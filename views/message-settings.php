<div id="lw-message-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-messages-tab" hidden>
	<div class="lw-settings-heading">
		<h2>Mensajes globales</h2>
		<p>Define los mensajes predeterminados para todos los clientes. Podrás personalizarlos antes de programarlos o enviarlos.</p>
	</div>
	<div class="lw-message-token-help">
		<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
		<div>
			<strong>Variables disponibles</strong>
			<p><code>{{name}}</code> <code>{{points}}</code> <code>{{business_name}}</code> <code>{{appointment_date}}</code> <code>{{last_visit}}</code> <code>{{next_visit}}</code></p>
		</div>
	</div>
	<div class="lw-global-message-grid">
		<label>
			<span><strong>Mensaje general de WhatsApp</strong><small>Mensaje predeterminado del botón de WhatsApp de cada cliente.</small></span>
			<textarea name="global_message_general" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['general'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Recordatorio de cita</strong><small>Predeterminado para recordatorios recurrentes, un día antes y una semana antes.</small></span>
			<textarea name="global_message_appointment" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['appointment'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Mensaje de cumpleaños</strong><small>Predeterminado para mensajes y recordatorios de cumpleaños.</small></span>
			<textarea name="global_message_birthday" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['birthday'] ); ?></textarea>
		</label>
		<label>
			<span><strong>Mensaje para clientes inactivos</strong><small>Predeterminado para clientes que no han visitado recientemente.</small></span>
			<textarea name="global_message_inactive" rows="4" maxlength="2000" required><?php echo esc_textarea( $templates['inactive'] ); ?></textarea>
		</label>
	</div>
	<p class="lw-global-message-note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>Los cambios actualizan los mensajes futuros de todos los clientes. Los mensajes ya programados conservarán su texto actual.</p>
	<button type="submit" class="button button-primary lw-save-global-messages">Guardar mensajes globales</button>
</div>
