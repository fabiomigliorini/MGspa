import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

// Detecção de nova versão do PWA.
//
// Antes isso era um EVENTO: o register-service-worker disparava `updated()` e a
// tarja acendia. Evento não é idempotente — não dá pra perguntar "isso ainda é
// verdade?". Resultado: cada aba acendia a própria tarja, recarregar uma não
// apagava a das outras, e um worker preso em `waiting` fazia o `updated()`
// disparar em TODO page load (register-service-worker: `if (registration.waiting)
// { emit('updated'); return }`), num ciclo que só terminava quando a aba saía do
// origin — ou seja, no logout.
//
// Agora é um PREDICADO: versão rodando (assada no bundle) vs versão publicada
// (/version.json do próprio origin). Depois de atualizar, o BUILD_ID do bundle
// novo É o conteúdo do version.json, o predicado vira false e não tem como
// reaparecer. A idempotência é estrutural, não uma flag guardada que envelhece.

// `process.env.X` precisa ser referenciado PELADO: o Vite substitui isso
// textualmente por um literal no build. NÃO envolver em
// `typeof process !== 'undefined'` — esse guard referencia o global `process`
// (inexistente no browser em prod) e curto-circuita antes de chegar no literal.
// Mesmo motivo do comentário em MgUserMenu.vue sobre PESSOAS_URL.
const VERSAO_RODANDO = process.env.BUILD_ID || ''

const URL_VERSAO = '/version.json'
const INTERVALO_MS = 5 * 60 * 1000
const MIN_ENTRE_MS = 30 * 1000
const TIMEOUT_ATIVACAO_MS = 5000
const CHAVE_GUARDA = 'mg:pwa:reload'

// Singleton de módulo: um estado por documento, compartilhado por todos os
// componentes do app que consumirem o composable.
export const versaoPublicada = ref(null)
export const falhouAtualizar = ref(false)
let ultimaVerificacao = 0
let timer = null
let consumidores = 0

export const versaoRodando = VERSAO_RODANDO

export const temAtualizacao = computed(
  () => !!VERSAO_RODANDO && !!versaoPublicada.value && versaoPublicada.value !== VERSAO_RODANDO,
)

function lerGuarda() {
  try {
    return sessionStorage.getItem(CHAVE_GUARDA)
  } catch {
    return null
  }
}

function gravarGuarda(valor) {
  try {
    if (valor) sessionStorage.setItem(CHAVE_GUARDA, valor)
    else sessionStorage.removeItem(CHAVE_GUARDA)
  } catch {
    // modo privado / storage bloqueado: seguir sem guarda
  }
}

export async function verificarVersao({ forcar = false } = {}) {
  // Sem BUILD_ID (dev, ou build anterior a esta mudança) o mecanismo fica
  // desligado — melhor não avisar nada do que dar falso positivo.
  if (!VERSAO_RODANDO) return

  const agora = Date.now()
  if (!forcar && agora - ultimaVerificacao < MIN_ENTRE_MS) return
  ultimaVerificacao = agora

  try {
    const resposta = await fetch(`${URL_VERSAO}?_=${agora}`, {
      cache: 'no-store',
      credentials: 'omit',
    })
    // 404 = app ainda não redeployado com o version.json. Fica "desconhecido",
    // nunca acende a tarja. Permite migrar app por app sem susto.
    if (!resposta.ok) return

    const dados = await resposta.json()
    if (typeof dados?.buildId === 'string' && dados.buildId) {
      versaoPublicada.value = dados.buildId
    }

    if (temAtualizacao.value) {
      // Pré-baixa os assets novos: o clique em Atualizar fica instantâneo e
      // funciona mesmo se a rede cair no meio.
      const registro = await navigator.serviceWorker?.getRegistration()
      registro?.update().catch(() => {})
    } else {
      gravarGuarda(null)
      falhouAtualizar.value = false
    }
  } catch {
    // Offline / rede instável: mantém o último estado conhecido. Não inventa
    // tarja nem apaga uma tarja legítima.
  }
}

export async function aplicarAtualizacao() {
  const alvo = versaoPublicada.value

  // Guarda anti-loop: no máximo um reload por buildId alvo, por aba. No caminho
  // feliz ela nunca é consultada — depois do reload o predicado já é false.
  if (lerGuarda() === alvo) {
    falhouAtualizar.value = true
    return
  }
  gravarGuarda(alvo)

  if ('serviceWorker' in navigator) {
    try {
      const registro = await navigator.serviceWorker.getRegistration()
      if (registro) {
        await registro.update().catch(() => {})
        const esperando = registro.waiting
        if (esperando) {
          // Handshake explícito: reload puro NÃO ativa um worker em `waiting`
          // (o documento novo se sobrepõe ao antigo e o client count nunca
          // zera). Só o SKIP_WAITING resolve. O listener correspondente só
          // existe no sw.js quando `skipWaiting` é false na config do workbox.
          await new Promise((resolve) => {
            const t = setTimeout(resolve, TIMEOUT_ATIVACAO_MS)
            navigator.serviceWorker.addEventListener(
              'controllerchange',
              () => {
                clearTimeout(t)
                resolve()
              },
              { once: true },
            )
            esperando.postMessage({ type: 'SKIP_WAITING' })
          })
        }
      }
    } catch {
      // sem SW disponível: o reload abaixo ainda resolve o caso comum
    }
  }

  window.location.reload()
}

// Escape hatch: apaga caches do Workbox, desregistra os SWs e recarrega. O SW
// se registra de novo no boot.
export async function forcarAtualizacao() {
  try {
    if ('caches' in window) {
      const chaves = await caches.keys()
      await Promise.all(chaves.map((chave) => caches.delete(chave)))
    }
    if ('serviceWorker' in navigator) {
      const registros = await navigator.serviceWorker.getRegistrations()
      await Promise.all(registros.map((reg) => reg.unregister()))
    }
    gravarGuarda(null)
  } finally {
    window.location.reload()
  }
}

const aoVisibilidade = () => {
  if (document.visibilityState === 'visible') verificarVersao()
}
const aoOnline = () => verificarVersao({ forcar: true })
// pageshow com persisted = volta do bfcache: o documento foi restaurado
// congelado, incluindo a tarja. Revalidar aqui faz ela sumir sozinha se a aba
// já estiver na versão publicada.
const aoPageShow = (e) => {
  if (e.persisted) verificarVersao({ forcar: true })
}
// Dica do register-service-worker: só encurta a latência de detecção.
const aoSwAtualizado = () => verificarVersao({ forcar: true })

export function useAtualizacaoApp() {
  onMounted(() => {
    if (++consumidores === 1) {
      verificarVersao({ forcar: true })
      timer = setInterval(() => verificarVersao(), INTERVALO_MS)
      document.addEventListener('visibilitychange', aoVisibilidade)
      window.addEventListener('online', aoOnline)
      window.addEventListener('pageshow', aoPageShow)
      window.addEventListener('pwa-sw-atualizado', aoSwAtualizado)
    }
  })

  onBeforeUnmount(() => {
    if (--consumidores === 0) {
      clearInterval(timer)
      timer = null
      document.removeEventListener('visibilitychange', aoVisibilidade)
      window.removeEventListener('online', aoOnline)
      window.removeEventListener('pageshow', aoPageShow)
      window.removeEventListener('pwa-sw-atualizado', aoSwAtualizado)
    }
  })

  return {
    temAtualizacao,
    versaoRodando,
    versaoPublicada,
    falhouAtualizar,
    verificarVersao,
    aplicarAtualizacao,
    forcarAtualizacao,
  }
}
