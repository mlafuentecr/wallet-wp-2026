(function () {
	'use strict';
	var reviewTab = document.getElementById('lw-review-tab');
	var nameTab = document.getElementById('lw-name-tab');
	var customersTab = document.getElementById('lw-customers-tab');
	var activityTab = document.getElementById('lw-activity-tab');
	var configurationTab = document.getElementById('lw-configuration-tab');
	var googleLoyaltyTab = document.getElementById('lw-google-loyalty-tab');
	var loyaltyTab = document.getElementById('lw-loyalty-tab');
	var qrPanel = document.getElementById('lw-qr-settings');
	var googleLoyaltyPanel = document.getElementById('lw-google-loyalty-settings');
	var namePanel = document.getElementById('lw-name-settings');
	var customersPanel = document.getElementById('lw-customers-panel');
	var activityPanel = document.getElementById('lw-activity-panel');
	var loyaltyPanel = document.getElementById('lw-loyalty-settings');
	var section = document.getElementById('lw-settings-section');
	var settingsSubmit = document.querySelector('#lw-code-editor > form #submit');
	var nameInput = document.getElementById('lw-wallet-name');
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
		var editingAccess = selected === 'loyalty';
		var editingConfiguration = editingName || editingReviews || editingGoogleLoyalty || editingAccess;
		if (nameTab) nameTab.classList.toggle('is-active', editingName);
		customersTab.classList.toggle('is-active', selected === 'customers');
		activityTab.classList.toggle('is-active', selected === 'activity');
		if (configurationTab) configurationTab.classList.toggle('is-active', editingConfiguration);
		if (reviewTab) reviewTab.classList.toggle('is-active', editingReviews);
		if (googleLoyaltyTab) googleLoyaltyTab.classList.toggle('is-active', editingGoogleLoyalty);
		if (loyaltyTab) loyaltyTab.classList.toggle('is-active', editingAccess);
		if (nameTab) nameTab.setAttribute('aria-selected', editingName ? 'true' : 'false');
		customersTab.setAttribute('aria-selected', selected === 'customers' ? 'true' : 'false');
		activityTab.setAttribute('aria-selected', selected === 'activity' ? 'true' : 'false');
		if (configurationTab) configurationTab.setAttribute('aria-selected', editingConfiguration ? 'true' : 'false');
		if (reviewTab) reviewTab.setAttribute('aria-selected', editingReviews ? 'true' : 'false');
		if (googleLoyaltyTab) googleLoyaltyTab.setAttribute('aria-selected', editingGoogleLoyalty ? 'true' : 'false');
		if (loyaltyTab) loyaltyTab.setAttribute('aria-selected', editingAccess ? 'true' : 'false');
		namePanel.hidden = !editingName;
		customersPanel.hidden = selected !== 'customers';
		activityPanel.hidden = selected !== 'activity';
		if (qrPanel) qrPanel.hidden = !editingReviews;
		if (googleLoyaltyPanel) googleLoyaltyPanel.hidden = !editingGoogleLoyalty;
		if (loyaltyPanel) loyaltyPanel.hidden = !editingAccess;
		setPanelFields(namePanel, editingName);
		setPanelFields(qrPanel, editingReviews);
		setPanelFields(googleLoyaltyPanel, editingGoogleLoyalty);
		section.value = editingName ? 'name' : (editingReviews ? 'reviews' : (editingGoogleLoyalty ? 'google_loyalty' : ''));
		if (settingsSubmit) settingsSubmit.style.display = selected === 'customers' || selected === 'activity' || editingAccess || editingGoogleLoyalty ? 'none' : '';
		if (editingName && nameInput) nameInput.focus();
		else if (editingReviews && placeInput) placeInput.focus();
		else if (editingGoogleLoyalty) {
			var loyaltySummary = googleLoyaltyPanel ? googleLoyaltyPanel.querySelector('summary') : null;
			if (loyaltySummary) loyaltySummary.focus();
		}
	}
	if (nameTab) nameTab.addEventListener('click', function () { selectTab('name'); });
	customersTab.addEventListener('click', function () { selectTab('customers'); });
	activityTab.addEventListener('click', function () { selectTab('activity'); });
	if (configurationTab) configurationTab.addEventListener('click', function () { selectTab('name'); });
	if (reviewTab) reviewTab.addEventListener('click', function () { selectTab('reviews'); });
	if (googleLoyaltyTab && googleLoyaltyPanel) googleLoyaltyTab.addEventListener('click', function () { selectTab('google-loyalty'); });
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
	var walletHeroInput = document.getElementById('lw-wallet-hero-upload'), walletHeroPreview = document.getElementById('lw-wallet-hero-preview');
	if (walletHeroInput && walletHeroPreview) walletHeroInput.addEventListener('change', function () {
		var file = walletHeroInput.files && walletHeroInput.files[0];
		if (!file) return;
		if (!/^image\/(png|jpeg|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { walletHeroInput.value = ''; window.alert('Choose a PNG, JPG or WebP banner under 5 MB.'); return; }
		var heroMediaId = document.getElementById('lw-google-wallet-hero-media-id');
		var heroUrl = document.getElementById('lw-wallet-hero-url');
		if (heroMediaId) heroMediaId.value = '';
		if (heroUrl) heroUrl.value = '';
		var heroReader = new FileReader(); heroReader.onload = function (event) { walletHeroPreview.innerHTML = ''; var heroImage = document.createElement('img'); heroImage.src = event.target.result; heroImage.alt = 'New Google Wallet banner preview'; walletHeroPreview.appendChild(heroImage); }; heroReader.readAsDataURL(file);
	});
	var walletColor = document.getElementById('lw-wallet-background-color'), walletColorValue = document.getElementById('lw-wallet-background-color-value');
	if (walletColor && walletColorValue) walletColor.addEventListener('input', function () { walletColorValue.textContent = walletColor.value.toUpperCase(); });
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
				if (mediaInput) mediaInput.value = selected.id;
				if (fileInput) fileInput.value = '';
				if (clearUrl) clearUrl.value = '';
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
	var requestedTab = new URLSearchParams(window.location.search).get('lw_tab');
	if (requestedTab === 'customers') selectTab('customers');
	else if (requestedTab === 'reviews' && reviewTab) selectTab('reviews');
	else if (requestedTab === 'google-loyalty' && googleLoyaltyTab) selectTab('google-loyalty');
	else if (requestedTab === 'loyalty' && loyaltyTab) selectTab('loyalty');
	else selectTab(configurationTab ? 'name' : 'customers');
}());
