<?php
$export_all_url = wp_nonce_url(
	admin_url( 'admin-post.php?action=loyalty_wallet_export_business_customers' ),
	'loyalty_wallet_export_business_customers'
);
?>
<div class="wrap lw-businesses-wrap">
	<div class="lw-businesses-hero">
		<div>
			<span class="lw-businesses-eyebrow">LOYALTY WALLET ADMIN</span>
			<h1>Businesses</h1>
			<p>Review every business and keep its customer information separated and ready to export.</p>
		</div>
		<a class="button button-primary lw-export-button" href="<?php echo esc_url( $export_all_url ); ?>"><span class="dashicons dashicons-download"></span>Export all customers</a>
	</div>

	<div class="lw-businesses-summary">
		<div><span class="dashicons dashicons-store"></span><span><small>Total businesses</small><strong><?php echo esc_html( count( $businesses ) ); ?></strong></span></div>
		<div><span class="dashicons dashicons-groups"></span><span><small>Total customers</small><strong><?php echo esc_html( array_sum( array_column( $businesses, 'customer_count' ) ) ); ?></strong></span></div>
		<div><span class="dashicons dashicons-chart-bar"></span><span><small>Total visits</small><strong><?php echo esc_html( array_sum( array_column( $businesses, 'total_visits' ) ) ); ?></strong></span></div>
	</div>

	<div class="lw-businesses-card">
		<div class="lw-businesses-card-head">
			<div><h2>Business list</h2><p>Select a business to review and export its customers.</p></div>
			<label class="lw-business-search"><span class="dashicons dashicons-search"></span><input id="lw-business-search" type="search" placeholder="Search business or email" aria-label="Search businesses"></label>
		</div>

		<div class="lw-table-scroll">
			<table class="widefat fixed striped lw-businesses-table">
				<thead><tr><th>Business</th><th>Owner</th><th>Customers</th><th>Visits</th><th>Points</th><th class="lw-table-actions">Actions</th></tr></thead>
				<tbody>
				<?php if ( ! $businesses ) : ?>
					<tr><td colspan="6">No Loyalty Wallet businesses found.</td></tr>
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
							<td><div class="lw-business-identity"><?php if ( $business['logo_url'] ) : ?><img src="<?php echo esc_url( $business['logo_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-store"></span><?php endif; ?><span><strong><?php echo esc_html( $business['name'] ); ?></strong><small><?php echo esc_html( $business['email'] ); ?></small></span></div></td>
							<td><strong><?php echo esc_html( $business['owner_name'] ); ?></strong><small><?php echo esc_html( $business['owner_email'] ); ?></small></td>
							<td><strong><?php echo esc_html( $business['customer_count'] ); ?></strong></td>
							<td><?php echo esc_html( $business['total_visits'] ); ?></td>
							<td><?php echo esc_html( $business['total_points'] ); ?></td>
							<td class="lw-table-actions"><a class="button" href="<?php echo esc_url( $view_url ); ?>">View customers</a><a class="button" href="<?php echo esc_url( $export_url ); ?>"><span class="dashicons dashicons-download"></span>CSV</a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<p id="lw-business-search-empty" class="lw-business-search-empty" hidden>No businesses match your search.</p>
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
				<div><span class="lw-businesses-eyebrow">CUSTOMERS FOR</span><h2><?php echo esc_html( $selected_business['name'] ); ?></h2><p><?php echo esc_html( $selected_business['customer_count'] ); ?> saved customers.</p></div>
				<a class="button button-primary lw-export-button" href="<?php echo esc_url( $selected_export_url ); ?>"><span class="dashicons dashicons-download"></span>Export this business</a>
			</div>
			<label class="lw-business-search lw-customer-table-search"><span class="dashicons dashicons-search"></span><input id="lw-business-customer-search" type="search" placeholder="Search customer by name, email or phone" aria-label="Search business customers"></label>
			<div class="lw-table-scroll">
				<table class="widefat fixed striped lw-customer-export-table">
					<thead><tr><th>Customer</th><th>Contact</th><th>Rating</th><th>Points</th><th>Last visit</th><th>Visits</th><th>Source</th></tr></thead>
					<tbody>
					<?php if ( ! $selected_business['customers'] ) : ?>
						<tr><td colspan="7">This business has no customers yet.</td></tr>
					<?php else : ?>
						<?php foreach ( array_reverse( $selected_business['customers'] ) as $customer ) : ?>
							<?php
							$visits = isset( $customer['visits'] ) && is_array( $customer['visits'] )
								? $customer['visits']
								: array_filter( array( $customer['date'] ?? '' ) );
							$customer_search = strtolower( remove_accents( implode( ' ', array( $customer['name'] ?? '', $customer['email'] ?? '', $customer['phone'] ?? '' ) ) ) );
							?>
							<tr class="lw-business-customer-row" data-customer-search="<?php echo esc_attr( $customer_search ); ?>">
								<td><strong><?php echo esc_html( $customer['name'] ?? '' ); ?></strong><small>ID: <?php echo esc_html( $customer['id'] ?? '' ); ?></small></td>
								<td><a href="mailto:<?php echo esc_attr( $customer['email'] ?? '' ); ?>"><?php echo esc_html( $customer['email'] ?? '' ); ?></a><small><?php echo esc_html( $customer['phone'] ?? '' ); ?></small></td>
								<td><span class="lw-table-stars"><?php echo esc_html( str_repeat( '★', absint( $customer['rating'] ?? 0 ) ) ); ?></span></td>
								<td><strong><?php echo esc_html( absint( $customer['points'] ?? 0 ) ); ?></strong></td>
								<td><?php echo esc_html( $customer['date'] ?? '—' ); ?></td>
								<td><?php echo esc_html( count( $visits ) ); ?></td>
								<td><span class="lw-source-badge"><?php echo esc_html( 'google_wallet' === ( $customer['source'] ?? '' ) ? 'Google Wallet' : 'Google Review' ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
			<p id="lw-business-customer-search-empty" class="lw-business-search-empty" hidden>No customers match your search.</p>
		</section>
	<?php endif; ?>
</div>
