(function () {
	'use strict';

	var search = document.getElementById('lw-business-search');
	var statusFilter = document.getElementById('lw-business-status-filter');
	var planFilter = document.getElementById('lw-business-plan-filter');
	var sortControl = document.getElementById('lw-business-sort');
	var body = document.getElementById('lw-business-table-body');
	var empty = document.getElementById('lw-business-search-empty');
	var resultCount = document.getElementById('lw-business-result-count');

	function normalize(value) {
		return (value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
	}

	function getPairs() {
		if (!body) return [];
		return Array.prototype.slice.call(body.querySelectorAll('.lw-business-row')).map(function (row) {
			var editRow = row.nextElementSibling;
			return { row: row, edit: editRow && editRow.classList.contains('lw-business-edit-row') ? editRow : null };
		});
	}

	function comparePairs(a, b) {
		var mode = sortControl ? sortControl.value : 'next';
		if (mode === 'name') return a.row.dataset.businessName.localeCompare(b.row.dataset.businessName);
		if (mode === 'recent') return b.row.dataset.businessStarted.localeCompare(a.row.dataset.businessStarted);
		if (mode === 'price') return Number(b.row.dataset.businessPrice) - Number(a.row.dataset.businessPrice);
		return a.row.dataset.businessNext.localeCompare(b.row.dataset.businessNext);
	}

	function applyFilters() {
		var query = normalize(search ? search.value : '');
		var status = statusFilter ? statusFilter.value : '';
		var plan = planFilter ? planFilter.value : '';
		var visible = 0;
		var pairs = getPairs().sort(comparePairs);
		pairs.forEach(function (pair) {
			var matches = normalize(pair.row.dataset.businessSearch).indexOf(query) !== -1 &&
				(!status || pair.row.dataset.businessStatus === status) &&
				(!plan || pair.row.dataset.businessPlan === plan);
			pair.row.hidden = !matches;
			if (pair.edit) {
				pair.edit.hidden = true;
				body.appendChild(pair.row);
				body.appendChild(pair.edit);
			} else {
				body.appendChild(pair.row);
			}
			if (matches) visible++;
		});
		if (empty) empty.hidden = visible !== 0 || pairs.length === 0;
		if (resultCount) resultCount.textContent = 'Mostrando ' + visible + ' de ' + pairs.length + ' cuentas';
	}

	[search, statusFilter, planFilter, sortControl].forEach(function (control) {
		if (!control) return;
		control.addEventListener(control === search ? 'input' : 'change', applyFilters);
	});

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

	function closeMenus(except) {
		document.querySelectorAll('.lw-action-menu-toggle[aria-expanded="true"]').forEach(function (toggle) {
			if (toggle === except) return;
			toggle.setAttribute('aria-expanded', 'false');
			var panel = toggle.nextElementSibling;
			if (panel) panel.hidden = true;
		});
	}

	document.querySelectorAll('.lw-action-menu-toggle').forEach(function (toggle) {
		toggle.addEventListener('click', function (event) {
			event.stopPropagation();
			var willOpen = toggle.getAttribute('aria-expanded') !== 'true';
			closeMenus(toggle);
			toggle.setAttribute('aria-expanded', String(willOpen));
			var panel = toggle.nextElementSibling;
			if (panel) panel.hidden = !willOpen;
		});
	});
	document.addEventListener('click', function () { closeMenus(); });
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') closeMenus();
	});
	document.querySelectorAll('.lw-action-menu-panel').forEach(function (panel) {
		panel.addEventListener('click', function (event) { event.stopPropagation(); });
	});

	document.querySelectorAll('.lw-edit-business-toggle').forEach(function (button) {
		button.addEventListener('click', function () {
			var row = document.getElementById(button.getAttribute('aria-controls'));
			if (!row) return;
			closeMenus();
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

	applyFilters();
}());
