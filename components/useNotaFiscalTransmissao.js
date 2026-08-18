import { ref, onUnmounted } from 'vue'
import { Notify } from 'quasar'

/**
 * Transmissão assíncrona da NFe: dispara o job e acompanha por polling.
 *
 * POR QUE EXISTE
 *
 * É a única ação de NFe que não cabe num request comum. O axios dos apps tem timeout de 15s,
 * mas a transmissão à SEFAZ leva até ~4 min no pior caso. O cliente abortava aos 15s enquanto
 * o backend seguia rodando e segurando o lock da nota — e o retry do usuário batia em "Outra
 * operação já está em andamento". Agora o POST só enfileira (responde na hora) e o progresso
 * vem de um GET com cadência em rampa.
 *
 * Criar XML, consultar, cancelar e inutilizar são chamadas diretas do componente: nenhuma
 * chega perto desse tempo.
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
// abortar o acompanhamento de algo que está indo bem no servidor.
const MAX_ERROS_SEGUIDOS = 5
// Teto de segurança para não vazar o timer se o backend nunca chegar a um estado
// terminal (4 ticks rápidos + 116 x 3s ~ 6 min).
const MAX_POLLS = 120

export function useNotaFiscalTransmissao({ api, codnotafiscal }) {
  const transmitindo = ref(false)

  let timer = null
  let notif = null
  let etapaAtual = null
  let errosSeguidos = 0
  let polls = 0
  let resolver = null
  let rejeitar = null

  const url = () => `/v1/nota-fiscal/${codnotafiscal.value}/transmitir`

  function pararPolling() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
    transmitindo.value = false
    polls = 0
    errosSeguidos = 0
    etapaAtual = null
  }

  // Um Notify só, que se atualiza a cada etapa — em vez de empilhar um toast por etapa.
  function abrirNotify(mensagem) {
    if (notif) return
    notif = Notify.create({
      group: false,
      timeout: 0,
      spinner: true,
      color: 'grey-8',
      message: 'Transmitindo NFe',
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
      message: sucesso ? 'NFe transmitida com sucesso!' : 'Erro ao transmitir NFe',
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
        new Error(
          'Tempo esgotado acompanhando a transmissão. Consulte a nota para ver o resultado.',
        ),
      )
      return
    }

    try {
      const { data } = await api.get(url())
      errosSeguidos = 0

      // Cache expirou (TTL 1h) ou nunca existiu: encerra sem erro.
      if (!data || data.status === null || data.status === undefined) {
        pararPolling()
        fecharNotify(false, 'Não foi possível acompanhar a transmissão. Consulte a nota.')
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
          new Error(
            'Sem conexão para acompanhar a transmissão. Consulte a nota para ver o resultado.',
          ),
        )
        return
      }
    }

    // Só reagenda aqui: todo caminho terminal retornou acima. Encadear setTimeout em vez de
    // usar setInterval também garante que duas consultas nunca se sobreponham.
    //
    // O `transmitindo` é a trava do desmonte: clearTimeout não alcança uma consulta que já está
    // em voo, então sem isto um onUnmounted no meio do await ressuscitaria o polling.
    if (transmitindo.value) agendarVerificacao()
  }

  function iniciarPolling() {
    if (timer) return
    transmitindo.value = true
    agendarVerificacao()
  }

  /**
   * Dispara a transmissão e devolve uma Promise que SÓ resolve no estado terminal.
   *
   * Isso é obrigatório: o botão Emitir encadeia criar → transmitir → DANFE, e o PDV
   * (negocios/src/components/offline/ListagemNotas.vue) faz `await comp.emitir()`.
   *
   * Sem parâmetros de propósito: transmitir entrega o XML assinado que já está em disco.
   * Quem decide tpEmis é o /criar.
   */
  function iniciarTransmissao() {
    return new Promise((resolve, reject) => {
      resolver = resolve
      rejeitar = reject
      transmitindo.value = true
      etapaAtual = null
      abrirNotify('Na fila...')

      api
        .post(url())
        .then(({ data }) => {
          if (data?.mensagem) atualizarNotify(data.mensagem)
          etapaAtual = data?.etapa ?? null
          iniciarPolling()
        })
        .catch((error) => {
          const msg =
            error?.response?.data?.message || error?.message || 'Falha ao enfileirar a transmissão'
          falhar(new Error(msg))
        })
    })
  }

  /**
   * Retoma o acompanhamento de uma transmissão que já estava correndo (F5, troca de aba).
   * O job segue no worker independente do navegador.
   */
  async function checarEmAndamento() {
    try {
      const { data } = await api.get(url())
      if (data?.status !== 'processando') return
      etapaAtual = data.etapa ?? null
      abrirNotify(data.mensagem || 'Transmitindo...')
      iniciarPolling()
    } catch {
      /* silencioso: é só uma retomada oportunista */
    }
  }

  onUnmounted(pararPolling)

  return { transmitindo, iniciarTransmissao, checarEmAndamento, pararPolling }
}
