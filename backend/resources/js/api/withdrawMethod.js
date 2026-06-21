import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdraw-methods', { params });
    },

    getEnabled() {
        return axios.get('/withdraw-methods/enabled');
    },

    getDetail(id) {
        return axios.get(`/withdraw-methods/${id}`);
    },

    create(data) {
        return axios.post('/withdraw-methods', data);
    },

    update(id, data) {
        return axios.put(`/withdraw-methods/${id}`, data);
    },

    delete(id) {
        return axios.delete(`/withdraw-methods/${id}`);
    },

    toggleStatus(id, status) {
        return axios.post(`/withdraw-methods/${id}/toggle-status`, { status });
    },
};
