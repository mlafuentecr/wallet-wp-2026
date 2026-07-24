<div id="lw-google-loyalty-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-google-loyalty-tab" hidden>
	<div class="lw-settings-heading">
		<h2>Google Loyalty settings</h2>
		<p>Configure the Google Wallet loyalty card, public enrollment page and secure issuer credentials.</p>
	</div>
	<section class="lw-google-settings-section lw-wallet-credentials">
		<div class="lw-google-status <?php echo $wallet['is_configured'] ? 'is-ready' : ''; ?>"><span class="lw-status-dot"></span><span><strong>Google Wallet API</strong><small><?php echo esc_html( $wallet['is_configured'] ? 'Ready to issue loyalty cards' : $wallet['configuration_error'] ); ?></small></span></div>
		<p class="lw-wallet-help">The QR opens a public review page. After posting the Google review, the customer can create and save a loyalty card.</p>
		<a class="button lw-google-profile-link" href="https://pay.google.com/business/console" target="_blank" rel="noopener noreferrer">Open Google Pay &amp; Wallet Console <span aria-hidden="true">↗</span></a>
		<label for="lw-wallet-public-url">Public landing URL</label><input id="lw-wallet-public-url" name="wallet_public_url" type="url" value="<?php echo esc_attr( $wallet['public_url'] ); ?>" placeholder="https://your-live-link.example/">
		<small>For phone scanning, use the production URL or your Local Live Link. A <code>.local</code> address only works on this computer.</small>
		<div class="lw-label-with-link"><label for="lw-wallet-issuer-id">Issuer ID</label><a href="https://pay.google.com/business/console" target="_blank" rel="noopener noreferrer">Open Wallet Console <span aria-hidden="true">↗</span></a></div><input id="lw-wallet-issuer-id" name="wallet_issuer_id" type="text" inputmode="numeric" value="<?php echo esc_attr( $wallet['issuer_id'] ); ?>" placeholder="3388000000022" <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>>
		<label for="lw-wallet-class-suffix">Loyalty class suffix</label><input id="lw-wallet-class-suffix" name="wallet_class_suffix" type="text" value="<?php echo esc_attr( $wallet['class_suffix'] ); ?>" placeholder="loyalty_wallet_1">
		<div class="lw-wallet-logo-row">
			<div class="lw-wallet-logo-upload">
				<div id="lw-wallet-logo-preview" class="lw-wallet-logo-preview"><?php if ( $wallet['logo_url'] ) : ?><img src="<?php echo esc_url( $wallet['logo_url'] ); ?>" alt="Google Wallet logo preview"><?php else : ?><span class="dashicons dashicons-format-image"></span><?php endif; ?></div>
				<div>
					<strong>Google Wallet program logo</strong>
					<p>Upload a PNG, JPG or WebP up to 5 MB. We will automatically fit new uploads into a 660 × 660 px PNG with Google's recommended 15% safe padding.</p>
					<input id="lw-google-wallet-logo-media-id" name="wallet_logo_media_id" type="hidden" value="">
					<input id="lw-wallet-logo-upload" name="wallet_logo_upload" type="file" accept="image/png,image/jpeg,image/webp" hidden>
					<div class="lw-logo-actions">
						<label class="button lw-wallet-upload-button" for="lw-wallet-logo-upload"><span class="dashicons dashicons-upload"></span> Upload logo</label>
						<button type="button" class="button lw-media-library-button" data-media-target="lw-google-wallet-logo-media-id" data-preview-target="lw-wallet-logo-preview" data-file-target="lw-wallet-logo-upload" data-clear-url="lw-wallet-logo-url"><span class="dashicons dashicons-admin-media"></span> Add from Media Library</button>
					</div>
				</div>
			</div>
			<div class="lw-wallet-logo-url-field">
				<label for="lw-wallet-logo-url">Or use a public HTTPS logo URL</label>
				<input id="lw-wallet-logo-url" name="wallet_logo_url" type="url" value="<?php echo esc_attr( $wallet['logo_url_input'] ); ?>" placeholder="https://example.com/logo.png">
				<small>Use this option when the logo is already hosted on a public HTTPS website.</small>
			</div>
		</div>
		<div class="lw-label-with-link"><label for="lw-wallet-service-email">Service account email</label><a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Open Service Accounts <span aria-hidden="true">↗</span></a></div><input id="lw-wallet-service-email" name="wallet_service_email" type="email" value="<?php echo esc_attr( $wallet['service_email'] ); ?>" placeholder="wallet-issuer@project.iam.gserviceaccount.com" <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>>
		<div class="lw-label-with-link"><label for="lw-wallet-private-key">Service account private key</label><a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Manage service account keys <span aria-hidden="true">↗</span></a></div><textarea id="lw-wallet-private-key" name="wallet_private_key" rows="5" placeholder="<?php echo $wallet['has_private_key'] ? 'Saved — paste a new private key only to replace it' : '-----BEGIN PRIVATE KEY-----'; ?>" autocomplete="new-password" <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>></textarea>
		<small class="lw-wallet-security-note">The private key is used only on the server to sign Google Wallet links and is never sent to customers.</small>
	</section>
</div>
