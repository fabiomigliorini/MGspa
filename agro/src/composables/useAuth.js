import { storeToRefs } from 'pinia'
import { useAuthStore } from 'src/stores/auth'
import { computed } from 'vue'

/**
 * Composable de Autenticação
 * Fornece acesso fácil ao estado e métodos de autenticação
 */
export function useAuth() {
  const store = useAuthStore()

  const { token, usuario, expiresAt, carregando } = storeToRefs(store)

  const estaAutenticado = computed(() => !!token.value && !!usuario.value)

  const permissoes = computed(() => {
    if (!usuario.value?.permissoes) return []
    return usuario.value.permissoes.map((p) => p.grupousuario)
  })

  const ehAdmin = computed(() => permissoes.value.includes('Administrador'))

  const { validarToken, logout, temAlgumaPermissao } = store

  function temPermissao(nome) {
    if (ehAdmin.value) return true
    return permissoes.value.includes(nome)
  }

  async function renovarToken() {
    await validarToken()
  }

  return {
    token,
    usuario,
    expiresAt,
    carregando,
    permissoes,
    ehAdmin,
    estaAutenticado,
    validarToken,
    renovarToken,
    logout,
    temPermissao,
    temAlgumaPermissao,
  }
}
