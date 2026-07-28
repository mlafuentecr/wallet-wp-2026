<!doctype html>
<html lang="es">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $wallet_name ); ?> — Reseñas y tarjeta de lealtad</title>
	<?php wp_head(); ?>
</head>
<body class="lw-public-wallet">
	<main class="lw-public-shell">
		<header class="lw-public-brand">
			<?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( 'Logo de ' . $wallet_name ); ?>"><?php else : ?><span class="lw-public-logo-placeholder">★</span><?php endif; ?>
			<strong><?php echo esc_html( $wallet_name ); ?></strong>
			<small>Reseñas de Google y tarjeta de lealtad</small>
		</header>
		<section class="lw-public-content">
			<div class="lw-public-intro">
				<span class="lw-public-step">1</span>
				<div><h1>Comparte tu experiencia</h1><p>Deja tu reseña en Google y luego regresa aquí para agregar tu tarjeta de lealtad a Google Wallet.</p></div>
			</div>
			<?php if ( $google['review_url'] ) : ?>
				<a id="lw-public-review-button" class="lw-public-review-button" href="<?php echo esc_url( $google['review_url'] ); ?>" target="_blank" rel="noopener noreferrer"><span>★★★★★</span>Déjanos una reseña en Google <b>↗</b></a>
			<?php else : ?>
				<p class="lw-public-error">Este negocio todavía no ha configurado su enlace de reseñas de Google.</p>
			<?php endif; ?>

			<div class="lw-public-divider"><span>Después de publicar tu reseña</span></div>
			<div class="lw-public-intro">
				<span class="lw-public-step">2</span>
				<div><h2>Tu tarjeta digital de lealtad</h2><p>Agrégala a Google Wallet con <strong><?php echo esc_html( absint( $google['review_points'] ?? 0 ) ); ?> puntos de bienvenida</strong>.</p></div>
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
						<p class="lw-signin-copy">Continúa con Google para crear y vincular de forma segura tu tarjeta personal de lealtad.</p>
						<div class="g_id_signin"
							data-type="standard"
							data-shape="rectangular"
							data-theme="outline"
							data-text="continue_with"
							data-locale="es"
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
							<label for="lw-google-phone">WhatsApp / número de teléfono <small>Incluye el código de tu país</small>
								<input id="lw-google-phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+506 8888 8888" required>
							</label>
							<label class="lw-public-consent"><input id="lw-google-consent" type="checkbox" required><span>Acepto proporcionar esta información a <?php echo esc_html( $wallet_name ); ?> para el programa de lealtad y sus comunicaciones con clientes.</span></label>
							<button type="submit" class="lw-enrollment-button">Crear mi tarjeta de lealtad</button>
						</form>
					</div>
					<div id="lw-google-wallet-stage" hidden>
						<div class="lw-public-ready"><span>✓</span><div><strong>Tu tarjeta de lealtad está lista</strong><small>Tu perfil de cliente quedó vinculado de forma segura.</small></div></div>
						<a id="lw-personal-wallet-link" class="lw-google-wallet-button" href="#"><span aria-hidden="true">▣</span>Agregar a Google Wallet</a>
					</div>
				</div>
			<?php elseif ( $wallet_url ) : ?>
				<a class="lw-google-wallet-button" href="<?php echo esc_url( $wallet_url ); ?>"><span aria-hidden="true">▣</span>Agregar a Google Wallet</a>
			<?php endif; ?>
		</section>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
