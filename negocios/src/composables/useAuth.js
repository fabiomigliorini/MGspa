import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from 'stores/auth'
import { sincronizacaoStore } from 'src/stores/sincronizacao'

export function useAuth() {
  const store = useAuthStore()
  const sSinc = sincronizacaoStore()
  const { usuario: usuarioState, token } = storeToRefs(store)

  const usuario = computed(() => (usuarioState.value?.usuario ? usuarioState.value : null))
  const permissoes = computed(
    () => usuarioState.value?.permissoes?.map((p) => p.grupousuario) || [],
  )
  const ehAdmin = computed(() => permissoes.value.includes('Administrador'))
  const estaAutenticado = computed(() => !!usuario.value)
  const expiresEm = computed(() => token.value?.expires_at || null)
  const uuidPdv = computed(() => sSinc.pdv?.uuid || null)

  function logout() {
    return store.logout()
  }

  function renovarToken() {
    return store.renovarToken()
  }

  function login() {
    store.dialog.login = true
  }

  function temPermissao(nome) {
    if (ehAdmin.value) return true
    return permissoes.value.includes(nome)
  }

  function temAlgumaPermissao(lista) {
    if (ehAdmin.value) return true
    return lista.some((p) => permissoes.value.includes(p))
  }

  return {
    usuario,
    permissoes,
    ehAdmin,
    estaAutenticado,
    expiresEm,
    uuidPdv,
    permiteLogin: true,
    logout,
    renovarToken,
    login,
    temPermissao,
    temAlgumaPermissao,
    carregarUsuario: () => store.carregarUsuario(),
  }
}
