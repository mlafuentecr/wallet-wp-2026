<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $wallet_name ); ?> — Review &amp; Loyalty Card</title>
	<?php wp_head(); ?>
</head>
<body class="lw-public-wallet">
	<main class="lw-public-shell">
		<header class="lw-public-brand">
			<?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $wallet_name ); ?> logo"><?php else : ?><span class="lw-public-logo-placeholder">★</span><?php endif; ?>
			<strong><?php echo esc_html( $wallet_name ); ?></strong>
			<small>Google Review &amp; Loyalty Card</small>
		</header>
		<section class="lw-public-content">
			<div class="lw-public-intro">
				<span class="lw-public-step">1</span>
				<div><h1>Share your experience</h1><p>Leave your review on Google, then return here to add your loyalty card to Google Wallet.</p></div>
			</div>
			<?php if ( $google['review_url'] ) : ?>
				<a id="lw-public-review-button" class="lw-public-review-button" href="<?php echo esc_url( $google['review_url'] ); ?>" target="_blank" rel="noopener noreferrer"><span>★★★★★</span>Review us on Google <b>↗</b></a>
			<?php else : ?>
				<p class="lw-public-error">This business has not configured its Google review link.</p>
			<?php endif; ?>

			<div class="lw-public-divider"><span>After publishing your review</span></div>
			<div class="lw-public-intro">
				<span class="lw-public-step">2</span>
				<div><h2>Your digital loyalty card</h2><p>Add it to Google Wallet with <strong><?php echo esc_html( absint( $google['review_points'] ?? 0 ) ); ?> welcome points</strong>.</p></div>
			</div>

			<?php if ( $error ) : ?><p class="lw-public-error"><?php echo esc_html( $error ); ?></p><?php endif; ?>
			<?php if ( $google_signin_enabled ) : ?>
				<div id="lw-google-enrollment" class="lw-google-enrollment">
					<div id="g_id_onload"
						data-client_id="<?php echo esc_attr( $google['client_id'] ); ?>"
						data-callback="loyaltyWalletGoogleCredential"
						data-auto_prompt="false"
						data-context="signin"></div>
					<div id="lw-google-signin-stage">
						<p class="lw-signin-copy">Continue with Google so we can create and securely link your personal loyalty card.</p>
						<div class="g_id_signin"
							data-type="standard"
							data-shape="rectangular"
							data-theme="outline"
							data-text="continue_with"
							data-size="large"
							data-logo_alignment="left"
							data-width="360"></div>
					</div>
					<p id="lw-google-enrollment-status" class="lw-enrollment-status" role="status" aria-live="polite"></p>
					<div id="lw-google-phone-stage" hidden>
						<div class="lw-google-profile">
							<img id="lw-google-profile-picture" src="" alt="" hidden>
							<span id="lw-google-profile-placeholder">G</span>
							<span><strong id="lw-google-profile-name"></strong><small id="lw-google-profile-email"></small></span>
						</div>
						<form id="lw-google-phone-form" class="lw-public-form">
							<input id="lw-google-enrollment-token" type="hidden" value="">
							<label for="lw-google-phone">WhatsApp / phone number <small>Include your country code</small>
								<input id="lw-google-phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+506 8888 8888" required>
							</label>
							<label class="lw-public-consent"><input id="lw-google-consent" type="checkbox" required><span>I agree to provide this information to <?php echo esc_html( $wallet_name ); ?> for loyalty and customer communications.</span></label>
							<button type="submit" class="lw-enrollment-button">Create my loyalty card</button>
						</form>
					</div>
					<div id="lw-google-wallet-stage" hidden>
						<div class="lw-public-ready"><span>✓</span><div><strong>Your loyalty card is ready</strong><small>Your customer profile was securely linked.</small></div></div>
						<a id="lw-personal-wallet-link" class="lw-google-wallet-button" href="#"><span aria-hidden="true">▣</span>Add to Google Wallet</a>
					</div>
				</div>
			<?php elseif ( $wallet_url ) : ?>
				<a class="lw-google-wallet-button" href="<?php echo esc_url( $wallet_url ); ?>"><span aria-hidden="true">▣</span>Add to Google Wallet</a>
			<?php endif; ?>
		</section>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
