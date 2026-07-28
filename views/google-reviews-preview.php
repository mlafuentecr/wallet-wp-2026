<div class="lw-card lw-qr-preview lw-qr-preview-horizontal">
	<h2>Tu código QR de reseñas y lealtad</h2>
	<div class="lw-qr-frame <?php echo $google['review_url'] ? '' : 'is-empty'; ?>">
		<img id="lw-qr-image" src="<?php echo esc_url( $google['qr_image_url'] ); ?>" data-flow-url="<?php echo esc_attr( $google['flow_url'] ?? '' ); ?>" alt="Vista previa del código QR de reseñas y tarjeta de lealtad" <?php echo $google['review_url'] ? '' : 'hidden'; ?>>
		<p id="lw-qr-empty" <?php echo $google['review_url'] ? 'hidden' : ''; ?>>Escribe un enlace de reseñas de Google o un ID de lugar para generar el código QR.</p>
	</div>
	<div id="lw-qr-actions" class="lw-qr-actions" <?php echo $google['review_url'] ? '' : 'hidden'; ?>>
		<a id="lw-qr-open" class="button lw-qr-action-button" href="<?php echo esc_url( $google['flow_url'] ?? $google['review_url'] ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span>Abrir enlace</a>
		<button id="lw-qr-copy" class="button lw-qr-action-button" type="button" data-url="<?php echo esc_attr( $google['flow_url'] ?? $google['review_url'] ); ?>"><span class="dashicons dashicons-clipboard"></span><span class="lw-copy-label">Copiar enlace</span></button>
		<button type="button" class="button lw-qr-action-button lw-open-redemption"><span class="dashicons dashicons-cart"></span>Canjear</button>
		<small id="lw-qr-copy-status" role="status" aria-live="polite"></small>
	</div>
	<div class="lw-latest-review">
		<hr class="lw-settings-divider">
		<h2>Última reseña de Google <?php if ( $google['sandbox_mode'] ) : ?><span class="lw-sandbox-badge">Pruebas</span><?php endif; ?></h2>
		<div id="lw-latest-stars" class="lw-review-stars" aria-label="<?php echo $google['sandbox_mode'] ? '5 de 5 estrellas' : 'Calificación no disponible'; ?>"><?php echo $google['sandbox_mode'] ? '★★★★★' : '☆☆☆☆☆'; ?></div>
		<blockquote id="lw-latest-text" class="lw-review-text"><?php echo $google['sandbox_mode'] ? '¡Excelente servicio! Definitivamente volvería.' : 'La última reseña de un cliente aparecerá aquí cuando conectes el Perfil de Negocio de Google.'; ?></blockquote>
		<div class="lw-review-meta"><span class="dashicons dashicons-admin-users"></span><span><strong id="lw-latest-name"><?php echo $google['sandbox_mode'] ? 'Cliente de prueba' : 'Nombre del autor'; ?></strong><small id="lw-latest-date"><?php echo $google['sandbox_mode'] ? '5 estrellas · Reseña de prueba' : 'Fecha y calificación pendientes'; ?></small></span></div>
		<div class="lw-review-check">
			<label for="lw-review-search">Buscar reseña por nombre del cliente</label>
			<div><input id="lw-review-search" type="search" placeholder="Nombre del cliente"><button id="lw-review-search-button" type="button" class="button">Buscar reseña</button></div>
			<p id="lw-review-search-result" aria-live="polite"></p>
			<form id="lw-add-customer-form" class="lw-add-customer" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
				<input type="hidden" name="action" value="loyalty_wallet_add_customer"><?php wp_nonce_field( 'loyalty_wallet_add_customer' ); ?>
				<input id="lw-customer-name" type="hidden" name="customer_name">
				<input type="hidden" name="customer_review" value="¡Excelente servicio! Definitivamente volvería.">
				<input type="hidden" name="customer_rating" value="5">
				<input type="hidden" name="customer_review_source" value="google">
				<input type="hidden" name="customer_review_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
				<label for="lw-customer-email">Correo del cliente</label><input id="lw-customer-email" name="customer_email" type="email" placeholder="cliente@ejemplo.com" required>
				<label for="lw-customer-phone">Teléfono del cliente</label><input id="lw-customer-phone" name="customer_phone" type="tel" placeholder="+506 8888 8888" required>
				<button type="submit" class="button button-primary">Agregar cliente</button>
			</form>
		</div>
	</div>
</div>
