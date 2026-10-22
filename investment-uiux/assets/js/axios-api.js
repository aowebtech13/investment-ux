/**
 * Axios HTTP Client Library
 * Simple wrapper for connecting frontend to backend
 */

const API_CONFIG = {
    baseURL: 'http://localhost:8000/api',
    timeout: 10000,
};

// Create axios instance
const httpClient = axios.create({
    baseURL: API_CONFIG.baseURL,
    timeout: API_CONFIG.timeout,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    withCredentials: true
});

// Request interceptor
httpClient.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error)
);

// Response interceptor
httpClient.interceptors.response.use(
    response => response.data,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('auth_token');
        }
        return Promise.reject(error);
    }
);

const ApiService = {
    // Auth
    auth: {
        login: (data) => httpClient.post('/login', data),
        register: (data) => httpClient.post('/register', data),
        logout: () => httpClient.post('/logout'),
        user: () => httpClient.get('/user')
    },
    
    // User
    user: {
        profile: () => httpClient.get('/profile'),
        updateProfile: (data) => httpClient.post('/profile', data),
        updatePassword: (data) => httpClient.post('/profile/password', data),
        referrals: () => httpClient.get('/profile/referrals')
    },
    
    // Dashboard
    dashboard: {
        getData: () => httpClient.get('/dashboard-data')
    },
    
    // Investments
    investments: {
        plans: () => httpClient.get('/investment-plans'),
        list: () => httpClient.get('/investments'),
        invest: (data) => httpClient.post('/invest', data),
        cancel: (id) => httpClient.post(`/investments/${id}/cancel`)
    },
    
    // Transactions
    transactions: {
        list: (params) => httpClient.get('/transactions', { params }),
        download: () => httpClient.get('/transactions/download', { responseType: 'blob' })
    },
    
    // Deposits
    deposits: {
        list: () => httpClient.get('/deposits'),
        create: (data) => httpClient.post('/deposit', data),
        verifyPaystack: (data) => httpClient.post('/deposit/paystack/verify', data)
    },
    
    // Withdrawals
    withdrawals: {
        list: () => httpClient.get('/withdrawals'),
        create: (data) => httpClient.post('/withdraw', data),
        invoice: (id) => httpClient.get(`/withdrawals/${id}/invoice`, { responseType: 'blob' })
    },
    
    // Trading Signals
    signals: {
        all: (params) => httpClient.get('/trading-signals', { params }),
        featured: () => httpClient.get('/trading-signals/featured'),
        ai: () => httpClient.get('/trading-signals/ai'),
        aiGrouped: () => httpClient.get('/trading-signals/ai/grouped'),
        history: () => httpClient.get('/trading-signals/history'),
        getById: (id) => httpClient.get(`/trading-signals/${id}`)
    },
    
    // Products
    products: {
        list: (params) => httpClient.get('/products', { params }),
        getById: (id) => httpClient.get(`/products/${id}`)
    },
    
    // Generic
    get: (url, config) => httpClient.get(url, config),
    post: (url, data, config) => httpClient.post(url, data, config),
    put: (url, data, config) => httpClient.put(url, data, config),
    delete: (url, config) => httpClient.delete(url, config)
};

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { httpClient, ApiService, API_CONFIG };
}
if (typeof window !== 'undefined') {
    window.httpClient = httpClient;
    window.ApiService = ApiService;
    window.API_CONFIG = API_CONFIG;
}
