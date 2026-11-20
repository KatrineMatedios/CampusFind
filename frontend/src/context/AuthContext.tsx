import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import api, { getToken, setToken } from '../api/client'
import type { User } from '../types'

interface RegisterPayload {
  name: string
  student_id: string
  email: string
  password: string
  password_confirmation: string
}

interface AuthContextValue {
  user: User | null
  loading: boolean
  isAuthenticated: boolean
  isAdmin: boolean
  login: (login: string, password: string) => Promise<User>
  register: (payload: RegisterPayload) => Promise<void>
  demoAdminLogin: () => Promise<boolean>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
  setUser: (user: User | null) => void
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export const AUTO_ADMIN_LOGIN = import.meta.env.VITE_AUTO_ADMIN_LOGIN === 'true'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  const refreshUser = async () => {
    if (!getToken()) {
      setUser(null)
      setLoading(false)
      return
    }

    try {
      const { data } = await api.get<{ user: User }>('/user')
      setUser(data.user ?? null)
    } catch {
      setToken(null)
      setUser(null)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void refreshUser()

    const handleUnauthenticated = () => {
      setToken(null)
      setUser(null)
    }
    window.addEventListener('campusfind:unauthenticated', handleUnauthenticated)
    return () => window.removeEventListener('campusfind:unauthenticated', handleUnauthenticated)
  }, [])

  const login = async (loginValue: string, password: string): Promise<User> => {
    const { data } = await api.post<{ user: User; token: string }>('/login', {
      login: loginValue,
      password,
    })

    if (!data?.user || !data?.token) {
      throw new Error('Invalid response from the server.')
    }

    setToken(data.token)
    setUser(data.user)
    return data.user
  }

  const register = async (payload: RegisterPayload) => {
    const { data } = await api.post<{ user: User; token: string }>('/register', payload)

    if (!data?.user || !data?.token) {
      throw new Error('Invalid response from the server.')
    }

    setToken(data.token)
    setUser(data.user)
  }

  const demoAdminLogin = async (): Promise<boolean> => {
    try {
      const { data } = await api.post<{ user: User; token: string }>('/demo/admin-login')

      if (!data?.user || !data?.token) return false

      setToken(data.token)
      setUser(data.user)
      return true
    } catch {
      return false
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } finally {
      setToken(null)
      setUser(null)
    }
  }

  const value = useMemo<AuthContextValue>(() => ({
    user,
    loading,
    isAuthenticated: Boolean(user),
    isAdmin: user?.role === 'admin',
    login,
    register,
    demoAdminLogin,
    logout,
    refreshUser,
    setUser,
  }), [user, loading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used inside AuthProvider')
  return context
}
