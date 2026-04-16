import { storeToRefs } from 'pinia'
import { useAuthStore } from 'src/stores/auth'
import { computed } from 'vue'

/**
 * Composable de Autenticação
 * Fornece acesso fácil ao estado e métodos de autenticação
 */
export function useAuth() {
  const authStore = useAuthStore()

  const { token, user, loading } = storeToRefs(authStore)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const permissions = computed(() => {
    if (!user.value?.permissoes) return []
    return user.value.permissoes.map((p) => p.grupousuario)
  })

  const isAdmin = computed(() => permissions.value.includes('Administrador'))

  const { validateToken, logout, hasAnyPermission } = authStore

  function hasPermission(permission) {
    if (isAdmin.value) return true
    return permissions.value.includes(permission)
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    permissions,
    isAdmin,
    validateToken,
    logout,
    hasPermission,
    hasAnyPermission,
  }
}
