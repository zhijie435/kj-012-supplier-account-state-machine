import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdraw-rules', { params });
    },

    getEnabled(params = {}) {
        return axios.get('/withdraw-rules/enabled', { params });
    },

    getApplicable(methodId) {
        return axios.get('/withdraw-rules/applicable', { params: { withdraw_method_id: methodId } });
    },

    getDetail(id) {
        return axios.get(`/withdraw-rules/${id}`);
    },

    create(data) {
        return axios.post('/withdraw-rules', data);
    },

    update(id, data) {
        return axios.put(`/withdraw-rules/${id}`, data);
    },

    delete(id) {
        return axios.delete(`/withdraw-rules/${id}`);
    },

    toggleStatus(id, status) {
        return axios.post(`/withdraw-rules/${id}/toggle-status`, { status });
    },
};
