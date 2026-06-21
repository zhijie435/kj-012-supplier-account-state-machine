import {
  isAuthError,
  isBusinessError,
  extractErrorMessage,
  handleBusinessError,
  handleError
} from '@/utils/errorHandler'

import { Message, MessageBox } from 'element-ui'

jest.mock('element-ui', () => ({
  Message: jest.fn(),
  MessageBox: {
    confirm: jest.fn()
  }
}))

jest.mock('@/store', () => ({
  dispatch: jest.fn()
}))

jest.mock('@/router', () => ({
  push: jest.fn()
}))

describe('isAuthError', () => {
  it('returns true when response.status is 401', () => {
    const error = { response: { status: 401 } }
    expect(isAuthError(error)).toBe(true)
  })

  it('returns true when error.code is 401', () => {
    const error = { code: 401 }
    expect(isAuthError(error)).toBe(true)
  })

  it('returns true when error.code is 40101', () => {
    const error = { code: 40101 }
    expect(isAuthError(error)).toBe(true)
  })

  it('returns true when error.code is 40102', () => {
    const error = { code: 40102 }
    expect(isAuthError(error)).toBe(true)
  })

  it('returns false for non-auth HTTP errors', () => {
    const error = { response: { status: 403 } }
    expect(isAuthError(error)).toBe(false)
  })

  it('returns false for non-auth business codes', () => {
    const error = { code: 404 }
    expect(isAuthError(error)).toBe(false)
  })

  it('returns false when no response or code', () => {
    expect(isAuthError({})).toBe(false)
    expect(isAuthError(new Error('timeout'))).toBe(false)
  })
})

describe('isBusinessError', () => {
  it('returns true when no response but has code', () => {
    const error = { code: 40001, message: 'biz error' }
    expect(isBusinessError(error)).toBe(true)
  })

  it('returns false when has response', () => {
    const error = { response: { status: 500 }, code: 500 }
    expect(isBusinessError(error)).toBe(false)
  })

  it('returns false when no code', () => {
    expect(isBusinessError(new Error('fail'))).toBe(false)
  })
})

describe('extractErrorMessage', () => {
  it('extracts from response.data.message', () => {
    const error = { response: { data: { message: 'server msg' } } }
    expect(extractErrorMessage(error)).toBe('server msg')
  })

  it('extracts from response.data.msg as fallback', () => {
    const error = { response: { data: { msg: 'server msg2' } } }
    expect(extractErrorMessage(error)).toBe('server msg2')
  })

  it('extracts from error.message as second fallback', () => {
    const error = new Error('network fail')
    expect(extractErrorMessage(error)).toBe('network fail')
  })

  it('returns default when no message found', () => {
    expect(extractErrorMessage({})).toBe('网络错误')
  })

  it('prioritizes response.data.message over msg', () => {
    const error = { response: { data: { message: 'priority', msg: 'secondary' } } }
    expect(extractErrorMessage(error)).toBe('priority')
  })
})

describe('handleBusinessError', () => {
  it('returns null when code is 0', () => {
    expect(handleBusinessError({ code: 0, data: {} })).toBeNull()
  })

  it('returns null when code is 200', () => {
    expect(handleBusinessError({ code: 200, data: {} })).toBeNull()
  })

  it('returns null when code is undefined', () => {
    expect(handleBusinessError({ data: {} })).toBeNull()
  })

  it('returns error object when code is non-success', () => {
    const result = handleBusinessError({ code: 400, message: '参数错误' })
    expect(result).not.toBeNull()
    expect(result.code).toBe(400)
    expect(result.message).toBe('参数错误')
    expect(result.isBusiness).toBe(true)
    expect(result.isAuth).toBeUndefined()
  })

  it('sets isAuth to true for auth codes (401)', () => {
    const result = handleBusinessError({ code: 401, message: '未授权' })
    expect(result.isAuth).toBe(true)
  })

  it('sets isAuth to true for auth code 40101', () => {
    const result = handleBusinessError({ code: 40101, message: 'token过期' })
    expect(result.isAuth).toBe(true)
  })

  it('sets isAuth to true for auth code 40102', () => {
    const result = handleBusinessError({ code: 40102, message: 'token无效' })
    expect(result.isAuth).toBe(true)
  })

  it('uses default message when no message in response', () => {
    const result = handleBusinessError({ code: 500 })
    expect(result.message).toBe('请求失败')
  })
})

describe('handleError', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('rejects with auth error and triggers handleAuthExpired when not silent', async () => {
    const authError = { response: { status: 401 }, code: 401 }
    MessageBox.confirm.mockResolvedValue('confirm')

    await expect(handleError(authError, { silent: false })).rejects.toBe(authError)
    expect(MessageBox.confirm).toHaveBeenCalled()
  })

  it('rejects with auth error without triggering handleAuthExpired when silent', async () => {
    const authError = { response: { status: 401 }, code: 401 }

    await expect(handleError(authError, { silent: true })).rejects.toBe(authError)
    expect(MessageBox.confirm).not.toHaveBeenCalled()
  })

  it('shows Message.error for business error when showMessage true', async () => {
    const bizError = new Error('业务异常')
    bizError.code = 40001

    await expect(handleError(bizError, { showMessage: true })).rejects.toBe(bizError)
    expect(Message).toHaveBeenCalledWith(
      expect.objectContaining({
        message: '业务异常',
        type: 'error'
      })
    )
  })

  it('shows extractErrorMessage for non-business error', async () => {
    const error = { response: { data: { message: '服务器异常' } } }

    await expect(handleError(error, { showMessage: true })).rejects.toBe(error)
    expect(Message).toHaveBeenCalledWith(
      expect.objectContaining({
        message: '服务器异常',
        type: 'error'
      })
    )
  })

  it('does not show Message when silent is true', async () => {
    const error = new Error('fail')

    await expect(handleError(error, { silent: true })).rejects.toBe(error)
    expect(Message).not.toHaveBeenCalled()
  })

  it('does not show Message when showMessage is false', async () => {
    const error = new Error('fail')

    await expect(handleError(error, { showMessage: false })).rejects.toBe(error)
    expect(Message).not.toHaveBeenCalled()
  })

  it('uses "操作失败" as default message for business error without message', async () => {
    const bizError = new Error()
    bizError.code = 40001

    await expect(handleError(bizError, { showMessage: true })).rejects.toBe(bizError)
    expect(Message).toHaveBeenCalledWith(
      expect.objectContaining({
        message: '操作失败',
        type: 'error'
      })
    )
  })
})
