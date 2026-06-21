import request from '@/utils/request'

export function getSupplierList(params) {
  return request({
    url: '/suppliers',
    method: 'get',
    params
  })
}

export function getSupplierDetail(id) {
  return request({
    url: `/suppliers/${id}`,
    method: 'get'
  })
}

export function createSupplier(data) {
  return request({
    url: '/suppliers',
    method: 'post',
    data
  })
}

export function updateSupplier(id, data) {
  return request({
    url: `/suppliers/${id}`,
    method: 'put',
    data
  })
}

export function deleteSupplier(id) {
  return request({
    url: `/suppliers/${id}`,
    method: 'delete'
  })
}

export function updateSupplierStatus(id, data) {
  return request({
    url: `/suppliers/${id}/status`,
    method: 'put',
    data
  })
}

export function getSupplierStatusLogs(id, params) {
  return request({
    url: `/suppliers/${id}/status-logs`,
    method: 'get',
    params
  })
}

export function getAllowedTransitions(id) {
  return request({
    url: `/suppliers/${id}/allowed-transitions`,
    method: 'get'
  })
}

export function validateTransition(id, data) {
  return request({
    url: `/suppliers/${id}/validate-transition`,
    method: 'post',
    data
  })
}

export function verifySupplier(id, data) {
  return request({
    url: `/suppliers/${id}/verify`,
    method: 'put',
    data
  })
}

export function activateSupplier(id, data) {
  return request({
    url: `/suppliers/${id}/activate`,
    method: 'put',
    data
  })
}

export function suspendSupplier(id, data) {
  return request({
    url: `/suppliers/${id}/suspend`,
    method: 'put',
    data
  })
}

export function rejectSupplier(id, data) {
  return request({
    url: `/suppliers/${id}/reject`,
    method: 'put',
    data
  })
}

export function cancelSupplier(id, data) {
  return request({
    url: `/suppliers/${id}/cancel`,
    method: 'put',
    data
  })
}
