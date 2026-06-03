/**
 * Volta para a página anterior do histórico.
 * Se não há histórico (ex: deep link direto), vai pra home.
 */
export function goBack(router, fallback = '/') {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push(fallback)
  }
}
