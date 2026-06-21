import api from './axios';

export const configApi = {
    getWithdrawConfig() {
        return api.get('/config/withdraw');
    },

    updateWithdrawConfig(data) {
        return api.put('/config/withdraw', data);
    },

    getPublicWithdrawConfig() {
        return api.get('/config/withdraw/public');
    },
};

export default configApi;
