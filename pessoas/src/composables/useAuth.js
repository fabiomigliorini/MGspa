import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from 'src/stores/auth'

export function useAuth() {
  const store = useAuthStore()
  const { token, usuario, expiresAt, carregando } = storeToRefs(store)

  const estaAutenticado = computed(() => !!token.value && !!usuario.value)

  const permissoes = computed(
    () =>
      usuario.value?.permissoes
        ?.map((p) => p.grupousuario)
        .filter((v, i, a) => a.indexOf(v) === i) || [],
  )
  const ehAdmin = computed(() => permissoes.value.includes('Administrador'))

  const { validarToken, logout, temPermissao, temAlgumaPermissao } = store

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
