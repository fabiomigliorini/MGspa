import { storeToRefs } from 'pinia'
import { useAuthStore } from 'src/stores/auth'
import { computed } from 'vue'

/**
 * Composable de Autenticação
 * Fornece acesso fácil ao estado e métodos de autenticação
 */
export function useAuth() {
  const authStore = useAuthStore()

  // Refs reativos do store
  const { token, user, loading } = storeToRefs(authStore)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const permissions = computed(() => {
    if (!user.value?.permissoes) return []
    return user.value.permissoes.map(p => p.grupousuario)
  })

  const isAdmin = computed(() => permissions.value.includes('Administrador'))

  // Métodos
  const { validateToken, logout, hasAnyPermission } = authStore

  /**
   * Verifica se tem UMA permissão específica
   */
  function hasPermission(permission) {
    if (isAdmin.value) return true
    return permissions.value.includes(permission)
  }

  return {
    // Estado
    token,
    user,
    loading,
    isAuthenticated,
    permissions,
    isAdmin,

    // Métodos
    validateToken,
    logout,
    hasPermission,
    hasAnyPermission
  }
}
