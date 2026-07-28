<div id="lw-qr-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-review-tab" hidden>
	<div class="lw-settings-heading"><h2>Configuración de reseñas de Google</h2><p>Conecta la ficha del negocio utilizada por el código QR de reseñas.</p></div>
	<section class="lw-direct-review-link">
		<div class="lw-direct-review-icon"><span class="dashicons dashicons-star-filled"></span></div>
		<div><label for="lw-google-review-url">Enlace de reseñas de Google</label><p><a href="https://support.google.com/business/answer/3474122" target="_blank" rel="noopener noreferrer">Cómo obtener el enlace «Solicitar reseñas» de Google <span aria-hidden="true">↗</span></a></p></div>
		<div class="lw-direct-review-input"><input id="lw-google-review-url" name="google_review_url" type="url" value="<?php echo esc_attr( $google['review_url_input'] ); ?>" placeholder="https://g.page/r/.../review"><a id="lw-open-review-link" class="button" href="<?php echo esc_url( $google['review_url'] ?: '#' ); ?>" target="_blank" rel="noopener noreferrer">Abrir enlace <span aria-hidden="true">↗</span></a></div>
	</section>
	<section class="lw-review-reward" aria-labelledby="lw-review-reward-title">
		<div class="lw-reward-icon"><span class="dashicons dashicons-awards"></span></div>
		<div class="lw-reward-copy"><h3 id="lw-review-reward-title">Puntos por reseña</h3><p>Puntos asignados automáticamente cuando una reseña verificada se agrega como cliente.</p></div>
		<label for="lw-review-points"><span>Puntos por reseña</span><span class="lw-points-input"><input id="lw-review-points" name="review_points" type="number" value="<?php echo esc_attr( $google['review_points'] ); ?>" min="0" max="100000" step="1"><b>pts</b></span></label>
	</section>
	<details class="lw-credentials-toggle" <?php echo $google['is_configured'] ? 'open' : ''; ?>>
		<summary><span><strong>Credenciales de Google para reseñas</strong><small>Conexión avanzada con el Perfil de Negocio de Google</small></span><i aria-hidden="true"></i></summary>
		<div class="lw-credentials-content">
			<div class="lw-review-fields">
				<div class="lw-field-group"><label for="lw-place-id">ID de lugar de Google</label><input id="lw-place-id" name="place_id" type="text" value="<?php echo esc_attr( $google['place_id'] ); ?>" placeholder="ChIJ..." maxlength="512" required><a class="lw-help-link" href="https://developers.google.com/maps/documentation/places/web-service/place-id#find-id" target="_blank" rel="noopener noreferrer">Encontrar el ID de lugar <span aria-hidden="true">↗</span></a></div>
				<div class="lw-field-group"><label for="lw-maps-url">Enlace de Google Maps</label><input id="lw-maps-url" name="maps_url" type="url" value="<?php echo esc_attr( $google['maps_url'] ); ?>" placeholder="https://www.google.com/maps/place/..." required><a id="lw-open-map" class="lw-help-link" href="<?php echo esc_url( $google['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer">Abrir negocio en Google Maps <span aria-hidden="true">↗</span></a></div>
			</div>
			<label class="lw-sandbox-option"><input name="google_sandbox_mode" type="checkbox" value="1" <?php checked( $google['sandbox_mode'] ); ?>><span><strong>Modo de pruebas</strong><small>Usa reseñas de prueba sin conectar Google</small></span></label>
			<div class="lw-google-status <?php echo $google['is_connected'] ? 'is-ready' : ''; ?>"><span class="lw-status-dot"></span><span><strong>Perfil de Negocio de Google</strong><small><?php echo $google['is_connected'] ? 'Cuenta conectada con Google' : 'Todavía no conectado'; ?></small></span></div>
			<a class="button lw-google-profile-link" href="https://business.google.com/add" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-google"></span> Agregar o reclamar Perfil de Negocio <span aria-hidden="true">↗</span></a>
			<div class="lw-oauth-json-import">
				<div><strong>Archivo OAuth de Google</strong><small>Carga el archivo <code>client_secret_….json</code>. El navegador copiará automáticamente <code>web.client_id</code> y <code>web.client_secret</code>; el archivo no se sube ni se conserva.</small></div>
				<input id="lw-google-oauth-json" type="file" accept="application/json,.json" hidden>
				<label class="button" for="lw-google-oauth-json"><span class="dashicons dashicons-upload"></span>Cargar client_secret.json</label>
				<span id="lw-google-oauth-json-status" class="lw-oauth-json-status" role="status"></span>
			</div>
			<div class="lw-google-credential-field">
				<div class="lw-label-with-link"><label for="lw-google-client-id"><code>client_id</code> — ID de cliente OAuth</label><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Abrir clientes OAuth <span aria-hidden="true">↗</span></a></div>
				<input id="lw-google-client-id" name="google_client_id" type="text" value="<?php echo esc_attr( $google['client_id'] ); ?>" placeholder="000000000000-xxxx.apps.googleusercontent.com">
				<small>En el JSON de Google: <code>web.client_id</code></small>
			</div>
			<div class="lw-google-credential-field">
				<div class="lw-label-with-link"><label for="lw-google-client-secret"><code>client_secret</code> — Secreto del cliente OAuth</label><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Administrar credenciales OAuth <span aria-hidden="true">↗</span></a></div>
				<input id="lw-google-client-secret" name="google_client_secret" type="password" value="" placeholder="<?php echo $google['has_secret'] ? 'Guardado — escribe un valor nuevo para reemplazarlo' : 'Escribe el secreto del cliente'; ?>" autocomplete="new-password">
				<small>En el JSON de Google: <code>web.client_secret</code></small>
			</div>
			<div class="lw-google-callback-box">
				<div><strong>URI de redirección autorizada</strong><small>Copia esta dirección exactamente en Google Cloud → Cliente OAuth → URI de redirección autorizada.</small></div>
				<code><?php echo esc_html( $google['callback_url'] ); ?></code>
			</div>
			<div class="lw-google-connect-actions">
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=loyalty_wallet_google_connect' ), 'loyalty_wallet_google_connect' ) ); ?>"><span class="dashicons dashicons-google"></span><?php echo $google['is_connected'] ? 'Volver a conectar con Google' : 'Conectar con Google'; ?></a>
				<?php if ( $google['is_connected'] ) : ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=loyalty_wallet_google_refresh' ), 'loyalty_wallet_google_refresh' ) ); ?>"><span class="dashicons dashicons-update"></span>Actualizar negocios</a>
				<?php endif; ?>
			</div>
			<div class="lw-google-resource-note"><span class="dashicons dashicons-info-outline"></span><span><strong>No escribas identificadores manualmente.</strong><small>Después de conectar Google, el plugin obtiene los campos <code>accounts/{accountId}</code> y <code>locations/{locationId}</code> directamente de Business Profile.</small></span></div>
			<div class="lw-google-credential-field">
				<div class="lw-label-with-link"><label for="lw-google-account-id">Cuenta de Google Business Profile</label><a href="https://business.google.com/locations" target="_blank" rel="noopener noreferrer">Abrir Perfil de Negocio <span aria-hidden="true">↗</span></a></div>
				<select id="lw-google-account-id" name="google_account_id" <?php disabled( ! $google['account_options'] ); ?>>
					<?php if ( ! $google['account_options'] ) : ?><option value="">Conecta Google para cargar las cuentas</option><?php endif; ?>
					<?php foreach ( $google['account_options'] as $resource_name => $label ) : ?>
						<option value="<?php echo esc_attr( $resource_name ); ?>" <?php selected( $google['account_id'], $resource_name ); ?>><?php echo esc_html( $label . ' (' . $resource_name . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="lw-google-credential-field">
				<div class="lw-label-with-link"><label for="lw-google-location-id">Ubicación de Google Business Profile</label><a href="https://business.google.com/locations" target="_blank" rel="noopener noreferrer">Abrir ubicaciones del negocio <span aria-hidden="true">↗</span></a></div>
				<select id="lw-google-location-id" name="google_location_id" <?php disabled( ! $google['location_options'] ); ?>>
					<?php if ( ! $google['location_options'] ) : ?><option value="">Conecta Google para cargar las ubicaciones</option><?php endif; ?>
					<?php foreach ( $google['location_options'] as $resource_name => $label ) : ?>
						<option value="<?php echo esc_attr( $resource_name ); ?>" <?php selected( $google['location_id'], $resource_name ); ?>><?php echo esc_html( $label . ' (' . $resource_name . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</details>
</div>
