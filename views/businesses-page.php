<?php
$export_all_url = wp_nonce_url(
	admin_url( 'admin-post.php?action=loyalty_wallet_export_business_customers' ),
	'loyalty_wallet_export_business_customers'
);
?>
<div class="wrap lw-businesses-wrap">
	<div class="lw-businesses-hero">
		<div>
			<span class="lw-businesses-eyebrow">ADMINISTRACIÓN DE LOYALTY WALLET</span>
			<h1>Negocios</h1>
			<p>Administra cada negocio y mantén sus clientes separados y listos para exportar.</p>
		</div>
		<div class="lw-businesses-hero-actions">
			<a class="button lw-new-business-button" href="#nuevo-negocio"><span class="dashicons dashicons-plus-alt2"></span>Agregar negocio</a>
			<a class="button button-primary lw-export-button" href="<?php echo esc_url( $export_all_url ); ?>"><span class="dashicons dashicons-download"></span>Exportar todos los clientes</a>
		</div>
	</div>

	<?php
	$notice_messages = array(
		'negocio_creado'   => array( 'success', 'El negocio fue creado correctamente.' ),
		'datos_invalidos'  => array( 'error', 'Completa los datos obligatorios y revisa el correo, sitio web y WhatsApp.' ),
		'usuario_existente'=> array( 'error', 'Ese usuario o correo ya está registrado en WordPress.' ),
		'error_creacion'   => array( 'error', 'No se pudo crear el negocio.' ),
	);
	if ( isset( $notice_messages[ $notice ] ) ) :
		?>
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
		<div><span class="dashicons dashicons-store"></span><span><small>Total de negocios</small><strong><?php echo esc_html( count( $businesses ) ); ?></strong></span></div>
		<div><span class="dashicons dashicons-groups"></span><span><small>Total de clientes</small><strong><?php echo esc_html( array_sum( array_column( $businesses, 'customer_count' ) ) ); ?></strong></span></div>
		<div><span class="dashicons dashicons-chart-bar"></span><span><small>Total de visitas</small><strong><?php echo esc_html( array_sum( array_column( $businesses, 'total_visits' ) ) ); ?></strong></span></div>
	</div>

	<details class="lw-businesses-card lw-create-business" id="nuevo-negocio" <?php echo in_array( $notice, array( 'datos_invalidos', 'usuario_existente', 'error_creacion' ), true ) ? 'open' : ''; ?>>
		<summary><span><span class="dashicons dashicons-plus-alt2"></span><span><strong>Agregar un nuevo negocio</strong><small>Crea un acceso independiente con sus propios clientes, puntos y configuración.</small></span></span><i aria-hidden="true"></i></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-create-business-form">
			<input type="hidden" name="action" value="loyalty_wallet_create_business">
			<?php wp_nonce_field( 'loyalty_wallet_create_business' ); ?>
			<div class="lw-business-form-grid">
				<label><span>Nombre del negocio *</span><input name="business_name" type="text" maxlength="120" placeholder="Café Central" required></label>
				<label><span>Nombre del encargado *</span><input name="owner_name" type="text" maxlength="120" placeholder="María Pérez" required></label>
				<label><span>Usuario de acceso *</span><input name="owner_username" type="text" maxlength="60" autocomplete="off" placeholder="cafe-central" required></label>
				<label><span>Correo del encargado *</span><input name="owner_email" type="email" autocomplete="off" placeholder="maria@ejemplo.com" required></label>
				<label><span>Correo del negocio</span><input name="business_email" type="email" placeholder="info@cafecentral.com"></label>
				<label><span>WhatsApp del negocio</span><input name="business_whatsapp" type="tel" placeholder="+506 8888 8888"><small>Incluye el código de país.</small></label>
				<label class="lw-business-form-wide"><span>Sitio web</span><input name="business_website" type="url" placeholder="https://cafecentral.com"></label>
			</div>
			<div class="lw-create-business-actions"><button type="submit" class="button button-primary"><span class="dashicons dashicons-store"></span>Crear negocio</button><p>Se generará una contraseña temporal y el negocio solo podrá entrar a Loyalty Wallet.</p></div>
		</form>
	</details>

	<div class="lw-businesses-card">
		<div class="lw-businesses-card-head">
			<div><h2>Lista de negocios</h2><p>Selecciona un negocio para revisar o exportar sus clientes.</p></div>
			<label class="lw-business-search"><span class="dashicons dashicons-search"></span><input id="lw-business-search" type="search" placeholder="Buscar negocio, encargado o correo" aria-label="Buscar negocios"></label>
		</div>

		<div class="lw-table-scroll">
			<table class="widefat fixed striped lw-businesses-table">
				<thead><tr><th>Negocio</th><th>Encargado</th><th>Clientes</th><th>Visitas</th><th>Puntos</th><th class="lw-table-actions">Acciones</th></tr></thead>
				<tbody>
				<?php if ( ! $businesses ) : ?>
					<tr><td colspan="6">Todavía no hay negocios en Loyalty Wallet.</td></tr>
				<?php else : ?>
					<?php foreach ( $businesses as $business ) : ?>
						<?php
						$view_url = add_query_arg(
							array( 'page' => 'loyalty-wallet-businesses', 'business_id' => $business['id'] ),
							admin_url( 'admin.php' )
						);
						$export_url = wp_nonce_url(
							admin_url( 'admin-post.php?action=loyalty_wallet_export_business_customers&business_id=' . $business['id'] ),
							'loyalty_wallet_export_business_customers'
						);
						$search_text = strtolower( remove_accents( $business['name'] . ' ' . $business['email'] . ' ' . $business['owner_name'] . ' ' . $business['owner_email'] ) );
						?>
						<tr class="lw-business-row" data-business-search="<?php echo esc_attr( $search_text ); ?>">
							<td data-label="Negocio"><div class="lw-business-identity"><?php if ( $business['logo_url'] ) : ?><img src="<?php echo esc_url( $business['logo_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-store"></span><?php endif; ?><span><strong><?php echo esc_html( $business['name'] ); ?></strong><small><?php echo esc_html( $business['email'] ); ?></small></span></div></td>
							<td data-label="Encargado"><strong><?php echo esc_html( $business['owner_name'] ); ?></strong><small><?php echo esc_html( $business['owner_email'] ); ?></small></td>
							<td data-label="Clientes"><strong><?php echo esc_html( $business['customer_count'] ); ?></strong></td>
							<td data-label="Visitas"><?php echo esc_html( $business['total_visits'] ); ?></td>
							<td data-label="Puntos"><?php echo esc_html( $business['total_points'] ); ?></td>
							<td data-label="Acciones" class="lw-table-actions"><a class="button" href="<?php echo esc_url( $view_url ); ?>">Ver clientes</a><a class="button" href="<?php echo esc_url( $export_url ); ?>"><span class="dashicons dashicons-download"></span>CSV</a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<p id="lw-business-search-empty" class="lw-business-search-empty" hidden>No hay negocios que coincidan con la búsqueda.</p>
	</div>

	<?php if ( $selected_business ) : ?>
		<?php
		$selected_export_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=loyalty_wallet_export_business_customers&business_id=' . $selected_business['id'] ),
			'loyalty_wallet_export_business_customers'
		);
		?>
		<section class="lw-businesses-card lw-business-customers" id="business-customers">
			<div class="lw-businesses-card-head">
				<div><span class="lw-businesses-eyebrow">CLIENTES DE</span><h2><?php echo esc_html( $selected_business['name'] ); ?></h2><p><?php echo esc_html( $selected_business['customer_count'] ); ?> clientes guardados.</p></div>
				<a class="button button-primary lw-export-button" href="<?php echo esc_url( $selected_export_url ); ?>"><span class="dashicons dashicons-download"></span>Exportar este negocio</a>
			</div>
			<label class="lw-business-search lw-customer-table-search"><span class="dashicons dashicons-search"></span><input id="lw-business-customer-search" type="search" placeholder="Buscar cliente por nombre, correo o teléfono" aria-label="Buscar clientes del negocio"></label>
			<div class="lw-table-scroll">
				<table class="widefat fixed striped lw-customer-export-table">
					<thead><tr><th>Cliente</th><th>Contacto</th><th>Calificación</th><th>Puntos</th><th>Última visita</th><th>Visitas</th><th>Origen</th></tr></thead>
					<tbody>
					<?php if ( ! $selected_business['customers'] ) : ?>
						<tr><td colspan="7">Este negocio todavía no tiene clientes.</td></tr>
					<?php else : ?>
						<?php foreach ( array_reverse( $selected_business['customers'] ) as $customer ) : ?>
							<?php
							$visits = isset( $customer['visits'] ) && is_array( $customer['visits'] )
								? $customer['visits']
								: array_filter( array( $customer['date'] ?? '' ) );
							$customer_search = strtolower( remove_accents( implode( ' ', array( $customer['name'] ?? '', $customer['email'] ?? '', $customer['phone'] ?? '' ) ) ) );
							?>
							<tr class="lw-business-customer-row" data-customer-search="<?php echo esc_attr( $customer_search ); ?>">
								<td data-label="Cliente"><strong><?php echo esc_html( $customer['name'] ?? '' ); ?></strong><small>ID: <?php echo esc_html( $customer['id'] ?? '' ); ?></small></td>
								<td data-label="Contacto"><a href="mailto:<?php echo esc_attr( $customer['email'] ?? '' ); ?>"><?php echo esc_html( $customer['email'] ?? '' ); ?></a><small><?php echo esc_html( $customer['phone'] ?? '' ); ?></small></td>
								<td data-label="Calificación"><span class="lw-table-stars"><?php echo esc_html( str_repeat( '★', absint( $customer['rating'] ?? 0 ) ) ); ?></span></td>
								<td data-label="Puntos"><strong><?php echo esc_html( absint( $customer['points'] ?? 0 ) ); ?></strong></td>
								<td data-label="Última visita"><?php echo esc_html( $customer['date'] ?? '—' ); ?></td>
								<td data-label="Visitas"><?php echo esc_html( count( $visits ) ); ?></td>
								<td data-label="Origen"><span class="lw-source-badge"><?php echo esc_html( 'google_wallet' === ( $customer['source'] ?? '' ) ? 'Google Wallet' : 'Reseña de Google' ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
			<p id="lw-business-customer-search-empty" class="lw-business-search-empty" hidden>No hay clientes que coincidan con la búsqueda.</p>
		</section>
	<?php endif; ?>
</div>
