<div id="lw-qr-settings" class="lw-settings-panel" role="tabpanel" aria-labelledby="lw-review-tab" hidden>
	<div class="lw-settings-heading"><h2>Google Reviews settings</h2><p>Connect the business listing used by the review QR code.</p></div>
	<section class="lw-direct-review-link">
		<div class="lw-direct-review-icon"><span class="dashicons dashicons-star-filled"></span></div>
		<div><label for="lw-google-review-url">Google review link</label><p><a href="https://support.google.com/business/answer/3474122" target="_blank" rel="noopener noreferrer">How to get your “Ask for reviews” link from Google <span aria-hidden="true">↗</span></a></p></div>
		<div class="lw-direct-review-input"><input id="lw-google-review-url" name="google_review_url" type="url" value="<?php echo esc_attr( $google['review_url_input'] ); ?>" placeholder="https://g.page/r/.../review"><a id="lw-open-review-link" class="button" href="<?php echo esc_url( $google['review_url'] ?: '#' ); ?>" target="_blank" rel="noopener noreferrer">Open link <span aria-hidden="true">↗</span></a></div>
	</section>
	<section class="lw-review-reward" aria-labelledby="lw-review-reward-title">
		<div class="lw-reward-icon"><span class="dashicons dashicons-awards"></span></div>
		<div class="lw-reward-copy"><h3 id="lw-review-reward-title">Review reward points</h3><p>Points automatically assigned when a verified review is added as a customer.</p></div>
		<label for="lw-review-points"><span>Points per review</span><span class="lw-points-input"><input id="lw-review-points" name="review_points" type="number" value="<?php echo esc_attr( $google['review_points'] ); ?>" min="0" max="100000" step="1"><b>pts</b></span></label>
	</section>
	<details class="lw-credentials-toggle" <?php echo $google['is_configured'] ? 'open' : ''; ?>>
		<summary><span><strong>Google credentials for reviews</strong><small>Advanced Google Business Profile connection</small></span><i aria-hidden="true"></i></summary>
		<div class="lw-credentials-content">
			<div class="lw-review-fields">
				<div class="lw-field-group"><label for="lw-place-id">Google Place ID</label><input id="lw-place-id" name="place_id" type="text" value="<?php echo esc_attr( $google['place_id'] ); ?>" placeholder="ChIJ..." maxlength="512" required><a class="lw-help-link" href="https://developers.google.com/maps/documentation/places/web-service/place-id#find-id" target="_blank" rel="noopener noreferrer">Find your Place ID <span aria-hidden="true">↗</span></a></div>
				<div class="lw-field-group"><label for="lw-maps-url">Google Maps link</label><input id="lw-maps-url" name="maps_url" type="url" value="<?php echo esc_attr( $google['maps_url'] ); ?>" placeholder="https://www.google.com/maps/place/..." required><a id="lw-open-map" class="lw-help-link" href="<?php echo esc_url( $google['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer">Open business on Google Maps <span aria-hidden="true">↗</span></a></div>
			</div>
			<label class="lw-sandbox-option"><input name="google_sandbox_mode" type="checkbox" value="1" <?php checked( $google['sandbox_mode'] ); ?>><span><strong>Sandbox mode</strong><small>Use test reviews without connecting Google</small></span></label>
			<div class="lw-google-status <?php echo $google['is_configured'] ? 'is-ready' : ''; ?>"><span class="lw-status-dot"></span><span><strong>Google Business Profile</strong><small><?php echo $google['is_configured'] ? 'Configuration saved — ready for authorization' : 'Not configured'; ?></small></span></div>
			<a class="button lw-google-profile-link" href="https://business.google.com/add" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-google"></span> Add or claim Business Profile <span aria-hidden="true">↗</span></a>
			<div class="lw-label-with-link"><label for="lw-google-client-id">OAuth Client ID</label><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Open OAuth Clients <span aria-hidden="true">↗</span></a></div><input id="lw-google-client-id" name="google_client_id" type="text" value="<?php echo esc_attr( $google['client_id'] ); ?>" placeholder="000000000000-xxxx.apps.googleusercontent.com">
			<div class="lw-label-with-link"><label for="lw-google-client-secret">OAuth Client Secret</label><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Manage OAuth credentials <span aria-hidden="true">↗</span></a></div><input id="lw-google-client-secret" name="google_client_secret" type="password" value="" placeholder="<?php echo $google['has_secret'] ? 'Saved — enter a new value to replace it' : 'Enter client secret'; ?>" autocomplete="new-password">
			<div class="lw-label-with-link"><label for="lw-google-account-id">Google Account ID</label><a href="https://business.google.com/locations" target="_blank" rel="noopener noreferrer">Open Business Profile <span aria-hidden="true">↗</span></a></div><input id="lw-google-account-id" name="google_account_id" type="text" value="<?php echo esc_attr( $google['account_id'] ); ?>" placeholder="accounts/123456789">
			<div class="lw-label-with-link"><label for="lw-google-location-id">Google Location ID</label><a href="https://business.google.com/locations" target="_blank" rel="noopener noreferrer">Open business locations <span aria-hidden="true">↗</span></a></div><input id="lw-google-location-id" name="google_location_id" type="text" value="<?php echo esc_attr( $google['location_id'] ); ?>" placeholder="locations/987654321">
		</div>
	</details>
</div>
