/*! For license information please see investment-auth.js.LICENSE.txt */
"use strict";document.addEventListener("DOMContentLoaded",(function(){setActivelink(),fixedHeaderSpace(),featherjs(),coverimg(),dontclosedd(),checkstrength(),bstooltip(),swipernavpagination(),PageLoaderHide()}));
document.addEventListener('DOMContentLoaded', function() {
    // Signup functionality
    const signupBtn = document.getElementById('signupBtn');
    if (signupBtn) {
        signupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('namef')?.value || '';
            const lastName = document.getElementById('namel')?.value || '';
            const email = document.getElementById('emailadd')?.value || '';
            const phoneCode = document.getElementById('code')?.value || '+44';
            const phone = document.getElementById('phonen')?.value || '';
            const password = document.getElementById('checkstrength')?.value || '';
            const confirmPassword = document.getElementById('passwd')?.value || '';
            
            if (!firstName || !lastName) {
                alert('Please enter first and last name');
                return;
            }
            if (!email) {
                alert('Please enter email');
                return;
            }
            if (!phone) {
                alert('Please enter phone number');
                return;
            }
            if (!password) {
                alert('Please enter password');
                return;
            }
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            
            const fullname = firstName + ' ' + lastName;
            const username = email.split('@')[0] + Math.floor(Math.random() * 1000);
            
            signupBtn.disabled = true;
            signupBtn.textContent = 'Signing up...';
            
            const baseURL = (typeof API_CONFIG !== 'undefined') ? API_CONFIG.baseURL : 'http://localhost:8000/api';
            
            fetch(baseURL + '/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                    fullname: fullname,
                    username: username,
                    email: email,
                    phone: phoneCode + phone,
                    password: password,
                    password_confirmation: confirmPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.access_token) {
                    localStorage.setItem('auth_token', data.access_token);
                    sessionStorage.setItem('verification_email', email);
                    if (data.requires_email_verification) {
                        window.location.href = 'investment-verify-email.html';
                    } else {
                        window.location.href = 'investment-signup-success.html';
                    }
                } else if (data.errors) {
                    const errorMsg = Object.values(data.errors).flat().join(', ');
                    alert('Registration failed: ' + errorMsg);
                } else {
                    alert(data.message || 'Registration failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Registration failed. Please try again.');
            })
            .finally(() => {
                signupBtn.disabled = false;
                signupBtn.textContent = 'Sign up';
            });
        });
    }
    
    // Login functionality
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('emailadd')?.value || '';
            const password = document.getElementById('passwd')?.value || '';
            const rememberMe = document.getElementById('rememberme')?.checked;
            
            if (!email) {
                alert('Please enter email');
                return;
            }
            if (!password) {
                alert('Please enter password');
                return;
            }
            
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            
            const baseURL = (typeof API_CONFIG !== 'undefined') ? API_CONFIG.baseURL : 'http://localhost:8000/api';
            
            fetch(baseURL + '/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.access_token) {
                    if (rememberMe) {
                        localStorage.setItem('auth_token', data.access_token);
                    } else {
                        sessionStorage.setItem('auth_token', data.access_token);
                    }
                    if (data.user) {
                        localStorage.setItem('user', JSON.stringify(data.user));
                    }
                    
                    if (data.user && !data.user.email_verified_at) {
                        sessionStorage.setItem('verification_email', email);
                        window.location.href = 'investment-verify-email.html';
                    } else {
                        window.location.href = 'investment-dashboard.html';
                    }
                } else if (data.errors) {
                    const errorMsg = Object.values(data.errors).flat().join(', ');
                    alert('Login failed: ' + errorMsg);
                } else {
                    alert(data.message || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Login failed. Please try again.');
            })
            .finally(() => {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            });
        });
    }
});
