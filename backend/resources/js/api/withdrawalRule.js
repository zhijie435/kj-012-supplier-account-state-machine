import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdrawal-rules', { params });
    },

    getCurrent(params = {}) {
        return axios.get('/withdrawal-rules/current', { params });
    },

    getStatusOptions() {
        return axios.get('/withdrawal-rules/status-options');
    },

    getLevelOptions() {
        return axios.get('/withdrawal-rules/level-options');
    },

    getMethodOptions() {
        return axios.get('/withdrawal-rules/method-options');
    },

    getCurrencyOptions() {
        return axios.get('/withdrawal-rules/currency-options');
    },

    getDetail(id) {
        return axios.get(`/withdrawal-rules/${id}`);
    },

    create(data) {
        return axios.post('/withdrawal-rules', data);
    },

    update(id, data) {
        return axios.put(`/withdrawal-rules/${id}`, data);
    },

    delete(id) {
        return axios.delete(`/withdrawal-rules/${id}`);
    },

    toggleActive(id) {
        return axios.post(`/withdrawal-rules/${id}/toggle-active`);
    },
};
