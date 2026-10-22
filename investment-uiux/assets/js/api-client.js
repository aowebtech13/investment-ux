/**
 * Investment API Client Library (Axios-based)
 * Connects frontend (investment-uiux) to Laravel backend API
 */

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
    typeof define === 'function' && define.amd ? define(factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.InvestmentAPI = factory());
})(this, function () {
    'use strict';

    // Configuration
    const defaultConfig = {
        baseURL: window.API_BASE_URL || 'http://localhost:8000/api',
        timeout: 30000,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    class ApiClient {
        constructor(config = {}) {
            this.config = Object.assign({}, defaultConfig, config);
            this.instance = null;
            this.init();
        }

        init() {
            // Create axios instance if axios is available
            if (typeof axios !== 'undefined') {
                this.instance = axios.create({
                    baseURL: this.config.baseURL,
                    timeout: this.config.timeout,
                    headers: this.config.headers,
                    withCredentials: true
                });

                this.setupInterceptors();
            } else {
                console.error('Axios is not loaded. Please include axios.min.js before this library.');
            }
        }

        setupInterceptors() {
            // Request interceptor - attach auth token
            this.instance.interceptors.request.use(
                (config) => {
                    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                    if (token) {
                        config.headers.Authorization = `Bearer ${token}`;
                    }
                    return config;
                },
                (error) => {
                    return Promise.reject(error);
                }
            );

            // Response interceptor - handle errors
            this.instance.interceptors.response.use(
                (response) => {
                    return response;
                },
                (error) => {
                    if (error.response) {
                        // Handle specific error status codes
                        if (error.response.status === 401) {
                            // Unauthorized - clear token and redirect to login
                            localStorage.removeItem('auth_token');
                            sessionStorage.removeItem('auth_token');
                            if (window.location.pathname.indexOf('login') === -1) {
                                // Optional: redirect to login
                                // window.location.href = 'investment-login.html';
                            }
                        }
                    }
                    return Promise.reject(error);
                }
            );
        }

        // Auth methods
        login(credentials) {
            return this.instance.post('/login', credentials);
        }

        register(userData) {
            return this.instance.post('/register', userData);
        }

        logout() {
            return this.instance.post('/logout').finally(() => {
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
            });
        }

        getUser() {
            return this.instance.get('/user');
        }

        // Dashboard
        getDashboardData() {
            return this.instance.get('/dashboard-data');
        }

        // Investments
        getInvestments() {
            return this.instance.get('/investments');
        }

        getInvestmentPlans() {
            return this.instance.get('/investment-plans');
        }

        invest(data) {
            return this.instance.post('/invest', data);
        }

        cancelInvestment(id) {
            return this.instance.post(`/investments/${id}/cancel`);
        }

        // Transactions
        getTransactions(params = {}) {
            return this.instance.get('/transactions', { params });
        }

        downloadTransactions(params = {}) {
            return this.instance.get('/transactions/download', { 
                params,
                responseType: 'blob'
            });
        }

        // Deposits
        getDeposits() {
            return this.instance.get('/deposits');
        }

        deposit(data) {
            return this.instance.post('/deposit', data);
        }

        verifyPaystack(data) {
            return this.instance.post('/deposit/paystack/verify', data);
        }

        // Withdrawals
        getWithdrawals() {
            return this.instance.get('/withdrawals');
        }

        withdraw(data) {
            return this.instance.post('/withdraw', data);
        }

        downloadWithdrawalInvoice(id) {
            return this.instance.get(`/withdrawals/${id}/invoice`, {
                responseType: 'blob'
            });
        }

        // Profile
        getProfile() {
            return this.instance.get('/profile');
        }

        updateProfile(data) {
            return this.instance.post('/profile', data);
        }

        updatePassword(data) {
            return this.instance.post('/profile/password', data);
        }

        updateWithdrawalDetails(data) {
            return this.instance.post('/profile/withdrawal-details', data);
        }

        getReferrals() {
            return this.instance.get('/profile/referrals');
        }

        // Daily Login Bonus
        getDailyBonusStatus() {
            return this.instance.get('/daily-login-bonus/status');
        }

        claimDailyBonus() {
            return this.instance.post('/daily-login-bonus/claim');
        }

        // Trading Signals
        getTradingSignals(params = {}) {
            return this.instance.get('/trading-signals', { params });
        }

        getFeaturedSignals() {
            return this.instance.get('/trading-signals/featured');
        }

        getSignalById(id) {
            return this.instance.get(`/trading-signals/${id}`);
        }

        getAiSignals() {
            return this.instance.get('/trading-signals/ai');
        }

        getAiSignalsGrouped() {
            return this.instance.get('/trading-signals/ai/grouped');
        }

        getSignalHistory() {
            return this.instance.get('/trading-signals/history');
        }

        getAssetTypes() {
            return this.instance.get('/trading-assets');
        }

        // Products
        getProducts(params = {}) {
            return this.instance.get('/products', { params });
        }

        getProductById(id) {
            return this.instance.get(`/products/${id}`);
        }

        // Services & Partnerships
        getServices() {
            return this.instance.get('/services');
        }

        getPartnerships() {
            return this.instance.get('/partnerships');
        }

        getPartnershipById(id) {
            return this.instance.get(`/partnerships/${id}`);
        }

        // Generic methods
        get(url, config = {}) {
            return this.instance.get(url, config);
        }

        post(url, data = {}, config = {}) {
            return this.instance.post(url, data, config);
        }

        put(url, data = {}, config = {}) {
            return this.instance.put(url, data, config);
        }

        patch(url, data = {}, config = {}) {
            return this.instance.patch(url, data, config);
        }

        delete(url, config = {}) {
            return this.instance.delete(url, config);
        }

        // Set auth token
        setToken(token) {
            if (token) {
                localStorage.setItem('auth_token', token);
            } else {
                localStorage.removeItem('auth_token');
            }
        }

        // Get auth token
        getToken() {
            return localStorage.getItem('auth_token');
        }

        // Check if authenticated
        isAuthenticated() {
            return !!this.getToken();
        }
    }

    // Create default instance
    const api = new ApiClient();

    return {
        ApiClient: ApiClient,
        api: api,
        createClient: function(config) {
            return new ApiClient(config);
        }
    };
});
