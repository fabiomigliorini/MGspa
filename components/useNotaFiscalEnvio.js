import { ref, onUnmounted } from 'vue'
import { Notify } from 'quasar'

/**
 * Envio assíncrono de NFe: dispara o job e acompanha por polling.
 *
 * POR QUE EXISTE
 *
 * O axios dos apps tem timeout de 15s, mas o envio à SEFAZ leva até ~4 min no pior caso.
 * O cliente abortava aos 15s enquanto o backend seguia rodando e segurando o lock da nota
 * — e o retry do usuário batia em "Outra operação já está em andamento". Agora o POST só
 * enfileira (responde na hora) e o progresso vem de um GET com cadência em rampa.
 *
 * Sem prefixo Mg por não ser componente, igual a abrirPdf.js e formatters.js.
 * Espelha pessoas/src/composables/useReprocessamentoPeriodo.js.
 */

// Rampa: a NFC-e autoriza em ~1,5s, então esperar 3s fixos pela PRIMEIRA consulta fazia a
// linha só ficar verde bem depois de a nota já estar autorizada. Os ticks rápidos cobrem o
// caso comum e a NFe lenta cai no teto sem gerar centenas de requisições.
const ATRASOS_MS = [0, 400, 800, 1500]
const INTERVALO_MAX_MS = 3000
// Desiste só depois de 5 falhas seguidas: é PDV, uma oscilação de rede não pode
// abortar o acompanhamento de um envio que está indo bem no servidor.
const MAX_ERROS_SEGUIDOS = 5
// Teto de segurança para não vazar o timer se o backend nunca chegar a um estado
// terminal (4 ticks rápidos + 116 x 3s ~ 6 min).
const MAX_POLLS = 120

export function useNotaFiscalEnvio({ api, codnotafiscal }) {
  const enviando = ref(false)

  let timer = null
  let notif = null
  let etapaAtual = null
  let errosSeguidos = 0
  let polls = 0
  let resolver = null
  let rejeitar = null

  const url = () => `/v1/nota-fiscal/${codnotafiscal.value}/enviar`

  function pararPolling() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
    enviando.value = false
    polls = 0
    errosSeguidos = 0
    etapaAtual = null
  }

  // Um Notify só, que se atualiza a cada etapa — em vez de empilhar 4 toasts por envio.
  function abrirNotify(mensagem) {
    if (notif) return
    notif = Notify.create({
      group: false,
      timeout: 0,
      spinner: true,
      color: 'grey-8',
      message: 'Enviando NFe',
      caption: mensagem,
    })
  }

  function atualizarNotify(mensagem) {
    if (notif) notif({ caption: mensagem })
  }

  function fecharNotify(sucesso, mensagem) {
    if (!notif) return
    notif({
      spinner: false,
      timeout: 4000,
      color: sucesso ? 'green-5' : 'red-5',
      icon: sucesso ? 'done' : 'error',
      message: sucesso ? 'NFe enviada com sucesso!' : 'Erro ao enviar NFe',
      caption: mensagem,
      actions: [{ icon: 'close', color: 'white' }],
    })
    notif = null
  }

  function finalizar(dados) {
    pararPolling()
    const sucesso = !!dados.sucesso
    fecharNotify(sucesso, dados.xMotivo || dados.mensagem || '')
    if (resolver) resolver(dados)
    resolver = rejeitar = null
  }

  function falhar(erro) {
    pararPolling()
    fecharNotify(false, erro.message || '')
    if (rejeitar) rejeitar(erro)
    resolver = rejeitar = null
  }

  function agendarVerificacao() {
    timer = setTimeout(verificar, ATRASOS_MS[polls] ?? INTERVALO_MAX_MS)
  }

  async function verificar() {
    timer = null
    polls += 1
    if (polls > MAX_POLLS) {
      falhar(
        new Error('Tempo esgotado acompanhando o envio. Consulte a nota para ver o resultado.'),
      )
      return
    }

    try {
      const { data } = await api.get(url())
      errosSeguidos = 0

      // Cache expirou (TTL 1h) ou nunca existiu: encerra sem erro.
      if (!data || data.status === null || data.status === undefined) {
        pararPolling()
        fecharNotify(false, 'Não foi possível acompanhar o envio. Consulte a nota.')
        if (resolver) resolver({ sucesso: false, xMotivo: 'Progresso indisponível' })
        resolver = rejeitar = null
        return
      }

      if (data.etapa && data.etapa !== etapaAtual) {
        etapaAtual = data.etapa
        atualizarNotify(data.mensagem || '')
      }

      if (data.status === 'concluido' || data.status === 'erro') {
        finalizar(data)
        return
      }
    } catch (error) {
      errosSeguidos += 1
      if (errosSeguidos >= MAX_ERROS_SEGUIDOS) {
        falhar(
          new Error('Sem conexão para acompanhar o envio. Consulte a nota para ver o resultado.'),
        )
        return
      }
    }

    // Só reagenda aqui: todo caminho terminal retornou acima. Encadear setTimeout em vez de
    // usar setInterval também garante que duas consultas nunca se sobreponham.
    //
    // O `enviando` é a trava do desmonte: clearTimeout não alcança uma consulta que já está
    // em voo, então sem isto um onUnmounted no meio do await ressuscitaria o polling.
    if (enviando.value) agendarVerificacao()
  }

  function iniciarPolling() {
    if (timer) return
    enviando.value = true
    agendarVerificacao()
  }

  /**
   * Dispara o envio e devolve uma Promise que SÓ resolve no estado terminal.
   *
   * Isso é obrigatório: negocios/src/components/offline/ListagemNotas.vue faz
   * `await comp.enviarNfe()` no fluxo nova-nota → enviar do PDV.
   *
   * `offline` é tri-state: null = automático (segue a conf da empresa),
   * true = força contingência, false = força online.
   */
  function iniciarEnvio(offline = null) {
    return new Promise((resolve, reject) => {
      resolver = resolve
      rejeitar = reject
      enviando.value = true
      etapaAtual = null
      abrirNotify('Na fila...')

      const corpo = offline === null ? {} : { offline }

      api
        .post(url(), corpo)
        .then(({ data }) => {
          if (data?.mensagem) atualizarNotify(data.mensagem)
          etapaAtual = data?.etapa ?? null
          iniciarPolling()
        })
        .catch((error) => {
          const msg =
            error?.response?.data?.message || error?.message || 'Falha ao enfileirar o envio'
          falhar(new Error(msg))
        })
    })
  }

  /**
   * Retoma o acompanhamento de um envio que já estava correndo (F5, troca de aba).
   * O job segue no worker independente do navegador.
   */
  async function checarEmAndamento() {
    try {
      const { data } = await api.get(url())
      if (data?.status !== 'processando') return
      etapaAtual = data.etapa ?? null
      abrirNotify(data.mensagem || 'Enviando...')
      iniciarPolling()
    } catch {
      /* silencioso: é só uma retomada oportunista */
    }
  }

  onUnmounted(pararPolling)

  return { enviando, iniciarEnvio, checarEmAndamento, pararPolling }
}
