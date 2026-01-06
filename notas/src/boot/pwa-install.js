// Captura o evento beforeinstallprompt o mais cedo possível
// antes de qualquer componente Vue montar

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault()
  window.deferredPwaPrompt = e
})

window.addEventListener('appinstalled', () => {
  window.deferredPwaPrompt = null
})

export default () => {}
