<div id="lw-google-loyalty-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-google-loyalty-tab" hidden>
	<div class="lw-settings-heading">
		<h2>Google Loyalty settings</h2>
		<p>Configure the Google Wallet loyalty card, public enrollment page and secure issuer credentials.</p>
	</div>
	<details class="lw-credentials-toggle lw-loyalty-settings-toggle" <?php echo isset( $_GET['lw_notice'], $_GET['lw_tab'] ) && 'google-loyalty' === sanitize_key( wp_unslash( $_GET['lw_tab'] ) ) && ( ! isset( $_GET['lw_section'] ) || 'configuration' === sanitize_key( wp_unslash( $_GET['lw_section'] ) ) ) ? 'open' : ''; ?>>
		<summary>
			<span><strong>Google Loyalty configuration</strong><small>Google Wallet card, public enrollment, issuer and service account</small></span>
			<i aria-hidden="true"></i>
		</summary>
		<div class="lw-loyalty-settings-content">
			<section class="lw-google-settings-section lw-wallet-credentials">
				<div class="lw-google-status <?php echo $wallet['is_configured'] ? 'is-ready' : ''; ?>"><span class="lw-status-dot"></span><span><strong>Google Wallet API</strong><small><?php echo esc_html( $wallet['is_configured'] ? 'Ready to issue loyalty cards' : $wallet['configuration_error'] ); ?></small></span></div>
				<p class="lw-wallet-help">The QR opens a public review page. After posting the Google review, the customer can create and save a loyalty card.</p>
				<a class="button lw-google-profile-link" href="https://pay.google.com/business/console" target="_blank" rel="noopener noreferrer">Open Google Pay &amp; Wallet Console <span aria-hidden="true">↗</span></a>
				<label for="lw-wallet-public-url">Public landing URL</label><input id="lw-wallet-public-url" name="wallet_public_url" type="url" value="<?php echo esc_attr( $wallet['public_url'] ); ?>" placeholder="https://your-live-link.example/">
				<small>For phone scanning, use the production URL or your Local Live Link. A <code>.local</code> address only works on this computer.</small>
				<div class="lw-label-with-link"><label for="lw-wallet-issuer-id">Issuer ID</label><a href="https://pay.google.com/business/console" target="_blank" rel="noopener noreferrer">Open Wallet Console <span aria-hidden="true">↗</span></a></div><input id="lw-wallet-issuer-id" name="wallet_issuer_id" type="text" inputmode="numeric" value="<?php echo esc_attr( $wallet['issuer_id'] ); ?>" placeholder="3388000000022" <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>>
				<label for="lw-wallet-class-suffix">Loyalty class suffix</label><input id="lw-wallet-class-suffix" name="wallet_class_suffix" type="text" value="<?php echo esc_attr( $wallet['class_suffix'] ); ?>" placeholder="loyalty_wallet_1">
				<div class="lw-label-with-link"><label for="lw-wallet-service-email">Service account email</label><a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Open Service Accounts <span aria-hidden="true">↗</span></a></div><input id="lw-wallet-service-email" name="wallet_service_email" type="email" value="<?php echo esc_attr( $wallet['service_email'] ); ?>" placeholder="wallet-issuer@project.iam.gserviceaccount.com" <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>>
				<div class="lw-label-with-link"><label for="lw-wallet-service-account-json">Service account private key JSON</label><a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Manage service account keys <span aria-hidden="true">↗</span></a></div>
				<div class="lw-service-account-json">
					<input id="lw-wallet-service-account-json" name="wallet_service_account_json" type="file" accept="application/json,.json" hidden <?php echo $wallet['uses_constants'] ? 'data-lw-locked="1" disabled' : ''; ?>>
					<label class="button lw-wallet-json-upload-button" for="lw-wallet-service-account-json"><span class="dashicons dashicons-upload"></span><?php echo $wallet['has_private_key'] ? 'Replace credentials JSON' : 'Upload credentials JSON'; ?></label>
					<span id="lw-wallet-service-account-json-name" class="lw-service-account-json-name"><?php echo $wallet['has_private_key'] ? 'Credentials saved' : 'No JSON uploaded'; ?></span>
				</div>
				<small class="lw-wallet-security-note">Upload the JSON downloaded from Google Cloud. The plugin validates it, saves only the service account email and private key, and does not retain the uploaded JSON file. Maximum 1 MB.</small>
			</section>
			<div class="lw-loyalty-settings-actions">
				<button type="submit" class="button button-primary" name="wallet_subsection" value="configuration">Save Loyalty settings</button>
			</div>
		</div>
	</details>
	<details class="lw-credentials-toggle lw-loyalty-settings-toggle lw-wallet-design-toggle" <?php echo isset( $_GET['lw_notice'], $_GET['lw_tab'], $_GET['lw_section'] ) && 'google-loyalty' === sanitize_key( wp_unslash( $_GET['lw_tab'] ) ) && 'design' === sanitize_key( wp_unslash( $_GET['lw_section'] ) ) ? 'open' : ''; ?>>
		<summary>
			<span><strong>Design</strong><small>Card color, program logo and full-width banner</small></span>
			<i aria-hidden="true"></i>
		</summary>
		<div class="lw-loyalty-settings-content">
			<section class="lw-google-settings-section">
				<div class="lw-wallet-design-section" aria-labelledby="lw-wallet-design-title">
					<?php
					$preview_wallet_name = (string) get_user_meta( get_current_user_id(), '_loyalty_wallet_name', true );
					$preview_wallet_name = $preview_wallet_name ?: 'Loyalty Wallet';
					$preview_program_name = $wallet['program_name'];
					$preview_customers   = Loyalty_Wallet_Customers_Module::all( get_current_user_id() );
					$preview_customer    = $preview_customers ? end( $preview_customers ) : array();
					$preview_name        = (string) ( $preview_customer['name'] ?? 'Nombre del cliente' );
					$preview_points      = absint( $preview_customer['points'] ?? Loyalty_Wallet_Google_Reviews_Module::review_points( get_current_user_id() ) );
					$preview_next_visit  = ! empty( $preview_customer['next_visit'] ) ? (string) $preview_customer['next_visit'] : 'No programada';
					$preview_qr_value    = $preview_customer ? Loyalty_Wallet_Rewards_Module::barcode_payload( get_current_user_id(), $preview_customer ) : 'LOYALTY-WALLET-PREVIEW';
					$preview_qr_url      = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' . rawurlencode( $preview_qr_value );
					?>
					<div class="lw-wallet-design-heading">
						<div><h3 id="lw-wallet-design-title">Google Wallet card design</h3><p>Edit the controls on the left and preview the card on the right.</p></div>
					</div>
					<div class="lw-wallet-design-workspace">
						<div class="lw-wallet-design-controls">
							<section class="lw-design-control-card">
								<label for="lw-wallet-program-name">Loyalty program name</label>
								<input id="lw-wallet-program-name" name="wallet_program_name" type="text" value="<?php echo esc_attr( $preview_program_name ); ?>" placeholder="Croc's Rewards" maxlength="120" required>
								<small>This is the main title shown on the Google Wallet card.</small>
							</section>
							<section class="lw-design-control-card">
								<label for="lw-wallet-contact-help">Contact instruction</label>
								<textarea id="lw-wallet-contact-help" name="wallet_contact_help" maxlength="160" rows="3" required><?php echo esc_textarea( $wallet['contact_help'] ); ?></textarea>
								<small>Shown under “Contacto” on the Google Wallet card.</small>
							</section>
							<section class="lw-design-control-card">
								<label class="lw-wallet-color-field" for="lw-wallet-background-color"><span>Card color</span><span><input id="lw-wallet-background-color" name="wallet_background_color" type="color" value="<?php echo esc_attr( $wallet['background_color_input'] ); ?>"><code id="lw-wallet-background-color-value"><?php echo esc_html( strtoupper( $wallet['background_color_input'] ) ); ?></code></span></label>
							</section>
							<section class="lw-design-control-card">
								<div class="lw-wallet-logo-upload">
									<div id="lw-wallet-logo-preview" class="lw-wallet-logo-preview"><?php if ( $wallet['logo_url'] ) : ?><img src="<?php echo esc_url( $wallet['logo_url'] ); ?>" alt="Google Wallet logo preview"><?php else : ?><span class="dashicons dashicons-format-image"></span><?php endif; ?></div>
									<div>
										<strong>Google Wallet program logo</strong>
										<p>Square PNG, minimum 660 × 660 px, with 15% safe padding. Maximum 5 MB.</p>
										<input id="lw-google-wallet-logo-media-id" name="wallet_logo_media_id" type="hidden" value="">
										<input id="lw-wallet-logo-upload" name="wallet_logo_upload" type="file" accept="image/png,image/jpeg,image/webp" hidden>
										<div class="lw-logo-actions">
											<label class="button lw-wallet-upload-button" for="lw-wallet-logo-upload"><span class="dashicons dashicons-upload"></span> Upload logo</label>
											<button type="button" class="button lw-media-library-button" data-media-target="lw-google-wallet-logo-media-id" data-preview-target="lw-wallet-logo-preview" data-file-target="lw-wallet-logo-upload" data-clear-url="lw-wallet-logo-url" data-media-title="Choose a Google Wallet logo" data-media-button="Use this logo"><span class="dashicons dashicons-admin-media"></span> Media Library</button>
										</div>
									</div>
								</div>
								<div class="lw-wallet-logo-url-field">
									<label for="lw-wallet-logo-url">Public HTTPS logo URL</label>
									<input id="lw-wallet-logo-url" name="wallet_logo_url" type="url" value="<?php echo esc_attr( $wallet['logo_url_input'] ); ?>" placeholder="https://example.com/logo.png">
								</div>
							</section>
							<section class="lw-design-control-card lw-design-banner-control">
								<div id="lw-wallet-hero-preview" class="lw-wallet-hero-preview"><?php if ( $wallet['hero_url'] ) : ?><img src="<?php echo esc_url( $wallet['hero_url'] ); ?>" alt="Google Wallet banner preview"><?php else : ?><span class="dashicons dashicons-format-image"></span><small>No banner selected</small><?php endif; ?></div>
								<div class="lw-wallet-hero-controls">
									<div class="lw-wallet-hero-title"><strong>Card banner</strong><?php if ( 'random' === $wallet['hero_mode'] ) : ?><span id="lw-wallet-random-badge">Random banner</span><?php else : ?><span id="lw-wallet-random-badge" hidden>Random banner</span><?php endif; ?></div>
									<p>Recommended: PNG 1032 × 812 px, approximately 5:4, no embedded text. Maximum 5 MB.</p>
									<input id="lw-wallet-hero-mode" name="wallet_hero_mode" type="hidden" value="<?php echo esc_attr( $wallet['hero_mode'] ); ?>">
									<input id="lw-wallet-hero-random-seed" name="wallet_hero_random_seed" type="hidden" value="<?php echo esc_attr( $wallet['hero_random_seed'] ); ?>">
									<input id="lw-google-wallet-hero-media-id" name="wallet_hero_media_id" type="hidden" value="">
									<input id="lw-wallet-hero-upload" name="wallet_hero_upload" type="file" accept="image/png,image/jpeg,image/webp" hidden>
									<div class="lw-logo-actions">
										<label class="button lw-wallet-upload-button" for="lw-wallet-hero-upload"><span class="dashicons dashicons-upload"></span> Upload banner</label>
										<button type="button" class="button lw-media-library-button" data-media-target="lw-google-wallet-hero-media-id" data-preview-target="lw-wallet-hero-preview" data-file-target="lw-wallet-hero-upload" data-clear-url="lw-wallet-hero-url" data-mode-target="lw-wallet-hero-mode" data-media-title="Choose a Google Wallet banner" data-media-button="Use this banner"><span class="dashicons dashicons-admin-media"></span> Media Library</button>
										<button id="lw-wallet-random-hero" type="button" class="button" data-random-base="https://picsum.photos/seed/"><span class="dashicons dashicons-image-rotate"></span> Random</button>
									</div>
									<label for="lw-wallet-hero-url">Public HTTPS banner URL</label>
									<input id="lw-wallet-hero-url" name="wallet_hero_url" type="url" value="<?php echo esc_attr( $wallet['hero_url_input'] ); ?>" placeholder="https://example.com/banner.png">
								</div>
							</section>
						</div>
						<aside class="lw-wallet-live-preview" aria-label="Google Wallet card preview">
							<div class="lw-live-preview-heading"><span><strong>Live preview</strong><small>Approximate Google Wallet appearance</small></span><span class="dashicons dashicons-smartphone"></span></div>
							<div id="lw-wallet-card-preview" class="lw-wallet-card-preview" style="--lw-card-color:<?php echo esc_attr( $wallet['background_color_input'] ); ?>">
								<div class="lw-card-preview-body">
									<div class="lw-card-preview-brand"><span id="lw-card-preview-logo"><?php if ( $wallet['logo_url'] ) : ?><img src="<?php echo esc_url( $wallet['logo_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-store"></span><?php endif; ?></span><strong>[TEST ONLY] <?php echo esc_html( $preview_wallet_name ); ?></strong></div>
									<h4 id="lw-card-preview-program-name"><?php echo esc_html( $preview_program_name ); ?></h4>
									<div class="lw-card-preview-fields"><span><small>Nombre</small><strong><?php echo esc_html( $preview_name ); ?></strong></span><span><small>Próxima visita</small><strong><?php echo esc_html( $preview_next_visit ); ?></strong></span></div>
									<div class="lw-card-preview-contact"><small>Contacto</small><strong id="lw-card-preview-contact-help"><?php echo esc_html( $wallet['contact_help'] ); ?></strong></div>
									<div class="lw-card-preview-qr"><img src="<?php echo esc_url( $preview_qr_url ); ?>" alt="Preview QR code"><strong><?php echo esc_html( $preview_points ); ?> puntos</strong></div>
								</div>
								<div id="lw-card-preview-banner" class="lw-card-preview-banner"><?php if ( $wallet['hero_url'] ) : ?><img src="<?php echo esc_url( $wallet['hero_url'] ); ?>" alt=""><?php endif; ?></div>
							</div>
							<p>Google may adjust typography and spacing depending on the device.</p>
						</aside>
					</div>
				</div>
			</section>
			<div class="lw-loyalty-settings-actions">
				<button type="submit" class="button button-primary" name="wallet_subsection" value="design">Save Design</button>
			</div>
		</div>
	</details>
	<details class="lw-credentials-toggle lw-loyalty-settings-toggle lw-wallet-promotions-toggle" <?php echo isset( $_GET['lw_notice'], $_GET['lw_tab'], $_GET['lw_section'] ) && 'google-loyalty' === sanitize_key( wp_unslash( $_GET['lw_tab'] ) ) && 'promotions' === sanitize_key( wp_unslash( $_GET['lw_section'] ) ) ? 'open' : ''; ?>>
		<summary>
			<span><strong>Promotions &amp; appointments</strong><small>Clickable promotion and booking action for the Google Wallet card</small></span>
			<i aria-hidden="true"></i>
		</summary>
		<div class="lw-loyalty-settings-content">
			<section class="lw-google-settings-section">
				<div class="lw-wallet-promo-workspace">
					<div class="lw-wallet-promo-controls">
						<section class="lw-promo-control-card">
							<label class="lw-feature-check" for="lw-wallet-promo-enabled">
								<input id="lw-wallet-promo-enabled" name="wallet_promo_enabled" type="checkbox" value="1" <?php checked( $wallet['promo_enabled'] ); ?>>
								<span><strong>Show promotion</strong><small>Add a clickable promotion to every loyalty card.</small></span>
							</label>
							<div class="lw-promo-fields">
								<label for="lw-wallet-promo-title">Promotion title<input id="lw-wallet-promo-title" name="wallet_promo_title" type="text" value="<?php echo esc_attr( $wallet['promo_title'] ); ?>" maxlength="60" placeholder="Promociones"></label>
								<label for="lw-wallet-promo-body">Short description<input id="lw-wallet-promo-body" name="wallet_promo_body" type="text" value="<?php echo esc_attr( $wallet['promo_body'] ); ?>" maxlength="50" placeholder="Revisa promociones disponibles"></label>
								<label for="lw-wallet-promo-url">Promotion destination URL<input id="lw-wallet-promo-url" name="wallet_promo_url" type="url" value="<?php echo esc_attr( $wallet['promo_url'] ); ?>" placeholder="https://example.com/promociones"></label>
							</div>
							<div class="lw-promo-image-editor">
								<div id="lw-wallet-promo-image-preview" class="lw-promo-image-preview"><?php if ( $wallet['promo_image_url'] ) : ?><img src="<?php echo esc_url( $wallet['promo_image_url'] ); ?>" alt="Promotion preview"><?php else : ?><span class="dashicons dashicons-format-image"></span><?php endif; ?></div>
								<div>
									<strong>Promotion image</strong>
									<p>Recommended: square PNG, JPG or WebP, minimum 660 × 660 px. Maximum 5 MB.</p>
									<input id="lw-wallet-promo-image-media-id" name="wallet_promo_image_media_id" type="hidden" value="">
									<input id="lw-wallet-promo-image-upload" name="wallet_promo_image_upload" type="file" accept="image/png,image/jpeg,image/webp" hidden>
									<div class="lw-logo-actions">
										<label class="button lw-wallet-upload-button" for="lw-wallet-promo-image-upload"><span class="dashicons dashicons-upload"></span> Upload image</label>
										<button type="button" class="button lw-media-library-button" data-media-target="lw-wallet-promo-image-media-id" data-preview-target="lw-wallet-promo-image-preview" data-file-target="lw-wallet-promo-image-upload" data-clear-url="lw-wallet-promo-image-url" data-media-title="Choose a promotion image" data-media-button="Use this image"><span class="dashicons dashicons-admin-media"></span> Media Library</button>
									</div>
								</div>
							</div>
							<label for="lw-wallet-promo-image-url">Public HTTPS promotion image URL</label>
							<input id="lw-wallet-promo-image-url" name="wallet_promo_image_url" type="url" value="<?php echo esc_attr( $wallet['promo_image_url_input'] ); ?>" placeholder="https://example.com/promo.png">
						</section>
						<section class="lw-promo-control-card">
							<label class="lw-feature-check" for="lw-wallet-appointment-enabled">
								<input id="lw-wallet-appointment-enabled" name="wallet_appointment_enabled" type="checkbox" value="1" <?php checked( $wallet['appointment_enabled'] ); ?>>
								<span><strong>Show appointment button</strong><small>Display a call-to-action at the bottom of the pass.</small></span>
							</label>
							<div class="lw-appointment-fields">
								<label for="lw-wallet-appointment-label">Button label<input id="lw-wallet-appointment-label" name="wallet_appointment_label" type="text" value="<?php echo esc_attr( $wallet['appointment_label'] ); ?>" maxlength="30" placeholder="Hacer cita"></label>
								<label for="lw-wallet-appointment-url">Booking URL<input id="lw-wallet-appointment-url" name="wallet_appointment_url" type="url" value="<?php echo esc_attr( $wallet['appointment_url'] ); ?>" placeholder="https://example.com/citas"></label>
							</div>
						</section>
					</div>
					<aside class="lw-wallet-actions-preview" aria-label="Google Wallet promotion and appointment preview">
						<div class="lw-live-preview-heading"><span><strong>Wallet actions preview</strong><small>Approximate Google Wallet appearance</small></span><span class="dashicons dashicons-megaphone"></span></div>
						<div id="lw-card-promo-preview" class="lw-card-promo-preview" <?php echo $wallet['promo_enabled'] ? '' : 'hidden'; ?>>
							<div><strong id="lw-card-promo-title"><?php echo esc_html( $wallet['promo_title'] ); ?></strong><span id="lw-card-promo-body"><?php echo esc_html( $wallet['promo_body'] ); ?></span></div>
							<span id="lw-card-promo-image"><?php if ( $wallet['promo_image_url'] ) : ?><img src="<?php echo esc_url( $wallet['promo_image_url'] ); ?>" alt=""><?php else : ?><span class="dashicons dashicons-tag"></span><?php endif; ?></span>
						</div>
						<a id="lw-card-appointment-preview" class="lw-card-appointment-preview" href="<?php echo esc_url( $wallet['appointment_url'] ?: '#' ); ?>" <?php echo $wallet['appointment_enabled'] ? '' : 'hidden'; ?>><span class="dashicons dashicons-external"></span><strong id="lw-card-appointment-label"><?php echo esc_html( $wallet['appointment_label'] ); ?></strong></a>
						<p>Google controls the final placement and typography on each device.</p>
					</aside>
				</div>
			</section>
			<div class="lw-loyalty-settings-actions">
				<button type="submit" class="button button-primary" name="wallet_subsection" value="promotions">Save Promotions &amp; appointments</button>
			</div>
		</div>
	</details>
</div>
