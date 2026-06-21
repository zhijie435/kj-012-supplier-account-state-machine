import request from '@/utils/request'

export function getProducts(params) {
  return request({
    url: '/products',
    method: 'get',
    params
  })
}

export function getProduct(id) {
  return request({
    url: `/products/${id}`,
    method: 'get'
  })
}

export function createProduct(data) {
  return request({
    url: '/products',
    method: 'post',
    data
  })
}

export function updateProduct(id, data) {
  return request({
    url: `/products/${id}`,
    method: 'put',
    data
  })
}

export function deleteProduct(id) {
  return request({
    url: `/products/${id}`,
    method: 'delete'
  })
}

export function calculateProductCost(params) {
  return request({
    url: '/products/calculate-cost',
    method: 'get',
    params
  })
}

export function getProductCosts(productId, params) {
  return request({
    url: `/products/${productId}/costs`,
    method: 'get',
    params
  })
}

export function addProductCost(productId, data) {
  return request({
    url: `/products/${productId}/costs`,
    method: 'post',
    data
  })
}
