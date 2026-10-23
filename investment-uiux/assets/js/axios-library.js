/*!
 * Axios Library for Frontend-Backend Connection
 * A clean Axios-based HTTP client for the Investment platform
 */

(function(window) {
    'use strict';

    // API Base URL - Update this to match your backend URL
    const BASE_URL = window.location.hostname === 'localhost' 
        ? 'http://localhost:8000/api' 
        : '/api';

    /**
     * Create Axios instance with default configuration
     */
    const api = axios.create({
        baseURL: BASE_URL,
        timeout: 30000,
        withCredentials: true,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    /**
     * Request interceptor to add auth token
     */
    api.interceptors.request.use(
        function(config) {
            const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            if (token) {
                config.headers.Authorization = 'Bearer ' + token;
            }
            return config;
        },
        function(error) {
            return Promise.reject(error);
        }
    );

    /**
     * Response interceptor for error handling
     */
    api.interceptors.response.use(
        function(response) {
            return response;
        },
        function(error) {
            if (error.response) {
                const status = error.response.status;
                
                if (status === 401) {
                    // Clear auth data on unauthorized
                    localStorage.removeItem('auth_token');
                    sessionStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    
                    // Optionally redirect to login if not already there
                    const currentPath = window.location.pathname;
                    if (currentPath.indexOf('login') === -1 && currentPath.indexOf('signup') === -1) {
                        // Uncomment to auto-redirect
                        // window.location.href = 'investment-login.html';
                    }
                } else if (status === 419) {
                    console.error('CSRF token mismatch');
                } else if (status === 422) {
                    console.error('Validation error:', error.response.data);
                }
            }
            return Promise.reject(error);
        }
    );

    /**
     * API Methods organized by resource
     */
    const API = {
        // Authentication
        auth: {
            login: function(email, password) {
                return api.post('/login', { email: email, password: password });
            },
            register: function(data) {
                return api.post('/register', data);
            },
            logout: function() {
                return api.post('/logout').then(function(response) {
                    localStorage.removeItem('auth_token');
                    sessionStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    return response;
                });
            },
            getUser: function() {
                return api.get('/user');
            },
            forgotPassword: function(email) {
                return api.post('/forgot-password-otp', { email: email });
            },
            resetPassword: function(data) {
                return api.post('/reset-password-with-otp', data);
            }
        },

        // User Profile
        profile: {
            get: function() {
                return api.get('/profile');
            },
            update: function(data) {
                return api.post('/profile', data);
            },
            updatePassword: function(data) {
                return api.post('/profile/password', data);
            },
            updateWithdrawalDetails: function(data) {
                return api.post('/profile/withdrawal-details', data);
            },
            referrals: function() {
                return api.get('/profile/referrals');
            }
        },

        // Dashboard
        dashboard: {
            getData: function() {
                return api.get('/dashboard-data');
            }
        },

        // Investments
        investments: {
            getPlans: function() {
                return api.get('/investment-plans');
            },
            getAll: function() {
                return api.get('/investments');
            },
            invest: function(amount, planId, data) {
                return api.post('/invest', {
                    amount: amount,
                    investment_plan_id: planId,
                    ...data
                });
            },
            cancel: function(id) {
                return api.post('/investments/' + id + '/cancel');
            }
        },

        // Transactions
        transactions: {
            getAll: function(params) {
                return api.get('/transactions', { params: params });
            },
            download: function(params) {
                return api.get('/transactions/download', {
                    params: params,
                    responseType: 'blob'
                });
            }
        },

        // Deposits
        deposits: {
            getAll: function() {
                return api.get('/deposits');
            },
            create: function(data) {
                return api.post('/deposit', data);
            },
            verifyPaystack: function(reference) {
                return api.post('/deposit/paystack/verify', { reference: reference });
            }
        },

        // Withdrawals
        withdrawals: {
            getAll: function() {
                return api.get('/withdrawals');
            },
            create: function(data) {
                return api.post('/withdraw', data);
            },
            getInvoice: function(id) {
                return api.get('/withdrawals/' + id + '/invoice', {
                    responseType: 'blob'
                });
            }
        },

        // Trading Signals
        tradingSignals: {
            getAll: function(params) {
                return api.get('/trading-signals', { params: params });
            },
            getFeatured: function() {
                return api.get('/trading-signals/featured');
            },
            getById: function(id) {
                return api.get('/trading-signals/' + id);
            },
            getHistory: function() {
                return api.get('/trading-signals/history');
            },
            getAi: function() {
                return api.get('/trading-signals/ai');
            },
            getAiGrouped: function() {
                return api.get('/trading-signals/ai/grouped');
            },
            getAssetTypes: function() {
                return api.get('/trading-assets');
            }
        },

        // Products
        products: {
            getAll: function(params) {
                return api.get('/products', { params: params });
            },
            getById: function(id) {
                return api.get('/products/' + id);
            }
        },

        // Misc
        services: function() {
            return api.get('/services');
        },
        partnerships: function() {
            return api.get('/partnerships');
        },

        // Daily Bonus
        dailyBonus: {
            getStatus: function() {
                return api.get('/daily-login-bonus/status');
            },
            claim: function() {
                return api.post('/daily-login-bonus/claim');
            }
        }
    };

    // Helper functions
    const Utils = {
        setToken: function(token) {
            if (token) {
                localStorage.setItem('auth_token', token);
            }
        },
        getToken: function() {
            return localStorage.getItem('auth_token');
        },
        removeToken: function() {
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('auth_token');
        },
        isLoggedIn: function() {
            return !!this.getToken();
        },
        setUser: function(user) {
            localStorage.setItem('user', JSON.stringify(user));
        },
        getUser: function() {
            const user = localStorage.getItem('user');
            return user ? JSON.parse(user) : null;
        }
    };

    // Export to window
    window.axiosInstance = api;
    window.API = API;
    window.ApiUtils = Utils;

})(window);
