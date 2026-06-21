import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdrawals', { params });
    },

    getDetail(id) {
        return axios.get(`/withdrawals/${id}`);
    },

    create(data) {
        return axios.post('/withdrawals', data);
    },

    validateAmount(data) {
        return axios.post('/withdrawals/validate-amount', data);
    },

    approve(id, data = {}) {
        return axios.post(`/withdrawals/${id}/approve`, data);
    },

    reject(id, data) {
        return axios.post(`/withdrawals/${id}/reject`, data);
    },

    cancel(id) {
        return axios.post(`/withdrawals/${id}/cancel`);
    },

    process(id, data = {}) {
        return axios.post(`/withdrawals/${id}/process`, data);
    },

    complete(id, data = {}) {
        return axios.post(`/withdrawals/${id}/complete`, data);
    },

    fail(id, data) {
        return axios.post(`/withdrawals/${id}/fail`, data);
    },

    batchApprove(data) {
        return axios.post('/withdrawals/batch-approve', data);
    },

    batchReject(data) {
        return axios.post('/withdrawals/batch-reject', data);
    },

    getStatistics(params = {}) {
        return axios.get('/withdrawals/statistics', { params });
    },

    getPendingCount() {
        return axios.get('/withdrawals/pending-count');
    },
};
