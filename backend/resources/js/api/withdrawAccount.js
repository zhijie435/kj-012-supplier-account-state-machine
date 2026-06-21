import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/withdraw-accounts', { params });
    },

    getDetail(id) {
        return axios.get(`/withdraw-accounts/${id}`);
    },

    create(data) {
        return axios.post('/withdraw-accounts', data);
    },

    update(id, data) {
        return axios.put(`/withdraw-accounts/${id}`, data);
    },

    delete(id) {
        return axios.delete(`/withdraw-accounts/${id}`);
    },

    setDefault(id) {
        return axios.post(`/withdraw-accounts/${id}/set-default`);
    },
};
