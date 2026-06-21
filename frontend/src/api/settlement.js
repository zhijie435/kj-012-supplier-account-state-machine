import request from '@/utils/request'

export function getSettlements(params) {
  return request({
    url: '/settlements',
    method: 'get',
    params
  })
}

export function getSettlement(id) {
  return request({
    url: `/settlements/${id}`,
    method: 'get'
  })
}

export function createSettlement(data) {
  return request({
    url: '/settlements',
    method: 'post',
    data
  })
}

export function updateSettlement(id, data) {
  return request({
    url: `/settlements/${id}`,
    method: 'put',
    data
  })
}

export function deleteSettlement(id) {
  return request({
    url: `/settlements/${id}`,
    method: 'delete'
  })
}

export function previewSettlement(data) {
  return request({
    url: '/settlements/preview',
    method: 'post',
    data
  })
}

export function recalculateSettlement(id) {
  return request({
    url: `/settlements/${id}/recalculate`,
    method: 'post'
  })
}

export function confirmSettlement(id) {
  return request({
    url: `/settlements/${id}/confirm`,
    method: 'post'
  })
}

export function settleSettlement(id) {
  return request({
    url: `/settlements/${id}/settle`,
    method: 'post'
  })
}

export function cancelSettlement(id, data) {
  return request({
    url: `/settlements/${id}/cancel`,
    method: 'post',
    data
  })
}

export function getSettlementStatistics(params) {
  return request({
    url: '/settlements/statistics',
    method: 'get',
    params
  })
}

export function getSettlementPartyOptions(params) {
  return request({
    url: '/settlements/party-options',
    method: 'get',
    params
  })
}
