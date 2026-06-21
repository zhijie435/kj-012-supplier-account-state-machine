import request from '@/utils/request'

export function getRoles(params) {
  return request({
    url: '/roles',
    method: 'get',
    params
  })
}

export function createRole(data) {
  return request({
    url: '/roles',
    method: 'post',
    data
  })
}

export function updateRole(id, data) {
  return request({
    url: `/roles/${id}`,
    method: 'put',
    data
  })
}

export function deleteRole(id) {
  return request({
    url: `/roles/${id}`,
    method: 'delete'
  })
}

export function getPermissions() {
  return request({
    url: '/roles/permissions',
    method: 'get'
  })
}
