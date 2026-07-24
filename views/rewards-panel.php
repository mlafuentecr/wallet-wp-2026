<div id="lw-rewards-panel" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-rewards-tab" hidden>
	<div class="lw-rewards-header">
		<div><span class="dashicons dashicons-cart" aria-hidden="true"></span><div><h2>Canjear puntos</h2><p>Escanea la tarjeta del cliente, selecciona una recompensa y confirma el canje.</p></div></div>
		<button type="button" class="button button-primary lw-start-scanner"><span class="dashicons dashicons-camera"></span> Escanear QR del cliente</button>
	</div>

	<section class="lw-redemption-workflow" hidden>
		<div class="lw-redemption-step">
			<span>1</span>
			<div><strong>Identificar cliente</strong><small>El QR contiene un ID, nombre y saldo firmados. El saldo real siempre se valida en el servidor.</small></div>
		</div>
		<div class="lw-scanner-shell">
			<div class="lw-scanner-camera">
				<video id="lw-redemption-video" playsinline muted hidden></video>
				<div class="lw-scanner-placeholder"><span class="dashicons dashicons-camera"></span><strong>Cámara lista para escanear</strong><small>En producción, permite acceso a la cámara cuando el navegador lo solicite.</small></div>
			</div>
			<div class="lw-scanner-controls">
				<button type="button" class="button button-primary lw-camera-start">Iniciar cámara</button>
				<button type="button" class="button lw-camera-stop" hidden>Detener cámara</button>
				<label for="lw-redemption-code">También puedes pegar el código del QR</label>
				<div><input id="lw-redemption-code" type="text" autocomplete="off" placeholder="LW1..."><button type="button" class="button lw-lookup-code">Buscar cliente</button></div>
				<p class="lw-redemption-status" role="status" aria-live="polite"></p>
			</div>
		</div>

		<div class="lw-redemption-customer" hidden>
			<div class="lw-redemption-step">
				<span>2</span>
				<div><strong>Seleccionar recompensa</strong><small>Solo se pueden escoger productos que el cliente pueda pagar con su saldo actual.</small></div>
			</div>
			<div class="lw-scanned-customer">
				<span class="dashicons dashicons-id"></span>
				<div><strong class="lw-scanned-name"></strong><small class="lw-scanned-email"></small></div>
				<div><small>Saldo disponible</small><strong><b class="lw-scanned-points">0</b> pts</strong></div>
			</div>
			<div class="lw-redeem-products" role="list"></div>
			<button type="button" class="button lw-scan-another">Escanear otro cliente</button>
		</div>
	</section>

	<section class="lw-reward-catalog">
		<div class="lw-catalog-heading"><div><h3>Productos canjeables</h3><p>Configura lo que el negocio ofrece y cuántos puntos cuesta.</p></div><span><?php echo esc_html( count( $rewards ) ); ?> disponibles</span></div>
		<div class="lw-reward-list">
			<?php foreach ( $rewards as $reward ) : ?>
				<form class="lw-reward-row" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="loyalty_wallet_save_reward">
					<input type="hidden" name="reward_id" value="<?php echo esc_attr( $reward['id'] ); ?>">
					<?php wp_nonce_field( 'loyalty_wallet_save_reward' ); ?>
					<label>Producto<input name="reward_name" type="text" value="<?php echo esc_attr( $reward['name'] ); ?>" maxlength="100" required></label>
					<label>Puntos<input name="reward_points" type="number" value="<?php echo esc_attr( absint( $reward['points'] ) ); ?>" min="1" max="100000" required></label>
					<button type="submit" class="button">Guardar</button>
					<button type="submit" class="button lw-delete-reward" name="action" value="loyalty_wallet_delete_reward" aria-label="<?php echo esc_attr( 'Delete ' . $reward['name'] ); ?>"><span class="dashicons dashicons-trash"></span></button>
				</form>
			<?php endforeach; ?>
		</div>
		<form class="lw-add-reward" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="loyalty_wallet_save_reward">
			<?php wp_nonce_field( 'loyalty_wallet_save_reward' ); ?>
			<div><strong>Agregar producto</strong><small>Ejemplo: Café gratis · 100 puntos</small></div>
			<label>Producto<input name="reward_name" type="text" placeholder="Café gratis" maxlength="100" required></label>
			<label>Puntos<input name="reward_points" type="number" placeholder="100" min="1" max="100000" required></label>
			<button type="submit" class="button button-primary"><span class="dashicons dashicons-plus-alt2"></span> Agregar</button>
		</form>
	</section>
</div>
