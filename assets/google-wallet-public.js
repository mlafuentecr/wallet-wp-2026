(function () {
	'use strict';

	var root = document.getElementById('lw-google-enrollment');
	if (!root) return;

	var signinStage = document.getElementById('lw-google-signin-stage');
	var phoneStage = document.getElementById('lw-google-phone-stage');
	var walletStage = document.getElementById('lw-google-wallet-stage');
	var status = document.getElementById('lw-google-enrollment-status');
	var tokenInput = document.getElementById('lw-google-enrollment-token');
	var phoneForm = document.getElementById('lw-google-phone-form');
	var phoneInput = document.getElementById('lw-google-phone');
	var profileName = document.getElementById('lw-google-profile-name');
	var profileEmail = document.getElementById('lw-google-profile-email');
	var profilePicture = document.getElementById('lw-google-profile-picture');
	var profilePlaceholder = document.getElementById('lw-google-profile-placeholder');
	var walletLink = document.getElementById('lw-personal-wallet-link');

	function setStatus(message, isError) {
		status.textContent = message || '';
		status.classList.toggle('is-error', !!isError);
	}

	function request(data) {
		return fetch(window.location.href, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
			body: new URLSearchParams(data).toString()
		}).then(function (response) {
			return response.json().then(function (payload) {
				if (!response.ok || !payload.success) throw new Error(payload.message || 'The request could not be completed.');
				return payload.data;
			});
		});
	}

	window.loyaltyWalletGoogleCredential = function (response) {
		if (!response || !response.credential) {
			setStatus('Google Sign-In did not return a valid credential.', true);
			return;
		}
		setStatus('Verifying your Google account…', false);
		request({ lw_identity_action: 'verify', credential: response.credential })
			.then(function (identity) {
				tokenInput.value = identity.enrollment_token;
				profileName.textContent = identity.name;
				profileEmail.textContent = identity.email;
				if (identity.picture) {
					profilePicture.src = identity.picture;
					profilePicture.alt = identity.name + ' profile photo';
					profilePicture.hidden = false;
					profilePlaceholder.hidden = true;
				}
				signinStage.hidden = true;
				phoneStage.hidden = false;
				setStatus('Google account verified.', false);
				phoneInput.focus();
			})
			.catch(function (error) {
				setStatus(error.message, true);
			});
	};

	phoneForm.addEventListener('submit', function (event) {
		event.preventDefault();
		if (!phoneForm.reportValidity()) return;
		var submit = phoneForm.querySelector('button[type=submit]');
		submit.disabled = true;
		setStatus('Creating your customer profile and loyalty card…', false);
		request({
			lw_identity_action: 'complete',
			enrollment_token: tokenInput.value,
			phone: phoneInput.value
		})
			.then(function (result) {
				walletLink.href = result.walletUrl;
				phoneStage.hidden = true;
				walletStage.hidden = false;
				setStatus('', false);
			})
			.catch(function (error) {
				submit.disabled = false;
				setStatus(error.message, true);
			});
	});
}());
