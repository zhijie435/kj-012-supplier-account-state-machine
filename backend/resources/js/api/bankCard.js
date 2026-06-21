import axios from './axios';

export default {
    getList(params = {}) {
        return axios.get('/bank-cards', { params });
    },

    getAllActive(params = {}) {
        return axios.get('/bank-cards/all-active', { params });
    },

    getTypeOptions() {
        return axios.get('/bank-cards/type-options');
    },

    getBankOptions() {
        return axios.get('/bank-cards/bank-options');
    },

    getDetail(id) {
        return axios.get(`/bank-cards/${id}`);
    },

    create(data) {
        return axios.post('/bank-cards', data);
    },

    update(id, data) {
        return axios.put(`/bank-cards/${id}`, data);
    },

    delete(id) {
        return axios.delete(`/bank-cards/${id}`);
    },

    setDefault(id) {
        return axios.post(`/bank-cards/${id}/set-default`);
    },
};
