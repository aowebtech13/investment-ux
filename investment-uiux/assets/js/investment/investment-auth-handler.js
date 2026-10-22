/*!
 * Auth Handler - Connects Register and Login forms to the backend API
 */

(function (window) {
    'use strict';

    // Ensure API and Utils are available
    if (!window.API || !window.ApiUtils) {
        console.error('API or ApiUtils not loaded. Make sure axios-library.js is included.');
        return;
    }

    var API = window.API;
    var ApiUtils = window.ApiUtils;

    /**
     * Handle Register form submission
     */
    function handleRegisterSubmit(e) {
        e.preventDefault();

        var firstName = document.getElementById('namef')?.value?.trim() || '';
        var lastName = document.getElementById('namel')?.value?.trim() || '';
        var email = document.getElementById('emailadd')?.value?.trim() || '';
        var phoneCode = document.getElementById('code')?.value || '';
        var phoneNumber = document.getElementById('phonen')?.value?.trim() || '';
        var password = document.getElementById('checkstrength')?.value || '';
        var confirmPassword = document.getElementById('passwd')?.value || '';

        // Validate
        if (!firstName || !lastName || !email || !phoneNumber || !password) {
            showError('Please fill in all required fields.');
            return;
        }

        if (password !== confirmPassword) {
            showError('Passwords do not match.');
            return;
        }

        if (password.length < 8) {
            showError('Password must be at least 8 characters.');
            return;
        }

        // Build phone number with country code
        var fullPhone = phoneCode + ' ' + phoneNumber;

        // Generate username from email (backend requires unique username)
        var username = email.split('@')[0] + Math.floor(Math.random() * 1000);

        var formData = {
            fullname: firstName + ' ' + lastName,
            username: username,
            email: email,
            phone: fullPhone,
            password: password
        };

        // Show loading
        var submitBtn = document.querySelector('.btn-theme.w-100');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Creating account...';
        }

        API.auth.register(formData)
            .then(function (response) {
                var data = response.data;
                if (data.access_token) {
                    ApiUtils.setToken(data.access_token);
                    if (data.user) {
                        ApiUtils.setUser(data.user);
                    }
                    // Redirect to success page
                    window.location.href = 'investment-signup-success.html';
                } else {
                    showError(data.message || 'Registration failed. Please try again.');
                }
            })
            .catch(function (error) {
                var message = 'Registration failed. Please try again.';
                if (error.response && error.response.data) {
                    if (error.response.data.errors) {
                        // Validation errors
                        var errors = error.response.data.errors;
                        message = Object.values(errors).flat().join('\n');
                    } else if (error.response.data.message) {
                        message = error.response.data.message;
                    }
                }
                showError(message);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign up';
                }
            });
    }

    /**
     * Handle Login form submission
     */
    function handleLoginSubmit(e) {
        e.preventDefault();

        var email = document.getElementById('emailadd')?.value?.trim() || '';
        var password = document.getElementById('passwd')?.value || '';

        if (!email || !password) {
            showError('Please enter email and password.');
            return;
        }

        var submitBtn = document.querySelector('.btn-theme.w-100');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Logging in...';
        }

        API.auth.login(email, password)
            .then(function (response) {
                var data = response.data;
                if (data.access_token) {
                    ApiUtils.setToken(data.access_token);
                    if (data.user) {
                        ApiUtils.setUser(data.user);
                    }
                    // Redirect to dashboard
                    window.location.href = 'investment-dashboard.html';
                } else {
                    showError(data.message || 'Login failed. Please try again.');
                }
            })
            .catch(function (error) {
                var message = 'Login failed. Please try again.';
                if (error.response && error.response.data) {
                    if (error.response.data.errors) {
                        var errors = error.response.data.errors;
                        message = Object.values(errors).flat().join('\n');
                    } else if (error.response.data.message) {
                        message = error.response.data.message;
                    }
                }
                showError(message);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Login';
                }
            });
    }

    /**
     * Show error message on the form
     */
    function showError(message) {
        // Remove existing error
        var existing = document.querySelector('.auth-error-message');
        if (existing) existing.remove();

        // Create error element
        var errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger auth-error-message mt-3';
        errorDiv.style.cssText = 'font-size: 0.875rem; margin-top: 1rem;';
        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + message.replace(/\n/g, '<br>');

        // Insert before the submit button
        var submitBtn = document.querySelector('.btn-theme.w-100');
        if (submitBtn && submitBtn.parentNode) {
            submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
        } else {
            var form = document.querySelector('.login-box');
            if (form) form.appendChild(errorDiv);
        }

        // Auto remove after 5 seconds
        setTimeout(function () {
            if (errorDiv.parentNode) errorDiv.parentNode.removeChild(errorDiv);
        }, 5000);
    }

    /**
     * Initialize based on page
     */
    function init() {
        // Register page
        var registerForm = document.querySelector('.login-box form');
        if (registerForm && document.getElementById('namef')) {
            registerForm.addEventListener('submit', handleRegisterSubmit);
        }

        // Login page
        if (registerForm && document.getElementById('passwd') && !document.getElementById('namef')) {
            registerForm.addEventListener('submit', handleLoginSubmit);
        }
    }

    // Load on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window);