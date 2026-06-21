import request from '@/utils/request'

export function getProductCostList(params) {
  return request({
    url: '/product-costs',
    method: 'get',
    params
  })
}

export function getProductCost(id) {
  return request({
    url: `/product-costs/${id}`,
    method: 'get'
  })
}

export function createProductCost(data) {
  return request({
    url: '/product-costs',
    method: 'post',
    data
  })
}

export function batchCreateProductCost(data) {
  return request({
    url: '/product-costs/batch',
    method: 'post',
    data
  })
}

export function updateProductCost(id, data) {
  return request({
    url: `/product-costs/${id}`,
    method: 'put',
    data
  })
}

export function toggleProductCostActive(id) {
  return request({
    url: `/product-costs/${id}/toggle-active`,
    method: 'post'
  })
}

export function deleteProductCost(id) {
  return request({
    url: `/product-costs/${id}`,
    method: 'delete'
  })
}
