(function (window) {
    'use strict';

    if (!window.API || !window.ApiUtils) {
        console.error('API or ApiUtils not loaded. Make sure axios-library.js is included.');
        return;
    }

    const API = window.API;
    const ApiUtils = window.ApiUtils;

    let inFlight = null;

    function showError(message) {
        const existing = document.querySelector('.auth-error-message');
        if (existing) existing.remove();
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger auth-error-message mt-3';
        errorDiv.style.cssText = 'font-size: 0.875rem; margin-top: 1rem;';
        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + String(message).replace(/\n/g, '<br>');
        const submitBtn = document.querySelector('.btn-theme.w-100');
        if (submitBtn && submitBtn.parentNode) {
            submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
        } else {
            const box = document.querySelector('.login-box');
            if (box) box.appendChild(errorDiv);
        }
        setTimeout(() => { if (errorDiv.parentNode) errorDiv.parentNode.removeChild(errorDiv); }, 6000);
    }

    function clearError() {
        const existing = document.querySelector('.auth-error-message');
        if (existing) existing.remove();
    }

    function setLoading(btn, text) {
        if (!btn) return;
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + text;
    }

    function resetLoading(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText;
    }

    function handleRegisterSubmit(e) {
        e.preventDefault();
        if (inFlight) return;
        clearError();

        const firstName = (document.getElementById('namef')?.value || '').trim();
        const lastName = (document.getElementById('namel')?.value || '').trim();
        const email = (document.getElementById('emailadd')?.value || '').trim();
        const phoneCode = document.getElementById('code')?.value || '+44';
        const phoneNumber = (document.getElementById('phonen')?.value || '').trim();
        const password = document.getElementById('checkstrength')?.value || '';
        const confirmPassword = document.getElementById('passwd')?.value || '';

        if (!firstName || !lastName) { showError('Please enter first and last name.'); return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Please enter a valid email.'); return; }
        if (!phoneNumber) { showError('Please enter phone number.'); return; }
        if (password.length < 8) { showError('Password must be at least 8 characters.'); return; }
        if (password !== confirmPassword) { showError('Passwords do not match.'); return; }

        const submitBtn = document.querySelector('.btn-theme.w-100');
        const controller = new AbortController();
        inFlight = controller;

        const formData = {
            fullname: firstName + ' ' + lastName,
            username: email.split('@')[0] + Math.floor(Math.random() * 10000),
            email: email,
            phone: (phoneCode + ' ' + phoneNumber).trim(),
            password: password,
            password_confirmation: confirmPassword
        };

        setLoading(submitBtn, 'Creating account...');
        API.auth.register(formData)
            .then((response) => {
                const data = response.data || {};
                if (data.access_token) {
                    ApiUtils.setToken(data.access_token);
                    if (data.user) ApiUtils.setUser(data.user);
                    sessionStorage.setItem('verification_email', email);
                    if (data.requires_email_verification) {
                        window.location.replace('investment-verify-email.html');
                    } else {
                        window.location.replace('investment-signup-success.html');
                    }
                } else {
                    showError(data.message || 'Registration failed. Please try again.');
                }
            })
            .catch((error) => {
                let message = 'Registration failed. Please try again.';
                const resp = error?.response?.data;
                if (resp?.errors) {
                    message = Object.values(resp.errors).flat().join('\n');
                } else if (resp?.message) {
                    message = resp.message;
                } else if (error?.code === 'ERR_CANCELED') {
                    message = 'Request cancelled.';
                }
                showError(message);
            })
            .finally(() => {
                inFlight = null;
                resetLoading(submitBtn);
            });
    }

    function handleLoginSubmit(e) {
        e.preventDefault();
        if (inFlight) return;
        clearError();

        const email = (document.getElementById('emailadd')?.value || '').trim();
        const password = document.getElementById('passwd')?.value || '';
        const rememberMe = !!document.getElementById('rememberme')?.checked;

        if (!email || !password) { showError('Please enter email and password.'); return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Please enter a valid email.'); return; }

        const submitBtn = document.querySelector('.btn-theme.w-100');
        const controller = new AbortController();
        inFlight = controller;

        setLoading(submitBtn, 'Logging in...');
        API.auth.login(email, password)
            .then((response) => {
                const data = response.data || {};
                if (data.access_token) {
                    if (rememberMe) {
                        localStorage.setItem('auth_token', data.access_token);
                    } else {
                        sessionStorage.setItem('auth_token', data.access_token);
                        localStorage.removeItem('auth_token');
                    }
                    if (data.user) ApiUtils.setUser(data.user);
                    if (data.user && !data.user.email_verified_at) {
                        sessionStorage.setItem('verification_email', email);
                        window.location.replace('investment-verify-email.html');
                    } else {
                        window.location.replace('investment-dashboard.html');
                    }
                } else {
                    showError(data.message || 'Login failed. Please try again.');
                }
            })
            .catch((error) => {
                let message = 'Login failed. Please try again.';
                const resp = error?.response?.data;
                if (resp?.errors) {
                    message = Object.values(resp.errors).flat().join('\n');
                } else if (resp?.message) {
                    message = resp.message;
                }
                showError(message);
            })
            .finally(() => {
                inFlight = null;
                resetLoading(submitBtn);
            });
    }

    function init() {
        const signupBtn = document.getElementById('signupBtn');
        if (signupBtn) {
            signupBtn.addEventListener('click', handleRegisterSubmit);
        }
        const loginBtn = document.getElementById('loginBtn');
        if (loginBtn) {
            loginBtn.addEventListener('click', handleLoginSubmit);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);