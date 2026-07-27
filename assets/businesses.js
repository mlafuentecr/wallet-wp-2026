(function () {
	'use strict';

	function normalize(value) {
		return (value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
	}

	function bindSearch(inputId, rowSelector, dataKey, emptyId) {
		var input = document.getElementById(inputId);
		var rows = Array.prototype.slice.call(document.querySelectorAll(rowSelector));
		var empty = document.getElementById(emptyId);
		if (!input || !rows.length || !empty) return;

		input.addEventListener('input', function () {
			var terms = normalize(input.value).split(' ').filter(Boolean);
			var visible = 0;
			rows.forEach(function (row) {
				var haystack = normalize(row.dataset[dataKey]);
				var matches = terms.every(function (term) { return haystack.indexOf(term) !== -1; });
				row.hidden = !matches;
				if (matches) visible++;
			});
			empty.hidden = visible !== 0;
		});
	}

	bindSearch('lw-business-search', '.lw-business-row', 'businessSearch', 'lw-business-search-empty');
	bindSearch('lw-business-customer-search', '.lw-business-customer-row', 'customerSearch', 'lw-business-customer-search-empty');

	var newBusinessButton = document.querySelector('.lw-new-business-button');
	var newBusinessPanel = document.getElementById('nuevo-negocio');
	if (newBusinessButton && newBusinessPanel) {
		newBusinessButton.addEventListener('click', function () {
			newBusinessPanel.open = true;
			window.setTimeout(function () {
				var firstField = newBusinessPanel.querySelector('input:not([type="hidden"])');
				if (firstField) firstField.focus({ preventScroll: true });
			}, 150);
		});
	}

	document.querySelectorAll('.lw-edit-business-toggle').forEach(function (button) {
		button.addEventListener('click', function () {
			var row = document.getElementById(button.getAttribute('aria-controls'));
			if (!row) return;
			row.hidden = false;
			button.setAttribute('aria-expanded', 'true');
			var firstField = row.querySelector('input:not([type="hidden"])');
			if (firstField) firstField.focus();
		});
	});
	document.querySelectorAll('.lw-cancel-business-edit').forEach(function (button) {
		button.addEventListener('click', function () {
			var row = button.closest('.lw-business-edit-row');
			if (!row) return;
			row.hidden = true;
			var toggle = document.querySelector('[aria-controls="' + row.id + '"]');
			if (toggle) toggle.setAttribute('aria-expanded', 'false');
		});
	});
}());
