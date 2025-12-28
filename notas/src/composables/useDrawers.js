import { inject, onUnmounted } from 'vue'

export function useDrawers() {
  const setLeftDrawer = inject('setLeftDrawer')
  const setRightDrawer = inject('setRightDrawer')
  const clearDrawers = inject('clearDrawers')

  function setLeft(component) {
    setLeftDrawer(component)
  }

  function setRight(component) {
    setRightDrawer(component)
  }

  // Limpa as drawers quando o componente é destruído
  onUnmounted(() => {
    clearDrawers()
  })

  return {
    setLeft,
    setRight,
    clearDrawers,
  }
}
