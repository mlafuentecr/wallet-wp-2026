<?php
$status_labels = array(
	'active'    => 'Activa',
	'due'       => 'Por vencer',
	'overdue'   => 'Vencida',
	'suspended' => 'Suspendida',
	'archived'  => 'Archivada',
);
$status_counts = array_fill_keys( array_keys( $status_labels ), 0 );
$mrr = 0.0;
$due_amount = 0.0;
$new_this_month = 0;
$current_month = current_time( 'Y-m' );
foreach ( $businesses as $business ) {
	$status_counts[ $business['billing_status'] ]++;
	if ( in_array( $business['billing_status'], array( 'active', 'due', 'overdue' ), true ) ) {
		$mrr += $business['monthly_price'];
	}
	if ( 'due' === $business['billing_status'] ) {
		$due_amount += $business['monthly_price'];
	}
	if ( 0 === strpos( $business['started_at'], $current_month ) ) {
		$new_this_month++;
	}
}
$total_accounts = count( $businesses );
$cancellation_rate = $total_accounts ? ( $status_counts['archived'] / $total_accounts ) * 100 : 0;
$export_accounts_url = wp_nonce_url(
	admin_url( 'admin-post.php?action=loyalty_wallet_export_business_accounts' ),
	'loyalty_wallet_export_business_accounts'
);
$notice_messages = array(
	'negocio_creado'      => array( 'success', 'El negocio fue creado correctamente.' ),
	'negocio_activado'    => array( 'success', 'El negocio fue activado.' ),
	'negocio_suspendido'  => array( 'warning', 'El acceso del negocio fue suspendido.' ),
	'negocio_archivado'   => array( 'success', 'El negocio fue archivado.' ),
	'negocio_actualizado' => array( 'success', 'La información del negocio fue actualizada.' ),
	'datos_invalidos'     => array( 'error', 'Revisa los datos obligatorios, el plan, el precio, los teléfonos y las fechas.' ),
	'usuario_existente'   => array( 'error', 'Ese usuario o correo ya está registrado en WordPress.' ),
	'error_creacion'      => array( 'error', 'No se pudo crear el negocio.' ),
	'estado_invalido'     => array( 'error', 'No se pudo cambiar el estado del negocio.' ),
);
?>
<div class="wrap lw-businesses-wrap">
	<header class="lw-accounts-heading">
		<div>
			<span class="lw-businesses-eyebrow">ADMINISTRACIÓN DE LOYALTY WALLET</span>
			<h1>Cuentas de negocios</h1>
			<p>Controla suscripciones, cobros y acceso a Google Wallet desde un solo lugar.</p>
		</div>
		<a class="button button-primary lw-new-business-button" href="#nuevo-negocio">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span>Agregar negocio</span>
		</a>
	</header>

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

	<section class="lw-account-stats" aria-label="Resumen de cuentas">
		<?php
		$summary_cards = array(
			array( 'total', 'groups', 'Total cuentas', $total_accounts, 'Todas las cuentas' ),
			array( 'active', 'yes-alt', 'Activas', $status_counts['active'], 'Clientes al día' ),
			array( 'due', 'clock', 'Por vencer', $status_counts['due'], 'Próximos 7 días' ),
			array( 'overdue', 'warning', 'Vencidas', $status_counts['overdue'], 'Pago retrasado' ),
			array( 'suspended', 'controls-pause', 'Suspendidas', $status_counts['suspended'], 'Acceso desactivado' ),
			array( 'archived', 'archive', 'Archivadas', $status_counts['archived'], 'Ya no son clientes' ),
		);
		foreach ( $summary_cards as $card ) :
			?>
			<article class="lw-stat-card is-<?php echo esc_attr( $card[0] ); ?>">
				<span class="lw-stat-icon dashicons dashicons-<?php echo esc_attr( $card[1] ); ?>" aria-hidden="true"></span>
				<span><small><?php echo esc_html( $card[2] ); ?></small><strong><?php echo esc_html( $card[3] ); ?></strong><em><?php echo esc_html( $card[4] ); ?></em></span>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="lw-revenue-strip" aria-label="Resumen financiero">
		<div><small>MRR</small><strong>$<?php echo esc_html( number_format( $mrr, 2 ) ); ?></strong><span>Ingreso mensual recurrente</span></div>
		<div><small>ARR</small><strong>$<?php echo esc_html( number_format( $mrr * 12, 2 ) ); ?></strong><span>Ingreso anual recurrente</span></div>
		<div class="is-warning"><small>Cobranza por vencer</small><strong>$<?php echo esc_html( number_format( $due_amount, 2 ) ); ?></strong><span>Próximos 7 días</span></div>
		<div class="is-danger"><small>Tasa de cancelación</small><strong><?php echo esc_html( number_format( $cancellation_rate, 1 ) ); ?>%</strong><span>Sobre todas las cuentas</span></div>
		<div class="is-success"><small>Nuevos clientes</small><strong><?php echo esc_html( $new_this_month ); ?></strong><span>Este mes</span></div>
	</section>

	<details class="lw-businesses-card lw-create-business" id="nuevo-negocio" <?php echo in_array( $notice, array( 'datos_invalidos', 'usuario_existente', 'error_creacion' ), true ) ? 'open' : ''; ?>>
		<summary><span><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span><strong>Agregar un nuevo negocio</strong><small>Crea la cuenta, suscripción y acceso independiente.</small></span></span><i aria-hidden="true"></i></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-create-business-form">
			<input type="hidden" name="action" value="loyalty_wallet_create_business">
			<?php wp_nonce_field( 'loyalty_wallet_create_business' ); ?>
			<div class="lw-business-form-grid">
				<label><span>Nombre del negocio *</span><input name="business_name" type="text" maxlength="120" placeholder="Café Central" required></label>
				<label><span>Nombre del encargado *</span><input name="owner_name" type="text" maxlength="120" placeholder="María Pérez" required></label>
				<label><span>Correo del encargado *</span><input name="owner_email" type="email" autocomplete="off" placeholder="maria@ejemplo.com" required></label>
				<label><span>Teléfono del encargado *</span><input name="manager_phone" type="tel" placeholder="+506 8888 8888" required><small>Incluye el código de país.</small></label>
				<label><span>Usuario de acceso *</span><input name="owner_username" type="text" maxlength="60" autocomplete="off" placeholder="cafe-central" required></label>
				<label><span>Correo del negocio</span><input name="business_email" type="email" placeholder="info@cafecentral.com"></label>
				<label><span>Plan *</span><select name="business_plan" required><option value="starter">Starter</option><option value="pro">Pro</option><option value="enterprise">Empresa</option></select></label>
				<label><span>Precio mensual *</span><input name="business_monthly_price" type="number" min="0" max="1000000" step="0.01" value="19.00" required></label>
				<label><span>Fecha de inicio *</span><input name="business_started_at" type="date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label>
				<label><span>Siguiente pago *</span><input name="business_next_payment" type="date" required></label>
				<label><span>Último pago</span><input name="business_last_payment" type="date"></label>
				<label><span>WhatsApp del negocio</span><input name="business_whatsapp" type="tel" placeholder="+506 8888 8888"></label>
				<label class="lw-business-form-wide"><span>Sitio web</span><input name="business_website" type="url" placeholder="https://cafecentral.com"></label>
			</div>
			<div class="lw-create-business-actions"><button type="submit" class="button button-primary"><span class="dashicons dashicons-store" aria-hidden="true"></span><span>Crear negocio</span></button><p>Se generará una contraseña temporal y el negocio solo podrá entrar a Loyalty Wallet.</p></div>
		</form>
	</details>

	<section class="lw-accounts-panel">
		<div class="lw-account-toolbar">
			<label class="lw-business-search"><span class="dashicons dashicons-search" aria-hidden="true"></span><input id="lw-business-search" type="search" placeholder="Buscar cliente, negocio o correo" aria-label="Buscar negocios"></label>
			<label class="lw-filter-control"><small>Estado</small><select id="lw-business-status-filter"><option value="">Todos</option><?php foreach ( $status_labels as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
			<label class="lw-filter-control"><small>Plan</small><select id="lw-business-plan-filter"><option value="">Todos</option><option value="starter">Starter</option><option value="pro">Pro</option><option value="enterprise">Empresa</option></select></label>
			<label class="lw-filter-control lw-sort-control"><small>Ordenar por</small><select id="lw-business-sort"><option value="next">Próxima facturación</option><option value="name">Nombre</option><option value="recent">Más recientes</option><option value="price">Mayor precio</option></select></label>
			<a class="button lw-export-button" href="<?php echo esc_url( $export_accounts_url ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><span>Exportar</span></a>
		</div>

		<div class="lw-table-scroll">
			<table class="widefat lw-businesses-table">
				<thead><tr><th>Cliente</th><th>Plan</th><th>Precio</th><th>Próxima facturación</th><th>Estado</th><th>Último pago</th><th>Wallet</th><th class="lw-table-actions">Acciones</th></tr></thead>
				<tbody id="lw-business-table-body">
				<?php if ( ! $businesses ) : ?>
					<tr class="lw-empty-table-row"><td colspan="8">Todavía no hay cuentas de negocios.</td></tr>
				<?php else : ?>
					<?php foreach ( $businesses as $business ) : ?>
						<?php
						$status = $business['billing_status'];
						$days = $business['days_until_payment'];
						$payment_note = '';
						if ( null !== $days ) {
							$payment_note = $days < 0 ? abs( $days ) . ' días vencido' : ( 0 === $days ? 'Vence hoy' : 'en ' . $days . ' días' );
						}
						$search_text = strtolower( remove_accents( implode( ' ', array( $business['name'], $business['email'], $business['owner_name'], $business['owner_email'], $business['manager_phone'] ) ) ) );
						?>
						<tr class="lw-business-row is-<?php echo esc_attr( $status ); ?>"
							data-business-search="<?php echo esc_attr( $search_text ); ?>"
							data-business-status="<?php echo esc_attr( $status ); ?>"
							data-business-plan="<?php echo esc_attr( $business['plan'] ); ?>"
							data-business-name="<?php echo esc_attr( strtolower( remove_accents( $business['name'] ) ) ); ?>"
							data-business-next="<?php echo esc_attr( $business['next_payment'] ?: '9999-12-31' ); ?>"
							data-business-started="<?php echo esc_attr( $business['started_at'] ); ?>"
							data-business-price="<?php echo esc_attr( $business['monthly_price'] ); ?>">
							<td data-label="Cliente">
								<div class="lw-business-identity">
									<?php if ( $business['logo_url'] ) : ?><img src="<?php echo esc_url( $business['logo_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-store" aria-hidden="true"></span><?php endif; ?>
									<span><strong><?php echo esc_html( $business['name'] ); ?></strong><small><?php echo esc_html( $business['owner_email'] ); ?></small></span>
								</div>
							</td>
							<td data-label="Plan"><span class="lw-plan-badge is-<?php echo esc_attr( $business['plan'] ); ?>"><?php echo esc_html( $business['plan_label'] ); ?></span><small>Mensual</small></td>
							<td data-label="Precio"><strong>$<?php echo esc_html( number_format( $business['monthly_price'], 2 ) ); ?></strong><small>por mes</small></td>
							<td data-label="Próxima facturación" class="<?php echo in_array( $status, array( 'due', 'overdue' ), true ) ? 'lw-payment-alert' : ''; ?>">
								<?php if ( $business['next_payment'] ) : ?><time datetime="<?php echo esc_attr( $business['next_payment'] ); ?>"><?php echo esc_html( wp_date( 'd M Y', strtotime( $business['next_payment'] ) ) ); ?></time><small><?php echo esc_html( $payment_note ); ?></small><?php else : ?><span class="lw-empty-value">—</span><?php endif; ?>
							</td>
							<td data-label="Estado"><span class="lw-business-status is-<?php echo esc_attr( $status ); ?>"><span></span><?php echo esc_html( $status_labels[ $status ] ); ?></span></td>
							<td data-label="Último pago"><?php if ( $business['last_payment'] ) : ?><time datetime="<?php echo esc_attr( $business['last_payment'] ); ?>"><?php echo esc_html( wp_date( 'd M Y', strtotime( $business['last_payment'] ) ) ); ?></time><small>$<?php echo esc_html( number_format( $business['monthly_price'], 2 ) ); ?></small><?php else : ?><span class="lw-empty-value">—</span><?php endif; ?></td>
							<td data-label="Wallet"><span class="lw-wallet-status <?php echo $business['wallet_active'] ? 'is-active' : 'is-inactive'; ?>"><?php echo $business['wallet_active'] ? 'Activo' : 'Inactivo'; ?></span></td>
							<td data-label="Acciones" class="lw-table-actions">
								<div class="lw-action-menu">
									<button type="button" class="lw-action-menu-toggle" aria-expanded="false" aria-label="Acciones de <?php echo esc_attr( $business['name'] ); ?>"><span class="dashicons dashicons-ellipsis" aria-hidden="true"></span></button>
									<div class="lw-action-menu-panel" hidden>
										<button type="button" class="lw-edit-business-toggle" aria-expanded="false" aria-controls="editar-negocio-<?php echo esc_attr( $business['id'] ); ?>"><span class="dashicons dashicons-edit"></span>Editar detalles</button>
										<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $business['id'] ) ); ?>"><span class="dashicons dashicons-admin-users"></span>Ver acceso</a>
										<hr>
										<?php if ( 'suspended' === $business['status'] ) : ?>
											<?php self::status_form( $business['id'], 'active', 'yes-alt', 'Reactivar acceso', 'is-reactivate' ); ?>
										<?php else : ?>
											<?php self::status_form( $business['id'], 'suspended', 'controls-pause', 'Suspender acceso', 'is-suspend' ); ?>
										<?php endif; ?>
										<?php if ( 'archived' !== $business['status'] ) : ?>
											<?php self::status_form( $business['id'], 'archived', 'archive', 'Archivar cliente', 'is-archive' ); ?>
										<?php else : ?>
											<?php self::status_form( $business['id'], 'active', 'yes-alt', 'Restaurar cliente', 'is-reactivate' ); ?>
										<?php endif; ?>
									</div>
								</div>
							</td>
						</tr>
						<tr id="editar-negocio-<?php echo esc_attr( $business['id'] ); ?>" class="lw-business-edit-row" hidden>
							<td colspan="8">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lw-inline-business-form">
									<input type="hidden" name="action" value="loyalty_wallet_update_business">
									<input type="hidden" name="business_id" value="<?php echo esc_attr( $business['id'] ); ?>">
									<?php wp_nonce_field( 'loyalty_wallet_update_business' ); ?>
									<label><span>Negocio</span><input name="business_name" type="text" value="<?php echo esc_attr( $business['name'] ); ?>" required></label>
									<label><span>Encargado</span><input name="owner_name" type="text" value="<?php echo esc_attr( $business['owner_name'] ); ?>" required></label>
									<label><span>Teléfono</span><input name="manager_phone" type="tel" value="<?php echo esc_attr( $business['manager_phone'] ); ?>" required></label>
									<label><span>Plan</span><select name="business_plan"><option value="starter" <?php selected( $business['plan'], 'starter' ); ?>>Starter</option><option value="pro" <?php selected( $business['plan'], 'pro' ); ?>>Pro</option><option value="enterprise" <?php selected( $business['plan'], 'enterprise' ); ?>>Empresa</option></select></label>
									<label><span>Precio mensual</span><input name="business_monthly_price" type="number" min="0" step="0.01" value="<?php echo esc_attr( number_format( $business['monthly_price'], 2, '.', '' ) ); ?>" required></label>
									<label><span>Inicio</span><input name="business_started_at" type="date" value="<?php echo esc_attr( $business['started_at'] ); ?>" required></label>
									<label><span>Siguiente pago</span><input name="business_next_payment" type="date" value="<?php echo esc_attr( $business['next_payment'] ); ?>" required></label>
									<label><span>Último pago</span><input name="business_last_payment" type="date" value="<?php echo esc_attr( $business['last_payment'] ); ?>"></label>
									<div class="lw-inline-business-actions"><button type="submit" class="button button-primary">Guardar cambios</button><button type="button" class="button lw-cancel-business-edit">Cancelar</button></div>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<footer class="lw-table-footer"><span id="lw-business-result-count">Mostrando <?php echo esc_html( $total_accounts ); ?> de <?php echo esc_html( $total_accounts ); ?> cuentas</span></footer>
		<p id="lw-business-search-empty" class="lw-business-search-empty" hidden>No hay cuentas que coincidan con los filtros.</p>
	</section>
</div>
