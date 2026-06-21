import api from './axios';

export const paymentApi = {
    list(params = {}) {
        return api.get('/payments', { params });
    },

    show(id) {
        return api.get(`/payments/${id}`);
    },

    create(data) {
        return api.post('/payments', data);
    },

    update(id, data) {
        return api.put(`/payments/${id}`, data);
    },

    delete(id) {
        return api.delete(`/payments/${id}`);
    },

    settle(id, data) {
        return api.post(`/payments/${id}/settle`, data);
    },

    refund(id, data) {
        return api.post(`/payments/${id}/refund`, data);
    },

    retry(id) {
        return api.post(`/payments/${id}/retry`);
    },

    recharge(data) {
        return api.post('/payments/recharge', data);
    },

    balance(distributorId = null) {
        const params = distributorId ? { distributor_id: distributorId } : {};
        return api.get('/payments/balance/info', { params });
    },

    withdrawRules(distributorId = null) {
        const params = distributorId ? { distributor_id: distributorId } : {};
        return api.get('/payments/withdraw/rules', { params });
    },

    withdraw(data) {
        return api.post('/payments/withdraw', data);
    },

    approveWithdraw(id, data = {}) {
        return api.post(`/payments/${id}/approve-withdraw`, data);
    },

    rejectWithdraw(id, data) {
        return api.post(`/payments/${id}/reject-withdraw`, data);
    },
};

export default paymentApi;
