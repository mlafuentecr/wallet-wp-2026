<?php
$active_businesses   = count( array_filter( $businesses, static fn( array $business ): bool => 'active' === $business['status'] ) );
$archived_businesses = count( $businesses ) - $active_businesses;
$notice_messages     = array(
	'negocio_creado'    => array( 'success', 'El negocio fue creado correctamente.' ),
	'negocio_activado'  => array( 'success', 'El negocio fue activado.' ),
	'negocio_archivado' => array( 'success', 'El negocio fue archivado.' ),
	'negocio_actualizado' => array( 'success', 'La información del negocio fue actualizada.' ),
	'datos_invalidos'   => array( 'error', 'Completa los datos obligatorios y revisa el correo, teléfono, fechas, sitio web y WhatsApp.' ),
	'usuario_existente' => array( 'error', 'Ese usuario o correo ya está registrado en WordPress.' ),
	'error_creacion'    => array( 'error', 'No se pudo crear el negocio.' ),
	'estado_invalido'   => array( 'error', 'No se pudo cambiar el estado del negocio.' ),
);
?>
<div class="wrap lw-businesses-wrap">
	<div class="lw-businesses-hero">
		<div>
			<span class="lw-businesses-eyebrow">ADMINISTRACIÓN DE LOYALTY WALLET</span>
			<h1>Negocios</h1>
			<p>Administra la información, pagos y estado de cada negocio.</p>
		</div>
		<div class="lw-businesses-hero-actions">
			<a class="button button-primary lw-new-business-button" href="#nuevo-negocio"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span>Agregar negocio</span></a>
		</div>
	</div>

	<?php if ( isset( $notice_messages[ $notice ] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_messages[ $notice ][0] ); ?> inline lw-business-notice"><p><strong><?php echo esc_html( $notice_messages[ $notice ][1] ); ?></strong></p></div>
	<?php endif; ?>
	<?php if ( is_array( $temporary_access ) && ! empty( $temporary_access['username'] ) ) : ?>
		<div class="notice notice-success inline lw-business-access-notice">
			<p><strong>Acceso temporal para <?php echo esc_html( $temporary_access['name'] ); ?></strong></p>
			<p>Usuario: <code><?php echo esc_html( $temporary_access['username'] ); ?></code> · Contraseña: <code><?php echo esc_html( $temporary_access['password'] ); ?></code></p>
			<p>Copia estos datos ahora. La contraseña no volverá a mostrarse.</p>
		</div>
	<?php endif; ?>

	<div class="lw-businesses-summary">
		<div><span class="dashicons dashicons-store" aria-hidden="true"></span><span><small>Total de negocios</small><strong><?php echo esc_html( count( $businesses ) ); ?></strong></span></div>
		<div><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span><small>Negocios activos</small><strong><?php echo esc_html( $active_businesses ); ?></strong></span></div>
		<div><span class="dashicons dashicons-archive" aria-hidden="true"></span><span><small>Negocios archivados</small><strong><?php echo esc_html( $archived_businesses ); ?></strong></span></div>
	</div>

	<details class="lw-businesses-card lw-create-business" id="nuevo-negocio" <?php echo in_array( $notice, array( 'datos_invalidos', 'usuario_existente', 'error_creacion' ), true ) ? 'open' : ''; ?>>
		<summary><span><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span><strong>Agregar un nuevo negocio</strong><small>Crea un acceso independiente y registra la información administrativa.</small></span></span><i aria-hidden="true"></i></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-create-business-form">
			<input type="hidden" name="action" value="loyalty_wallet_create_business">
			<?php wp_nonce_field( 'loyalty_wallet_create_business' ); ?>
			<div class="lw-business-form-grid">
				<label><span>Nombre del negocio *</span><input name="business_name" type="text" maxlength="120" placeholder="Café Central" required></label>
				<label><span>Nombre del encargado *</span><input name="owner_name" type="text" maxlength="120" placeholder="María Pérez" required></label>
				<label><span>Teléfono del encargado *</span><input name="manager_phone" type="tel" placeholder="+506 8888 8888" required><small>Incluye el código de país.</small></label>
				<label><span>Correo del encargado *</span><input name="owner_email" type="email" autocomplete="off" placeholder="maria@ejemplo.com" required></label>
				<label><span>Usuario de acceso *</span><input name="owner_username" type="text" maxlength="60" autocomplete="off" placeholder="cafe-central" required></label>
				<label><span>Correo del negocio</span><input name="business_email" type="email" placeholder="info@cafecentral.com"></label>
				<label><span>Fecha de inicio *</span><input name="business_started_at" type="date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label>
				<label><span>Siguiente pago *</span><input name="business_next_payment" type="date" required></label>
				<label><span>WhatsApp del negocio</span><input name="business_whatsapp" type="tel" placeholder="+506 8888 8888"><small>Incluye el código de país.</small></label>
				<label><span>Sitio web</span><input name="business_website" type="url" placeholder="https://cafecentral.com"></label>
			</div>
			<div class="lw-create-business-actions"><button type="submit" class="button button-primary"><span class="dashicons dashicons-store" aria-hidden="true"></span><span>Crear negocio</span></button><p>Se generará una contraseña temporal y el negocio solo podrá entrar a Loyalty Wallet.</p></div>
		</form>
	</details>

	<div class="lw-businesses-card">
		<div class="lw-businesses-card-head">
			<div><h2>Lista de negocios</h2><p>Consulta el encargado, fechas de pago y estado de cada cuenta.</p></div>
			<label class="lw-business-search"><span class="dashicons dashicons-search" aria-hidden="true"></span><input id="lw-business-search" type="search" placeholder="Buscar negocio, encargado o correo" aria-label="Buscar negocios"></label>
		</div>

		<div class="lw-table-scroll">
			<table class="widefat fixed striped lw-businesses-table">
				<thead><tr><th>Negocio</th><th>Encargado</th><th>Inicio</th><th>Siguiente pago</th><th>Estado</th><th class="lw-table-actions">Acciones</th></tr></thead>
				<tbody>
				<?php if ( ! $businesses ) : ?>
					<tr><td colspan="6">Todavía no hay negocios en Loyalty Wallet.</td></tr>
				<?php else : ?>
					<?php foreach ( $businesses as $business ) : ?>
						<?php
						$is_archived = 'archived' === $business['status'];
						$search_text = strtolower(
							remove_accents(
								implode(
									' ',
									array(
										$business['name'],
										$business['email'],
										$business['owner_name'],
										$business['owner_email'],
										$business['manager_phone'],
										$is_archived ? 'archivado' : 'activo',
									)
								)
							)
						);
						?>
						<tr class="lw-business-row <?php echo $is_archived ? 'is-archived' : ''; ?>" data-business-search="<?php echo esc_attr( $search_text ); ?>">
							<td data-label="Negocio"><div class="lw-business-identity"><?php if ( $business['logo_url'] ) : ?><img src="<?php echo esc_url( $business['logo_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-store" aria-hidden="true"></span><?php endif; ?><span><strong><?php echo esc_html( $business['name'] ); ?></strong><small><?php echo esc_html( $business['email'] ); ?></small></span></div></td>
							<td data-label="Encargado"><strong><?php echo esc_html( $business['owner_name'] ); ?></strong><small><span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php echo esc_html( $business['owner_email'] ); ?></small><small><span class="dashicons dashicons-phone" aria-hidden="true"></span><?php echo esc_html( $business['manager_phone'] ?: 'Sin teléfono' ); ?></small></td>
							<td data-label="Inicio"><time datetime="<?php echo esc_attr( $business['started_at'] ); ?>"><?php echo esc_html( wp_date( 'd/m/Y', strtotime( $business['started_at'] ) ) ); ?></time></td>
							<td data-label="Siguiente pago"><?php if ( $business['next_payment'] ) : ?><time datetime="<?php echo esc_attr( $business['next_payment'] ); ?>"><?php echo esc_html( wp_date( 'd/m/Y', strtotime( $business['next_payment'] ) ) ); ?></time><?php else : ?><span class="lw-empty-value">Sin definir</span><?php endif; ?></td>
							<td data-label="Estado"><span class="lw-business-status is-<?php echo $is_archived ? 'archived' : 'active'; ?>"><span></span><?php echo $is_archived ? 'Archivado' : 'Activo'; ?></span></td>
							<td data-label="Acciones" class="lw-table-actions">
								<button type="button" class="button lw-edit-business-toggle" aria-expanded="false" aria-controls="editar-negocio-<?php echo esc_attr( $business['id'] ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span>Editar</span></button>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="loyalty_wallet_update_business_status">
									<input type="hidden" name="business_id" value="<?php echo esc_attr( $business['id'] ); ?>">
									<input type="hidden" name="business_status" value="<?php echo $is_archived ? 'active' : 'archived'; ?>">
									<?php wp_nonce_field( 'loyalty_wallet_update_business_status' ); ?>
									<button type="submit" class="button lw-status-action <?php echo $is_archived ? 'is-activate' : 'is-archive'; ?>"><span class="dashicons dashicons-<?php echo $is_archived ? 'yes-alt' : 'archive'; ?>" aria-hidden="true"></span><span><?php echo $is_archived ? 'Activar' : 'Archivar'; ?></span></button>
								</form>
							</td>
						</tr>
						<tr id="editar-negocio-<?php echo esc_attr( $business['id'] ); ?>" class="lw-business-edit-row" hidden>
							<td colspan="6">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-inline-business-form">
									<input type="hidden" name="action" value="loyalty_wallet_update_business">
									<input type="hidden" name="business_id" value="<?php echo esc_attr( $business['id'] ); ?>">
									<?php wp_nonce_field( 'loyalty_wallet_update_business' ); ?>
									<label><span>Negocio</span><input name="business_name" type="text" value="<?php echo esc_attr( $business['name'] ); ?>" required></label>
									<label><span>Encargado</span><input name="owner_name" type="text" value="<?php echo esc_attr( $business['owner_name'] ); ?>" required></label>
									<label><span>Teléfono</span><input name="manager_phone" type="tel" value="<?php echo esc_attr( $business['manager_phone'] ); ?>" placeholder="+506 8888 8888" required></label>
									<label><span>Inicio</span><input name="business_started_at" type="date" value="<?php echo esc_attr( $business['started_at'] ); ?>" required></label>
									<label><span>Siguiente pago</span><input name="business_next_payment" type="date" value="<?php echo esc_attr( $business['next_payment'] ); ?>" required></label>
									<div class="lw-inline-business-actions"><button type="submit" class="button button-primary">Guardar</button><button type="button" class="button lw-cancel-business-edit">Cancelar</button></div>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<p id="lw-business-search-empty" class="lw-business-search-empty" hidden>No hay negocios que coincidan con la búsqueda.</p>
	</div>
</div>
