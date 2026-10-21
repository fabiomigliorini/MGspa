import { onMounted, onUnmounted } from 'vue'

// O leitor de codigo de barras embutido no Android nao aperta teclas: escreve no campo que esta
// com o cursor, como o teclado virtual. Sem campo com o cursor, a leitura se perde (TASK-244).
// Aqui o cursor descansa no campo do leitor: sempre que ele fica parado no vazio ou num botao,
// sem janela aberta e sem outro campo sendo preenchido, volta para o campo.
// Ao fechar uma janela o Quasar devolve o cursor para onde ele estava, ou seja, para o campo.
// `campo` e' uma funcao que devolve o <input> nativo (pode ser null enquanto nao montou).
export function useCursorNoLeitor(campo) {
  let timer = null

  const devolver = () => {
    if (timer) clearTimeout(timer)
    // espera o clique terminar: o botao pode estar abrindo uma janela
    timer = setTimeout(() => {
      const el = campo()
      const ativo = document.activeElement
      if (!el || ativo === el) return
      // alguem preenchendo outro campo, ou uma janela/menu aberto
      if (ativo?.closest('input, textarea, select, [contenteditable], .q-dialog, .q-menu')) return
      // desktop: deixa selecionar texto da tela para copiar
      if (window.getSelection()?.isCollapsed === false) return
      // nao rola a lista de itens de volta para o topo
      el.focus({ preventScroll: true })
    })
  }

  onMounted(() => {
    document.addEventListener('click', devolver)
    document.addEventListener('focusin', devolver)
    // o cursor ja comeca no campo
    devolver()
  })

  onUnmounted(() => {
    document.removeEventListener('click', devolver)
    document.removeEventListener('focusin', devolver)
    if (timer) clearTimeout(timer)
  })
}
