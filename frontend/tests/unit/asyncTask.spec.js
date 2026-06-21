import Vue from 'vue'
import asyncTask from '@/mixins/asyncTask'

jest.mock('element-ui', () => ({
  Message: {
    success: jest.fn(),
    error: jest.fn()
  }
}))

import { Message } from 'element-ui'

function createComponent(mixinData = {}) {
  return new Vue({
    mixins: [asyncTask],
    data() {
      return {
        loading: false,
        customLoading: false,
        ...mixinData
      }
    }
  })
}

describe('asyncTask mixin', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  describe('withLoading', () => {
    it('sets loading to true during async operation', async () => {
      const vm = createComponent()
      let loadingDuringCall = false

      await vm.withLoading(() => {
        loadingDuringCall = vm.loading
        return Promise.resolve('result')
      })

      expect(loadingDuringCall).toBe(true)
    })

    it('sets loading to false after success', async () => {
      const vm = createComponent()

      await vm.withLoading(() => Promise.resolve('ok'))

      expect(vm.loading).toBe(false)
    })

    it('sets loading to false after error', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject(new Error('fail')))
      } catch (e) {
        // expected
      }

      expect(vm.loading).toBe(false)
    })

    it('uses custom loadingKey', async () => {
      const vm = createComponent()

      await vm.withLoading(() => Promise.resolve('ok'), { loadingKey: 'customLoading' })

      expect(vm.customLoading).toBe(false)
    })

    it('calls onSuccess with result', async () => {
      const vm = createComponent()
      const onSuccess = jest.fn()

      await vm.withLoading(() => Promise.resolve({ id: 1 }), { onSuccess })

      expect(onSuccess).toHaveBeenCalledWith({ id: 1 })
    })

    it('shows successMessage via Message.success', async () => {
      const vm = createComponent()

      await vm.withLoading(() => Promise.resolve(), { successMessage: '操作成功' })

      expect(Message.success).toHaveBeenCalledWith('操作成功')
    })

    it('shows errorMessage on failure when provided', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject(new Error('fail')), { errorMessage: '自定义错误' })
      } catch (e) {
        // expected
      }

      expect(Message.error).toHaveBeenCalledWith('自定义错误')
    })

    it('shows extracted error message on failure when no errorMessage', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject(new Error('默认错误消息')))
      } catch (e) {
        // expected
      }

      expect(Message.error).toHaveBeenCalledWith('默认错误消息')
    })

    it('shows response error message on failure', async () => {
      const vm = createComponent()
      const err = { response: { data: { message: '服务器错误' } }, message: 'network' }

      try {
        await vm.withLoading(() => Promise.reject(err))
      } catch (e) {
        // expected
      }

      expect(Message.error).toHaveBeenCalledWith('服务器错误')
    })

    it('shows "操作失败" as fallback message', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject({}))
      } catch (e) {
        // expected
      }

      expect(Message.error).toHaveBeenCalledWith('操作失败')
    })

    it('does not show error message when silent is true', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject(new Error('fail')), { silent: true })
      } catch (e) {
        // expected
      }

      expect(Message.error).not.toHaveBeenCalled()
    })

    it('does not show error message when silent and errorMessage both set', async () => {
      const vm = createComponent()

      try {
        await vm.withLoading(() => Promise.reject(new Error('fail')), { silent: true, errorMessage: 'custom' })
      } catch (e) {
        // expected
      }

      expect(Message.error).not.toHaveBeenCalled()
    })

    it('returns the result of the async function', async () => {
      const vm = createComponent()

      const result = await vm.withLoading(() => Promise.resolve('data'))

      expect(result).toBe('data')
    })

    it('re-throws error after handling', async () => {
      const vm = createComponent()
      const error = new Error('rethrown')

      try {
        await vm.withLoading(() => Promise.reject(error))
        fail('should have thrown')
      } catch (e) {
        expect(e).toBe(error)
      }
    })
  })

  describe('safeCall', () => {
    it('returns result on success', async () => {
      const vm = createComponent()

      const result = await vm.safeCall(() => Promise.resolve('safe-data'))

      expect(result).toBe('safe-data')
    })

    it('calls onSuccess with result', async () => {
      const vm = createComponent()
      const onSuccess = jest.fn()

      await vm.safeCall(() => Promise.resolve({ id: 1 }), { onSuccess })

      expect(onSuccess).toHaveBeenCalledWith({ id: 1 })
    })

    it('returns null on error', async () => {
      const vm = createComponent()

      const result = await vm.safeCall(() => Promise.reject(new Error('fail')))

      expect(result).toBeNull()
    })

    it('shows error message from errorMessage option', async () => {
      const vm = createComponent()

      await vm.safeCall(() => Promise.reject(new Error('fail')), { errorMessage: '自定义错误' })

      expect(Message.error).toHaveBeenCalledWith('自定义错误')
    })

    it('shows error from response data when no errorMessage', async () => {
      const vm = createComponent()

      await vm.safeCall(() => Promise.reject({ response: { data: { message: 'resp error' } } }))

      expect(Message.error).toHaveBeenCalledWith('resp error')
    })

    it('shows error.message as fallback', async () => {
      const vm = createComponent()

      await vm.safeCall(() => Promise.reject(new Error('err msg')))

      expect(Message.error).toHaveBeenCalledWith('err msg')
    })

    it('shows "操作失败" when no message available', async () => {
      const vm = createComponent()

      await vm.safeCall(() => Promise.reject({}))

      expect(Message.error).toHaveBeenCalledWith('操作失败')
    })

    it('does not show message when silent is true', async () => {
      const vm = createComponent()

      await vm.safeCall(() => Promise.reject(new Error('fail')), { silent: true })

      expect(Message.error).not.toHaveBeenCalled()
    })
  })

  describe('cleanParams', () => {
    it('removes empty string values', () => {
      const vm = createComponent()

      expect(vm.cleanParams({ keyword: '', page: 1 })).toEqual({ page: 1 })
    })

    it('removes null values', () => {
      const vm = createComponent()

      expect(vm.cleanParams({ name: null, age: 20 })).toEqual({ age: 20 })
    })

    it('removes undefined values', () => {
      const vm = createComponent()

      expect(vm.cleanParams({ name: undefined, age: 20 })).toEqual({ age: 20 })
    })

    it('keeps falsy but valid values like 0 and false', () => {
      const vm = createComponent()

      expect(vm.cleanParams({ count: 0, active: false, name: 'test' })).toEqual({ count: 0, active: false, name: 'test' })
    })

    it('keeps all valid values', () => {
      const vm = createComponent()
      const params = { keyword: 'admin', page: 1, per_page: 15 }

      expect(vm.cleanParams(params)).toEqual(params)
    })

    it('returns empty object when all values are empty', () => {
      const vm = createComponent()

      expect(vm.cleanParams({ a: '', b: null, c: undefined })).toEqual({})
    })
  })
})
