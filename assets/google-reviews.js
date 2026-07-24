(function () {
	'use strict';
	var reviewTab = document.getElementById('lw-review-tab');
	var nameTab = document.getElementById('lw-name-tab');
	var customersTab = document.getElementById('lw-customers-tab');
	var rewardsTab = document.getElementById('lw-rewards-tab');
	var activityTab = document.getElementById('lw-activity-tab');
	var configurationTab = document.getElementById('lw-configuration-tab');
	var googleLoyaltyTab = document.getElementById('lw-google-loyalty-tab');
	var messagesTab = document.getElementById('lw-messages-tab');
	var loyaltyTab = document.getElementById('lw-loyalty-tab');
	var qrPanel = document.getElementById('lw-qr-settings');
	var googleLoyaltyPanel = document.getElementById('lw-google-loyalty-settings');
	var messagesPanel = document.getElementById('lw-message-settings');
	var namePanel = document.getElementById('lw-name-settings');
	var customersPanel = document.getElementById('lw-customers-panel');
	var rewardsPanel = document.getElementById('lw-rewards-panel');
	var activityPanel = document.getElementById('lw-activity-panel');
	var loyaltyPanel = document.getElementById('lw-loyalty-settings');
	var section = document.getElementById('lw-settings-section');
	if (googleLoyaltyPanel) {
		var designToggle = googleLoyaltyPanel.querySelector('.lw-wallet-design-toggle');
		var promotionsToggle = googleLoyaltyPanel.querySelector('.lw-wallet-promotions-toggle');
		var configurationToggle = googleLoyaltyPanel.querySelector('.lw-loyalty-settings-toggle:not(.lw-wallet-design-toggle)');
		if (designToggle && configurationToggle) googleLoyaltyPanel.insertBefore(designToggle, configurationToggle);
		if (promotionsToggle && configurationToggle) googleLoyaltyPanel.insertBefore(promotionsToggle, configurationToggle);
	}
	var settingsSubmit = document.querySelector('#lw-code-editor > form #submit');
	var nameInput = document.getElementById('lw-wallet-name');
	var programNameInput = document.getElementById('lw-wallet-program-name');
	var contactHelpInput = document.getElementById('lw-wallet-contact-help');
	var placeInput = document.getElementById('lw-place-id');
	var mapsInput = document.getElementById('lw-maps-url');
	var pointsInput = document.getElementById('lw-review-points');
	var mapLink = document.getElementById('lw-open-map');
	var image = document.getElementById('lw-qr-image');
	var empty = document.getElementById('lw-qr-empty');
	var qrActions = document.getElementById('lw-qr-actions');
	var qrOpen = document.getElementById('lw-qr-open');
	var qrCopy = document.getElementById('lw-qr-copy');
	var qrCopyStatus = document.getElementById('lw-qr-copy-status');
	if (!customersTab || !activityTab || !namePanel || !customersPanel || !activityPanel || !section || !image || !empty || !qrActions || !qrOpen || !qrCopy || !qrCopyStatus) return;
	function setPanelFields(panel, enabled) {
		if (!panel) return;
		panel.querySelectorAll('input, select, textarea').forEach(function (field) {
			field.disabled = !enabled || field.dataset.lwLocked === '1';
		});
	}
	function selectTab(selected) {
		var editingName = selected === 'name';
		var editingReviews = selected === 'reviews';
		var editingGoogleLoyalty = selected === 'google-loyalty';
		var editingMessages = selected === 'messages';
		var editingAccess = selected === 'loyalty';
		var editingConfiguration = editingName || editingReviews || editingGoogleLoyalty || editingMessages || editingAccess;
		if (nameTab) nameTab.classList.toggle('is-active', editingName);
		customersTab.classList.toggle('is-active', selected === 'customers');
		if (rewardsTab) rewardsTab.classList.toggle('is-active', selected === 'rewards');
		activityTab.classList.toggle('is-active', selected === 'activity');
		if (configurationTab) configurationTab.classList.toggle('is-active', editingConfiguration);
		if (reviewTab) reviewTab.classList.toggle('is-active', editingReviews);
		if (googleLoyaltyTab) googleLoyaltyTab.classList.toggle('is-active', editingGoogleLoyalty);
		if (messagesTab) messagesTab.classList.toggle('is-active', editingMessages);
		if (loyaltyTab) loyaltyTab.classList.toggle('is-active', editingAccess);
		if (nameTab) nameTab.setAttribute('aria-selected', editingName ? 'true' : 'false');
		customersTab.setAttribute('aria-selected', selected === 'customers' ? 'true' : 'false');
		if (rewardsTab) rewardsTab.setAttribute('aria-selected', selected === 'rewards' ? 'true' : 'false');
		activityTab.setAttribute('aria-selected', selected === 'activity' ? 'true' : 'false');
		if (configurationTab) configurationTab.setAttribute('aria-selected', editingConfiguration ? 'true' : 'false');
		if (reviewTab) reviewTab.setAttribute('aria-selected', editingReviews ? 'true' : 'false');
		if (googleLoyaltyTab) googleLoyaltyTab.setAttribute('aria-selected', editingGoogleLoyalty ? 'true' : 'false');
		if (messagesTab) messagesTab.setAttribute('aria-selected', editingMessages ? 'true' : 'false');
		if (loyaltyTab) loyaltyTab.setAttribute('aria-selected', editingAccess ? 'true' : 'false');
		namePanel.hidden = !editingName;
		customersPanel.hidden = selected !== 'customers';
		if (rewardsPanel) rewardsPanel.hidden = selected !== 'rewards';
		activityPanel.hidden = selected !== 'activity';
		if (qrPanel) qrPanel.hidden = !editingReviews;
		if (googleLoyaltyPanel) googleLoyaltyPanel.hidden = !editingGoogleLoyalty;
		if (messagesPanel) messagesPanel.hidden = !editingMessages;
		if (loyaltyPanel) loyaltyPanel.hidden = !editingAccess;
		setPanelFields(namePanel, editingName);
		setPanelFields(qrPanel, editingReviews);
		setPanelFields(googleLoyaltyPanel, editingGoogleLoyalty);
		setPanelFields(messagesPanel, editingMessages);
		section.value = editingName ? 'name' : (editingReviews ? 'reviews' : (editingGoogleLoyalty ? 'google_loyalty' : (editingMessages ? 'messages' : '')));
		if (settingsSubmit) settingsSubmit.style.display = selected === 'customers' || selected === 'rewards' || selected === 'activity' || editingAccess || editingGoogleLoyalty || editingMessages ? 'none' : '';
		if (editingName && nameInput) nameInput.focus();
		else if (editingReviews && placeInput) placeInput.focus();
		else if (editingGoogleLoyalty) {
			var loyaltySummary = googleLoyaltyPanel ? googleLoyaltyPanel.querySelector('summary') : null;
			if (loyaltySummary) loyaltySummary.focus();
		}
		else if (editingMessages && messagesPanel) messagesPanel.querySelector('textarea').focus();
	}
	if (nameTab) nameTab.addEventListener('click', function () { selectTab('name'); });
	customersTab.addEventListener('click', function () { selectTab('customers'); });
	if (rewardsTab && rewardsPanel) rewardsTab.addEventListener('click', function () { selectTab('rewards'); });
	activityTab.addEventListener('click', function () { selectTab('activity'); });
	if (configurationTab) configurationTab.addEventListener('click', function () { selectTab('name'); });
	if (reviewTab) reviewTab.addEventListener('click', function () { selectTab('reviews'); });
	if (googleLoyaltyTab && googleLoyaltyPanel) googleLoyaltyTab.addEventListener('click', function () { selectTab('google-loyalty'); });
	if (messagesTab && messagesPanel) messagesTab.addEventListener('click', function () { selectTab('messages'); });
	if (loyaltyTab && loyaltyPanel) loyaltyTab.addEventListener('click', function () { selectTab('loyalty'); });
	var logoInput = document.getElementById('lw-wallet-logo'), logoPreview = document.getElementById('lw-logo-preview');
	if (logoInput && logoPreview) logoInput.addEventListener('change', function () {
		var file = logoInput.files && logoInput.files[0];
		if (!file) return;
		if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { logoInput.value = ''; window.alert('Choose a JPG, PNG or WebP image under 5 MB.'); return; }
		var businessMediaId = document.getElementById('lw-business-logo-media-id');
		if (businessMediaId) businessMediaId.value = '';
		var reader = new FileReader(); reader.onload = function (event) { logoPreview.innerHTML = ''; var previewImage = document.createElement('img'); previewImage.src = event.target.result; previewImage.alt = 'New business logo preview'; logoPreview.appendChild(previewImage); }; reader.readAsDataURL(file);
	});
	var walletLogoInput = document.getElementById('lw-wallet-logo-upload'), walletLogoPreview = document.getElementById('lw-wallet-logo-preview');
	if (walletLogoInput && walletLogoPreview) walletLogoInput.addEventListener('change', function () {
		var file = walletLogoInput.files && walletLogoInput.files[0];
		if (!file) return;
		if (!/^image\/(png|jpeg|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { walletLogoInput.value = ''; window.alert('Choose a PNG, JPG or WebP image under 5 MB.'); return; }
		var googleWalletMediaId = document.getElementById('lw-google-wallet-logo-media-id');
		var googleWalletLogoUrl = document.getElementById('lw-wallet-logo-url');
		if (googleWalletMediaId) googleWalletMediaId.value = '';
		if (googleWalletLogoUrl) googleWalletLogoUrl.value = '';
		var previewReader = new FileReader(); previewReader.onload = function (event) { walletLogoPreview.innerHTML = ''; var logoImage = document.createElement('img'); logoImage.src = event.target.result; logoImage.alt = 'New Google Wallet logo preview'; walletLogoPreview.appendChild(logoImage); }; previewReader.readAsDataURL(file);
	});
	var walletHeroInput = document.getElementById('lw-wallet-hero-upload'), walletHeroPreview = document.getElementById('lw-wallet-hero-preview'), walletHeroMode = document.getElementById('lw-wallet-hero-mode'), walletHeroBadge = document.getElementById('lw-wallet-random-badge');
	if (walletHeroInput && walletHeroPreview) walletHeroInput.addEventListener('change', function () {
		var file = walletHeroInput.files && walletHeroInput.files[0];
		if (!file) return;
		if (!/^image\/(png|jpeg|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { walletHeroInput.value = ''; window.alert('Choose a PNG, JPG or WebP banner under 5 MB.'); return; }
		var heroMediaId = document.getElementById('lw-google-wallet-hero-media-id');
		var heroUrl = document.getElementById('lw-wallet-hero-url');
		if (heroMediaId) heroMediaId.value = '';
		if (heroUrl) heroUrl.value = '';
		if (walletHeroMode) walletHeroMode.value = 'custom';
		if (walletHeroBadge) walletHeroBadge.hidden = true;
		var heroReader = new FileReader(); heroReader.onload = function (event) { walletHeroPreview.innerHTML = ''; var heroImage = document.createElement('img'); heroImage.src = event.target.result; heroImage.alt = 'New Google Wallet banner preview'; walletHeroPreview.appendChild(heroImage); }; heroReader.readAsDataURL(file);
	});
	var walletHeroUrl = document.getElementById('lw-wallet-hero-url'), walletHeroRandom = document.getElementById('lw-wallet-random-hero'), walletHeroSeed = document.getElementById('lw-wallet-hero-random-seed');
	if (walletHeroUrl) walletHeroUrl.addEventListener('input', function () { if (walletHeroUrl.value.trim() && walletHeroMode) walletHeroMode.value = 'custom'; if (walletHeroUrl.value.trim() && walletHeroBadge) walletHeroBadge.hidden = true; });
	if (walletHeroRandom && walletHeroPreview && walletHeroSeed) walletHeroRandom.addEventListener('click', function () {
		var seed = 'loyalty-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
		var randomUrl = (walletHeroRandom.dataset.randomBase || 'https://picsum.photos/seed/') + encodeURIComponent(seed) + '/1032/812.jpg';
		var heroMediaId = document.getElementById('lw-google-wallet-hero-media-id');
		if (walletHeroInput) walletHeroInput.value = '';
		if (walletHeroUrl) walletHeroUrl.value = '';
		if (heroMediaId) heroMediaId.value = '';
		if (walletHeroMode) walletHeroMode.value = 'random';
		walletHeroSeed.value = seed;
		if (walletHeroBadge) walletHeroBadge.hidden = false;
		walletHeroPreview.innerHTML = '';
		var randomImage = document.createElement('img');
		randomImage.src = randomUrl;
		randomImage.alt = 'Random Google Wallet banner preview';
		walletHeroPreview.appendChild(randomImage);
	});
	var walletPromoImageInput = document.getElementById('lw-wallet-promo-image-upload');
	var walletPromoImagePreview = document.getElementById('lw-wallet-promo-image-preview');
	var walletPromoImageUrl = document.getElementById('lw-wallet-promo-image-url');
	if (walletPromoImageInput && walletPromoImagePreview) walletPromoImageInput.addEventListener('change', function () {
		var file = walletPromoImageInput.files && walletPromoImageInput.files[0];
		if (!file) return;
		if (!/^image\/(png|jpeg|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { walletPromoImageInput.value = ''; window.alert('Choose a square PNG, JPG or WebP promotion image under 5 MB.'); return; }
		var promoMediaId = document.getElementById('lw-wallet-promo-image-media-id');
		if (promoMediaId) promoMediaId.value = '';
		if (walletPromoImageUrl) walletPromoImageUrl.value = '';
		var promoReader = new FileReader(); promoReader.onload = function (event) { walletPromoImagePreview.innerHTML = ''; var promoImage = document.createElement('img'); promoImage.src = event.target.result; promoImage.alt = 'New promotion image preview'; walletPromoImagePreview.appendChild(promoImage); }; promoReader.readAsDataURL(file);
	});
	var walletColor = document.getElementById('lw-wallet-background-color'), walletColorValue = document.getElementById('lw-wallet-background-color-value');
	var walletCardPreview = document.getElementById('lw-wallet-card-preview');
	var walletCardLogo = document.getElementById('lw-card-preview-logo');
	var walletCardBanner = document.getElementById('lw-card-preview-banner');
	var walletCardProgramName = document.getElementById('lw-card-preview-program-name');
	var walletLogoUrlInput = document.getElementById('lw-wallet-logo-url');
	var walletPromoEnabled = document.getElementById('lw-wallet-promo-enabled');
	var walletPromoTitle = document.getElementById('lw-wallet-promo-title');
	var walletPromoBody = document.getElementById('lw-wallet-promo-body');
	var walletPromoPreview = document.getElementById('lw-card-promo-preview');
	var walletPromoPreviewTitle = document.getElementById('lw-card-promo-title');
	var walletPromoPreviewBody = document.getElementById('lw-card-promo-body');
	var walletPromoPreviewImage = document.getElementById('lw-card-promo-image');
	var walletAppointmentEnabled = document.getElementById('lw-wallet-appointment-enabled');
	var walletAppointmentLabel = document.getElementById('lw-wallet-appointment-label');
	var walletAppointmentUrl = document.getElementById('lw-wallet-appointment-url');
	var walletAppointmentPreview = document.getElementById('lw-card-appointment-preview');
	var walletAppointmentPreviewLabel = document.getElementById('lw-card-appointment-label');
	function syncWalletCardImage(source, target, fallbackIcon) {
		if (!source || !target) return;
		var sourceImage = source.querySelector('img');
		target.innerHTML = '';
		if (sourceImage && sourceImage.src) {
			var imageCopy = document.createElement('img');
			imageCopy.src = sourceImage.src;
			imageCopy.alt = '';
			target.appendChild(imageCopy);
		} else if (fallbackIcon) {
			var icon = document.createElement('span');
			icon.className = 'dashicons ' + fallbackIcon;
			target.appendChild(icon);
		}
	}
	function syncWalletCardPreview() {
		if (walletCardPreview && walletColor) walletCardPreview.style.setProperty('--lw-card-color', walletColor.value);
		if (walletCardProgramName && programNameInput) walletCardProgramName.textContent = programNameInput.value.trim() || ((nameInput ? nameInput.value.trim() : '') + ' Loyalty').trim();
		var walletCardContactHelp = document.getElementById('lw-card-preview-contact-help');
		if (walletCardContactHelp && contactHelpInput) walletCardContactHelp.textContent = contactHelpInput.value.trim() || 'Toca los tres puntos para llamar o escribir por WhatsApp.';
		syncWalletCardImage(walletLogoPreview, walletCardLogo, 'dashicons-store');
		syncWalletCardImage(walletHeroPreview, walletCardBanner, '');
	}
	function syncWalletActionsPreview() {
		if (walletPromoPreview && walletPromoEnabled) walletPromoPreview.hidden = !walletPromoEnabled.checked;
		if (walletPromoPreviewTitle && walletPromoTitle) walletPromoPreviewTitle.textContent = walletPromoTitle.value.trim() || 'Promociones';
		if (walletPromoPreviewBody && walletPromoBody) walletPromoPreviewBody.textContent = walletPromoBody.value.trim() || 'Revisa promociones disponibles';
		syncWalletCardImage(walletPromoImagePreview, walletPromoPreviewImage, 'dashicons-tag');
		if (walletAppointmentPreview && walletAppointmentEnabled) walletAppointmentPreview.hidden = !walletAppointmentEnabled.checked;
		if (walletAppointmentPreviewLabel && walletAppointmentLabel) walletAppointmentPreviewLabel.textContent = walletAppointmentLabel.value.trim() || 'Hacer cita';
		if (walletAppointmentPreview && walletAppointmentUrl) walletAppointmentPreview.href = walletAppointmentUrl.value.trim() || '#';
	}
	if (programNameInput) programNameInput.addEventListener('input', syncWalletCardPreview);
	if (contactHelpInput) contactHelpInput.addEventListener('input', syncWalletCardPreview);
	[walletPromoTitle, walletPromoBody, walletAppointmentLabel, walletAppointmentUrl].forEach(function (field) { if (field) field.addEventListener('input', syncWalletActionsPreview); });
	[walletPromoEnabled, walletAppointmentEnabled].forEach(function (field) { if (field) field.addEventListener('change', syncWalletActionsPreview); });
	if (walletColor && walletColorValue) walletColor.addEventListener('input', function () { walletColorValue.textContent = walletColor.value.toUpperCase(); syncWalletCardPreview(); });
	if (walletLogoUrlInput) walletLogoUrlInput.addEventListener('input', function () {
		var url = walletLogoUrlInput.value.trim();
		if (!url || !walletLogoPreview) return;
		walletLogoPreview.innerHTML = '';
		var logoFromUrl = document.createElement('img');
		logoFromUrl.src = url; logoFromUrl.alt = 'Google Wallet logo URL preview';
		walletLogoPreview.appendChild(logoFromUrl);
	});
	if (walletHeroUrl) walletHeroUrl.addEventListener('input', function () {
		var url = walletHeroUrl.value.trim();
		if (!url || !walletHeroPreview) return;
		walletHeroPreview.innerHTML = '';
		var bannerFromUrl = document.createElement('img');
		bannerFromUrl.src = url; bannerFromUrl.alt = 'Google Wallet banner URL preview';
		walletHeroPreview.appendChild(bannerFromUrl);
	});
	if (walletPromoImageUrl) walletPromoImageUrl.addEventListener('input', function () {
		var url = walletPromoImageUrl.value.trim();
		if (!url || !walletPromoImagePreview) return;
		walletPromoImagePreview.innerHTML = '';
		var promoFromUrl = document.createElement('img');
		promoFromUrl.src = url; promoFromUrl.alt = 'Promotion image URL preview';
		walletPromoImagePreview.appendChild(promoFromUrl);
	});
	if (window.MutationObserver) {
		if (walletLogoPreview) new MutationObserver(syncWalletCardPreview).observe(walletLogoPreview, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
		if (walletHeroPreview) new MutationObserver(syncWalletCardPreview).observe(walletHeroPreview, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
		if (walletPromoImagePreview) new MutationObserver(syncWalletActionsPreview).observe(walletPromoImagePreview, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
	}
	syncWalletCardPreview();
	syncWalletActionsPreview();
	var serviceAccountJsonInput = document.getElementById('lw-wallet-service-account-json');
	var serviceAccountJsonName = document.getElementById('lw-wallet-service-account-json-name');
	if (serviceAccountJsonInput && serviceAccountJsonName) serviceAccountJsonInput.addEventListener('change', function () {
		var file = serviceAccountJsonInput.files && serviceAccountJsonInput.files[0];
		if (!file) return;
		if (!/\.json$/i.test(file.name) || file.size < 1 || file.size > 1024 * 1024) {
			serviceAccountJsonInput.value = '';
			window.alert('Choose a Google service account JSON file under 1 MB.');
			return;
		}
		serviceAccountJsonName.textContent = file.name;
	});
	document.querySelectorAll('.lw-media-library-button').forEach(function (button) {
		button.addEventListener('click', function () {
			if (!window.wp || !window.wp.media) {
				window.alert('The WordPress Media Library is not available for this account.');
				return;
			}
			var frame = window.wp.media({
				title: button.dataset.mediaTitle || 'Choose an image',
				button: { text: button.dataset.mediaButton || 'Use this image' },
				library: { type: 'image' },
				multiple: false
			});
			frame.on('select', function () {
				var selected = frame.state().get('selection').first().toJSON();
				var allowed = ['image/jpeg', 'image/png', 'image/webp'];
				if (allowed.indexOf(selected.mime) === -1) {
					window.alert('Choose a JPG, PNG or WebP image.');
					return;
				}
				var mediaInput = document.getElementById(button.dataset.mediaTarget);
				var preview = document.getElementById(button.dataset.previewTarget);
				var fileInput = button.dataset.fileTarget ? document.getElementById(button.dataset.fileTarget) : null;
				var clearUrl = button.dataset.clearUrl ? document.getElementById(button.dataset.clearUrl) : null;
				var modeInput = button.dataset.modeTarget ? document.getElementById(button.dataset.modeTarget) : null;
				if (mediaInput) mediaInput.value = selected.id;
				if (fileInput) fileInput.value = '';
				if (clearUrl) clearUrl.value = '';
				if (modeInput) modeInput.value = 'custom';
				if (modeInput && walletHeroBadge) walletHeroBadge.hidden = true;
				if (preview) {
					var previewUrl = selected.sizes && selected.sizes.medium ? selected.sizes.medium.url : selected.url;
					preview.innerHTML = '';
					var imageElement = document.createElement('img');
					imageElement.src = previewUrl;
					imageElement.alt = 'Selected image preview';
					preview.appendChild(imageElement);
				}
			});
			frame.open();
		});
	});
	if (placeInput) placeInput.addEventListener('input', function () { var id = placeInput.value.trim(), valid = /^[A-Za-z0-9_-]{10,512}$/.test(id), flow = image.dataset.flowUrl || ''; image.hidden = !valid; empty.hidden = valid; qrActions.hidden = !valid; qrOpen.href = valid ? flow : '#'; qrCopy.dataset.url = valid ? flow : ''; if (valid) image.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' + encodeURIComponent(flow); });
	if (mapsInput && mapLink) mapsInput.addEventListener('input', function () { mapLink.href = mapsInput.value.trim() || '#'; });
	qrCopy.addEventListener('click', function () {
		var url = qrCopy.dataset.url || '';
		if (!url) return;
		var copied = function () {
			qrCopy.querySelector('.lw-copy-label').textContent = 'Copied!';
			qrCopyStatus.textContent = 'QR link copied to clipboard.';
			window.setTimeout(function () { qrCopy.querySelector('.lw-copy-label').textContent = 'Copy link'; qrCopyStatus.textContent = ''; }, 1800);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(url).then(copied);
			return;
		}
		var helper = document.createElement('textarea');
		helper.value = url; helper.setAttribute('readonly', ''); helper.style.position = 'fixed'; helper.style.opacity = '0';
		document.body.appendChild(helper); helper.select();
		if (document.execCommand('copy')) copied();
		document.body.removeChild(helper);
	});
	var search = document.getElementById('lw-review-search'), searchButton = document.getElementById('lw-review-search-button'), searchResult = document.getElementById('lw-review-search-result'), addForm = document.getElementById('lw-add-customer-form'), customerName = document.getElementById('lw-customer-name');
	if (search && searchButton && searchResult) searchButton.addEventListener('click', function () {
		var query = search.value.trim().toLowerCase(), sandbox = !!document.querySelector('.lw-sandbox-badge');
		if (!query) { searchResult.className = ''; searchResult.textContent = 'Enter a customer name.'; return; }
		if (!sandbox) { searchResult.className = 'is-pending'; searchResult.textContent = 'Connect Google Business Profile or enable Sandbox mode first.'; return; }
		var found = 'test customer'.indexOf(query) !== -1 || query.indexOf('test customer') !== -1;
		searchResult.className = found ? 'is-found' : 'is-missing'; searchResult.textContent = found ? 'Review found: Test Customer · 5 stars' : 'No sandbox review found for that name.';
		if (addForm && customerName) { addForm.hidden = !found; customerName.value = found ? 'Test Customer' : ''; }
	});
	var customerSearch = document.getElementById('lw-customer-search');
	var customerSearchStatus = document.getElementById('lw-customer-search-status');
	var customerSearchEmpty = document.getElementById('lw-customer-search-empty');
	var customerCards = Array.prototype.slice.call(document.querySelectorAll('#lw-customers-panel .lw-customer'));
	function normalizeCustomerSearch(value) {
		var normalized = String(value || '').toLowerCase();
		if (normalized.normalize) normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		return normalized.replace(/\s+/g, ' ').trim();
	}
	if (customerSearch && customerSearchStatus && customerSearchEmpty && customerCards.length) customerSearch.addEventListener('input', function () {
		var terms = normalizeCustomerSearch(customerSearch.value).split(' ').filter(Boolean);
		var visible = 0;
		customerCards.forEach(function (card) {
			var haystack = normalizeCustomerSearch(card.dataset.customerSearch);
			var matches = terms.every(function (term) { return haystack.indexOf(term) !== -1; });
			card.hidden = !matches;
			if (matches) visible += 1;
		});
		customerSearchEmpty.hidden = visible !== 0;
		customerSearchStatus.textContent = terms.length ? 'Showing ' + visible + ' of ' + customerCards.length + (customerCards.length === 1 ? ' customer' : ' customers') : 'Showing ' + customerCards.length + (customerCards.length === 1 ? ' customer' : ' customers');
	});
	document.querySelectorAll('.lw-edit-customer').forEach(function (button) { button.addEventListener('click', function () { var card = button.closest('.lw-customer'); card.querySelector('.lw-customer-view').hidden = true; card.querySelector('.lw-customer-edit').hidden = false; }); });
	document.querySelectorAll('.lw-cancel-customer').forEach(function (button) { button.addEventListener('click', function () { var card = button.closest('.lw-customer'); card.querySelector('.lw-customer-edit').hidden = true; card.querySelector('.lw-customer-view').hidden = false; }); });
	document.querySelectorAll('.lw-delete-customer').forEach(function (button) { button.addEventListener('click', function (event) { if (!window.confirm('Delete this customer and all saved visits, points and history? This cannot be undone.')) event.preventDefault(); }); });
	document.querySelectorAll('.lw-add-visit-toggle').forEach(function (button) { button.addEventListener('click', function () { var card = button.closest('.lw-customer'); card.querySelector('.lw-add-visit-form').hidden = false; }); });
	document.querySelectorAll('.lw-cancel-visit').forEach(function (button) { button.addEventListener('click', function () { button.closest('.lw-add-visit-form').hidden = true; }); });
	document.querySelectorAll('.lw-message-toggle').forEach(function (button) { button.addEventListener('click', function () { var card = button.closest('.lw-customer'), composer = card.querySelector('.lw-whatsapp-composer'), message = composer.querySelector('.lw-whatsapp-message'); composer.hidden = false; composer.dataset.phone = button.dataset.phone; message.value = button.dataset.message; var visitForm = card.querySelector('.lw-add-visit-form'); if (visitForm) visitForm.hidden = true; message.focus(); }); });
	document.querySelectorAll('.lw-whatsapp-cancel').forEach(function (button) { button.addEventListener('click', function () { button.closest('.lw-whatsapp-composer').hidden = true; }); });
	document.querySelectorAll('.lw-whatsapp-send').forEach(function (link) { link.addEventListener('click', function () { var composer = link.closest('.lw-whatsapp-composer'), message = composer.querySelector('.lw-whatsapp-message').value.trim(); link.href = 'https://wa.me/' + encodeURIComponent(composer.dataset.phone) + (message ? '?text=' + encodeURIComponent(message) : ''); composer.hidden = true; }); });
	document.querySelectorAll('.lw-reminder-toggle').forEach(function (button) { button.addEventListener('click', function () { var card = button.closest('.lw-customer'), form = card.querySelector('.lw-reminder-form'); form.hidden = false; var visit = card.querySelector('.lw-add-visit-form'), composer = card.querySelector('.lw-whatsapp-composer'); if (visit) visit.hidden = true; if (composer) composer.hidden = true; form.querySelector('input[type=date]').focus(); }); });
	document.querySelectorAll('.lw-reminder-cancel').forEach(function (button) { button.addEventListener('click', function () { button.closest('.lw-reminder-form').hidden = true; }); });
	document.querySelectorAll('.lw-open-redemption').forEach(function (button) { button.addEventListener('click', function () { selectTab('rewards'); var start = document.querySelector('.lw-start-scanner'); if (start) start.click(); }); });
	if (rewardsPanel && window.loyaltyWalletRedemption) {
		var redemptionWorkflow = rewardsPanel.querySelector('.lw-redemption-workflow');
		var startScanner = rewardsPanel.querySelector('.lw-start-scanner');
		var startCamera = rewardsPanel.querySelector('.lw-camera-start');
		var stopCamera = rewardsPanel.querySelector('.lw-camera-stop');
		var video = rewardsPanel.querySelector('#lw-redemption-video');
		var placeholder = rewardsPanel.querySelector('.lw-scanner-placeholder');
		var codeInput = rewardsPanel.querySelector('#lw-redemption-code');
		var lookupButton = rewardsPanel.querySelector('.lw-lookup-code');
		var status = rewardsPanel.querySelector('.lw-redemption-status');
		var customerStage = rewardsPanel.querySelector('.lw-redemption-customer');
		var products = rewardsPanel.querySelector('.lw-redeem-products');
		var activeStream = null, scanTimer = null, activeCode = '';
		function setRedemptionStatus(message, isError) { status.textContent = message || ''; status.classList.toggle('is-error', !!isError); }
		function stopRedemptionCamera() {
			if (scanTimer) window.clearInterval(scanTimer);
			scanTimer = null;
			if (activeStream) activeStream.getTracks().forEach(function (track) { track.stop(); });
			activeStream = null;
			video.srcObject = null; video.hidden = true; placeholder.hidden = false;
			stopCamera.hidden = true; startCamera.hidden = false;
		}
		function requestRedemption(action, data) {
			var form = new FormData();
			form.append('action', action); form.append('nonce', window.loyaltyWalletRedemption.nonce);
			Object.keys(data).forEach(function (key) { form.append(key, data[key]); });
			return fetch(window.loyaltyWalletRedemption.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: form }).then(function (response) {
				return response.json().then(function (payload) { if (!response.ok || !payload.success) throw new Error(payload.data && payload.data.message ? payload.data.message : 'The request could not be completed.'); return payload.data; });
			});
		}
		function renderScannedCustomer(data) {
			activeCode = codeInput.value.trim();
			customerStage.hidden = false;
			customerStage.querySelector('.lw-scanned-name').textContent = data.customer.name;
			customerStage.querySelector('.lw-scanned-email').textContent = data.customer.email;
			customerStage.querySelector('.lw-scanned-points').textContent = data.customer.points;
			products.innerHTML = '';
			data.rewards.forEach(function (reward) {
				var canRedeem = data.customer.points >= reward.points;
				var button = document.createElement('button');
				button.type = 'button'; button.className = 'lw-redeem-product'; button.disabled = !canRedeem;
				button.innerHTML = '<span class="dashicons dashicons-coffee"></span><span><strong></strong><small></small></span><b></b>';
				button.querySelector('strong').textContent = reward.name;
				button.querySelector('small').textContent = canRedeem ? 'Disponible para canjear' : 'Faltan ' + (reward.points - data.customer.points) + ' puntos';
				button.querySelector('b').textContent = reward.points + ' pts';
				button.addEventListener('click', function () {
					if (!window.confirm('¿Canjear ' + reward.name + ' por ' + reward.points + ' puntos? Esta acción descontará los puntos del cliente.')) return;
					button.disabled = true; setRedemptionStatus('Procesando canje…', false);
					var requestId = 'redeem-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
					requestRedemption('loyalty_wallet_redeem_reward', { code: activeCode, reward_id: reward.id, request_id: requestId }).then(function (result) {
						setRedemptionStatus(result.message + (result.walletSynced ? ' Google Wallet actualizado.' : ' El canje se guardó, pero Google Wallet no pudo actualizarse.'), !result.walletSynced);
						lookupCode(activeCode);
					}).catch(function (error) { setRedemptionStatus(error.message, true); button.disabled = false; });
				});
				products.appendChild(button);
			});
			if (!data.rewards.length) products.innerHTML = '<p class="lw-no-rewards">Agrega al menos un producto canjeable.</p>';
			customerStage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}
		function lookupCode(code) {
			code = (code || '').trim();
			if (!code) { setRedemptionStatus('Escanea o pega el código del cliente.', true); return; }
			codeInput.value = code; setRedemptionStatus('Validando cliente y saldo…', false); lookupButton.disabled = true;
			requestRedemption('loyalty_wallet_lookup_customer_qr', { code: code }).then(function (data) {
				stopRedemptionCamera(); renderScannedCustomer(data); setRedemptionStatus('Cliente verificado.', false);
			}).catch(function (error) { setRedemptionStatus(error.message, true); customerStage.hidden = true; }).finally(function () { lookupButton.disabled = false; });
		}
		startScanner.addEventListener('click', function () { redemptionWorkflow.hidden = false; customerStage.hidden = true; codeInput.focus(); redemptionWorkflow.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
		lookupButton.addEventListener('click', function () { lookupCode(codeInput.value); });
		codeInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); lookupCode(codeInput.value); } });
		stopCamera.addEventListener('click', stopRedemptionCamera);
		startCamera.addEventListener('click', function () {
			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !('BarcodeDetector' in window)) {
				setRedemptionStatus('Este navegador no permite escanear QR directamente. Usa Chrome actualizado o pega el código manualmente.', true); return;
			}
			setRedemptionStatus('Solicitando acceso a la cámara…', false);
			navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then(function (stream) {
				activeStream = stream; video.srcObject = stream; video.hidden = false; placeholder.hidden = true; startCamera.hidden = true; stopCamera.hidden = false; return video.play();
			}).then(function () {
				var detector = new window.BarcodeDetector({ formats: ['qr_code'] });
				scanTimer = window.setInterval(function () { detector.detect(video).then(function (codes) { if (codes && codes[0] && codes[0].rawValue) lookupCode(codes[0].rawValue); }).catch(function () {}); }, 500);
				setRedemptionStatus('Apunta la cámara al QR de Google Wallet.', false);
			}).catch(function () { stopRedemptionCamera(); setRedemptionStatus('No fue posible abrir la cámara. Revisa el permiso del navegador o pega el código.', true); });
		});
		rewardsPanel.querySelector('.lw-scan-another').addEventListener('click', function () { customerStage.hidden = true; codeInput.value = ''; activeCode = ''; setRedemptionStatus('', false); codeInput.focus(); });
		document.querySelectorAll('.lw-delete-reward').forEach(function (button) { button.addEventListener('click', function (event) { if (!window.confirm('¿Eliminar este producto canjeable?')) event.preventDefault(); }); });
		window.addEventListener('beforeunload', stopRedemptionCamera);
	}
	var requestedTab = new URLSearchParams(window.location.search).get('lw_tab');
	if (requestedTab === 'customers') selectTab('customers');
	else if (requestedTab === 'rewards' && rewardsTab) selectTab('rewards');
	else if (requestedTab === 'reviews' && reviewTab) selectTab('reviews');
	else if (requestedTab === 'google-loyalty' && googleLoyaltyTab) selectTab('google-loyalty');
	else if (requestedTab === 'messages' && messagesTab) selectTab('messages');
	else if (requestedTab === 'loyalty' && loyaltyTab) selectTab('loyalty');
	else selectTab(configurationTab ? 'name' : 'customers');
}());
