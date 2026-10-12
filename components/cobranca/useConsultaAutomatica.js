import { onUnmounted } from 'vue'

// Consulta periódica de uma cobrança pendente (PIX, maquininha).
// Um timer só, sem sobrepor requisição; para sozinho quando deixa de estar pendente
// ou quando a consulta falha (sem conexão) — aí o operador consulta com Enter.
export function useConsultaAutomatica({ consultar, pendente, intervalo = 3000 }) {
  let timer = null
  let consultando = false

  const parar = () => {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  const tick = async () => {
    if (consultando) {
      return
    }
    if (!pendente()) {
      parar()
      return
    }
    consultando = true
    const ok = await consultar()
    consultando = false
    if (ok === false) {
      parar()
    }
  }

  const iniciar = () => {
    parar()
    if (pendente()) {
      timer = setInterval(tick, intervalo)
    }
  }

  onUnmounted(parar)

  return { iniciar, parar }
}
