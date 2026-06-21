import axios from './axios';

export default {
    getBalance() {
        return axios.get('/wallet/balance');
    },

    getTransactions(params = {}) {
        return axios.get('/wallet/transactions', { params });
    },

    getStatistics(params = {}) {
        return axios.get('/wallet/statistics', { params });
    },

    getDistributorBalance(distributorId) {
        return axios.get(`/wallet/distributors/${distributorId}/balance`);
    },

    getDistributorTransactions(distributorId, params = {}) {
        return axios.get(`/wallet/distributors/${distributorId}/transactions`, { params });
    },

    getDistributorStatistics(distributorId, params = {}) {
        return axios.get(`/wallet/distributors/${distributorId}/statistics`, { params });
    },

    adjustBalance(distributorId, data) {
        return axios.post(`/wallet/distributors/${distributorId}/adjust`, data);
    },

    transfer(data) {
        return axios.post('/wallet/transfer', data);
    },
};
