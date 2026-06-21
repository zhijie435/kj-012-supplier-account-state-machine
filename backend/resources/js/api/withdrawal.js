import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdrawal-v2', { params });
    },

    getStatistics(params = {}) {
        return axios.get('/withdrawal-v2/statistics', { params });
    },

    getStatusOptions() {
        return axios.get('/withdrawal-v2/status-options');
    },

    calculateFee(data) {
        return axios.post('/withdrawal-v2/calculate-fee', data);
    },

    apply(data) {
        return axios.post('/withdrawal-v2/apply', data);
    },

    getDetail(id) {
        return axios.get(`/withdrawal-v2/${id}`);
    },

    approve(id, remark = '') {
        return axios.post(`/withdrawal-v2/${id}/approve`, { remark });
    },

    reject(id, rejectReason) {
        return axios.post(`/withdrawal-v2/${id}/reject`, { reject_reason: rejectReason });
    },

    process(id, data = {}) {
        return axios.post(`/withdrawal-v2/${id}/process`, data);
    },

    complete(id, data = {}) {
        return axios.post(`/withdrawal-v2/${id}/complete`, data);
    },

    fail(id, failReason) {
        return axios.post(`/withdrawal-v2/${id}/fail`, { fail_reason: failReason });
    },

    cancel(id, cancelReason = '') {
        return axios.post(`/withdrawal-v2/${id}/cancel`, { cancel_reason: cancelReason });
    },

    batchApprove(ids, remark = '') {
        return axios.post('/withdrawal-v2/batch-approve', { ids, remark });
    },

    batchProcess(ids, data = {}) {
        return axios.post('/withdrawal-v2/batch-process', { ids, ...data });
    },
};
