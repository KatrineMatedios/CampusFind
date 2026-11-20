import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { ApiValidationError } from '../types'

export const API_BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

const TOKEN_KEY = 'campusfind_token'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string | null): void {
  if (token) localStorage.setItem(TOKEN_KEY, token)
  else localStorage.removeItem(TOKEN_KEY)
}

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getToken()
  if (token) config.headers.set('Authorization', `Bearer ${token}`)
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiValidationError>) => {
    if (error.response?.status === 401) {
      setToken(null)
      window.dispatchEvent(new CustomEvent('campusfind:unauthenticated'))
    }
    return Promise.reject(error)
  },
)

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError<ApiValidationError>(error)) {
    const data = error.response?.data
    if (data?.errors) {
      const first = Object.values(data.errors)[0]?.[0]
      if (first) return first
    }
    if (data?.message) return data.message
    if (error.code === 'ERR_NETWORK') {
      return 'Cannot connect to the server. Make sure the Laravel backend is running.'
    }
    return error.message || 'Something went wrong. Please try again.'
  }

  if (error instanceof Error) return error.message
  return 'Something went wrong. Please try again.'
}

export function getValidationErrors(error: unknown): Record<string, string[]> {
  if (axios.isAxiosError<ApiValidationError>(error)) {
    return error.response?.data?.errors ?? {}
  }
  return {}
}

export default api
