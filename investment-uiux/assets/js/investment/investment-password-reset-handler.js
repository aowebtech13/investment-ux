/*!
 * Password Reset Handler - Connects forgot/reset password forms to the backend API
 */

(function (window) {
    'use strict';

    if (!window.API) {
        console.error('API not loaded. Make sure axios-library.js is included.');
        return;
    }

    var API = window.API;

    /**
     * Show error message on the form
     */
    function showError(message) {
        var existing = document.querySelector('.auth-error-message');
        if (existing) existing.remove();

        var errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger auth-error-message mt-3';
        errorDiv.style.cssText = 'font-size: 0.875rem; margin-top: 1rem;';
        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + message;

        var submitBtn = document.getElementById('resetNowBtn') || document.getElementById('changeNowBtn');
        if (submitBtn && submitBtn.parentNode) {
            submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
        } else {
            var form = document.querySelector('.login-box');
            if (form) form.appendChild(errorDiv);
        }

        setTimeout(function () {
            if (errorDiv.parentNode) errorDiv.parentNode.removeChild(errorDiv);
        }, 5000);
    }

    /**
     * Handle Forgot Password form submission (sends reset OTP)
     */
    function handleForgotSubmit(e) {
        e.preventDefault();

        var email = document.getElementById('emailadd')?.value?.trim() || '';

        if (!email) {
            showError('Please enter your email address.');
            return;
        }

        var btn = document.getElementById('resetNowBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Sending OTP...';
        }

        API.auth.forgotPassword(email)
            .then(function () {
                sessionStorage.setItem('reset_email', email);
                window.location.href = 'investment-change-password.html';
            })
            .catch(function (error) {
                var message = 'Failed to send reset OTP. Please try again.';
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        message = error.response.data.message;
                    } else if (error.response.data.errors) {
                        var errors = error.response.data.errors;
                        message = Object.values(errors).flat().join('\n');
                    }
                }
                showError(message);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Reset Now';
                }
            });
    }

    /**
     * Handle Reset Password form submission (verifies OTP and sets new password)
     */
    function handleResetSubmit(e) {
        e.preventDefault();

        var email = document.getElementById('resetEmail')?.value?.trim() || '';
        var otp = document.getElementById('resetOTP')?.value?.trim() || '';
        var password = document.getElementById('checkstrength')?.value || '';
        var confirmPassword = document.getElementById('passwdconfirm')?.value || '';

        if (!email || !otp || !password || !confirmPassword) {
            showError('Please fill in all fields.');
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

        var btn = document.getElementById('changeNowBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Changing password...';
        }

        API.auth.resetPassword({
            email: email,
            otp: otp,
            password: password,
            password_confirmation: confirmPassword
        })
            .then(function () {
                alert('Password reset successfully. Please login with your new password.');
                window.location.href = 'investment-login.html';
            })
            .catch(function (error) {
                var message = 'Failed to reset password. Please try again.';
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        message = error.response.data.message;
                    } else if (error.response.data.errors) {
                        var errors = error.response.data.errors;
                        message = Object.values(errors).flat().join('\n');
                    }
                }
                showError(message);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Change Now';
                }
            });
    }

    /**
     * Initialize based on page
     */
    function init() {
        var forgotForm = document.getElementById('forgotPasswordForm');
        if (forgotForm) {
            forgotForm.addEventListener('submit', handleForgotSubmit);
        }

        var resetForm = document.getElementById('resetPasswordForm');
        if (resetForm) {
            resetForm.addEventListener('submit', handleResetSubmit);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window);
