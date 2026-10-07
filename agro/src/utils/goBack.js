/**
 * Volta para a página anterior DENTRO do app.
 * Sem página anterior do app (deep link, aba nova por Ctrl+clique), vai pro
 * fallback. `history.state.back` é preenchido pelo vue-router só em navegação
 * interna — `window.history.length` contava também as páginas de fora do app.
 */
export function goBack(router, fallback = '/') {
  if (router.options.history.state?.back) {
    router.back()
  } else {
    router.push(fallback)
  }
}
