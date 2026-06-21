import axios from 'axios'
import store from '@/store'
import router from '@/router'
import { handleBusinessError, handleError, isAuthError } from '@/utils/errorHandler'

const service = axios.create({
  baseURL: import.meta.env.VITE_APP_API_BASE_URL || '/api/v1',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json'
  }
})

service.interceptors.request.use(
  config => {
    const token = store.state.token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => Promise.reject(error)
)

service.interceptors.response.use(
  response => {
    const res = response.data
    const bizError = handleBusinessError(res)

    if (bizError) {
      if (bizError.isAuth) {
        return handleError(bizError, { silent: false })
      }
      return handleError(bizError, { showMessage: true })
    }

    return res
  },
  error => {
    if (isAuthError(error)) {
      store.dispatch('logout')
      router.push('/login')
      return Promise.reject(error)
    }
    return handleError(error, { showMessage: true })
  }
)

export default service
