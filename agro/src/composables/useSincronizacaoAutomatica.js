import { onMounted, onBeforeUnmount } from 'vue'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'

// A balança fica com o pátio aberto o dia inteiro: a carga gravada sem internet
// tem que subir sozinha quando a rede volta, e o cadastro/saldo não pode ficar
// velho até alguém clicar em Sincronizar (TASK-169).
//
// Montado UMA vez no MainLayout (pai de todas as rotas autenticadas; no App.vue
// rodaria também no Login, sem token):
//  - evento `online` do navegador → sincroniza na hora;
//  - evento `offline` → só marca offline (o chip/ícone muda);
//  - a cada minuto → sincroniza. O pull pesado respeita o TTL de 5 min da store,
//    então o ciclo leve é: enviar pendentes + saldos + cargas abertas/recentes.
// Aba escondida não sincroniza (volta a sincronizar ao ficar visível). Ciclos
// não se sobrepõem: a store ignora a chamada enquanto `sincronizando`.
const INTERVALO = 60 * 1000

export function useSincronizacaoAutomatica() {
  const carga = useCargaStore()
  const sincronizacao = useSincronizacaoStore()
  let timer = null

  function sincronizar() {
    if (document.hidden) return
    carga.sincronizar()
  }

  function aoFicarOffline() {
    sincronizacao.online = false
  }

  function aoMudarVisibilidade() {
    if (!document.hidden) sincronizar()
  }

  onMounted(() => {
    window.addEventListener('online', sincronizar)
    window.addEventListener('offline', aoFicarOffline)
    document.addEventListener('visibilitychange', aoMudarVisibilidade)
    timer = setInterval(sincronizar, INTERVALO)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('online', sincronizar)
    window.removeEventListener('offline', aoFicarOffline)
    document.removeEventListener('visibilitychange', aoMudarVisibilidade)
    clearInterval(timer)
  })
}
