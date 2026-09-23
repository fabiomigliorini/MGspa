import { ref } from 'vue'
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
 * NÃO MORRE COM O COMPONENTE (TASK-123)
 *
 * O acompanhamento não tem onUnmounted de propósito. Amarrá-lo ao ciclo de vida deixava a
 * Promise de iniciarTransmissao() pendente para sempre e o toast girando sem fim quando o
 * operador trocava de negócio no PDV com a SEFAZ lenta — e o cupom nunca saía. O job roda no
 * servidor, o closure já tem tudo que precisa (api, codnotafiscal capturado no início) e o
 * fim é garantido por teto de TEMPO. Quem chamou decide o que fazer se a tela já mudou.
 *
 * Sem prefixo Mg por não ser componente, igual a abrirPdf.js e formatters.js.
 * Espelha pessoas/src/composables/useReprocessamentoPeriodo.js.
 */

// Rampa: a NFC-e autoriza em ~1,5s, então esperar 3s fixos pela PRIMEIRA consulta fazia a
// linha só ficar verde bem depois de a nota já estar autorizada. Os ticks rápidos cobrem o
// caso comum e a NFe lenta cai no intervalo máximo sem gerar centenas de requisições.
const ATRASOS_MS = [0, 400, 800, 1500]
const INTERVALO_MAX_MS = 3000
// Teto por TEMPO, não por contagem: o job tem $timeout de 420s e ainda pode esperar na fila.
// Ao estourar, a própria nota é consultada antes de dar o resultado.
const TEMPO_MAX_MS = 15 * 60 * 1000
// A cada tantas falhas seguidas do GET de progresso, pergunta à própria nota: se o progresso
// está quebrado mas a nota já autorizou, não faz sentido esperar o teto.
const FALHAS_ENTRE_CONSULTAS = 5

export function useNotaFiscalTransmissao({ api, codnotafiscal }) {
  const transmitindo = ref(false)

  let timer = null
  let pollingAtivo = false
  let notif = null
  let etapaAtual = null
  let polls = 0
  let errosSeguidos = 0
  let inicio = 0
  // Capturado no início: a prop pode mudar no meio e o polling não pode ir atrás dela.
  let cod = null
  let resolver = null
  let rejeitar = null

  const url = () => `/v1/nota-fiscal/${cod}/transmitir`

  // Todo caminho anormal deixa rastro: é o que faltou para diagnosticar o toast preso em prod.
  const log = (nivel, mensagem, extra) =>
    console[nivel](`[transmissão NFe #${cod}] ${mensagem}`, extra ?? '')

  function pararPolling() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
    pollingAtivo = false
    transmitindo.value = false
    polls = 0
    errosSeguidos = 0
    etapaAtual = null
  }

  // Um Notify só, que se atualiza a cada etapa — em vez de empilhar um toast por etapa.
  // Com botão de fechar: o acompanhamento continua, só o toast some. Se algum dia ele ficar
  // preso por um motivo que não previmos, o operador tira sem F5.
  function abrirNotify(mensagem) {
    if (notif) return
    notif = Notify.create({
      group: false,
      timeout: 0,
      spinner: true,
      color: 'grey-8',
      message: 'Transmitindo NFe',
      caption: mensagem,
      actions: [{ icon: 'close', color: 'white', handler: () => (notif = null) }],
    })
  }

  function atualizarNotify(mensagem) {
    if (!notif) return
    try {
      notif({ caption: mensagem })
    } catch (error) {
      log('error', 'falha ao atualizar o notify', error)
    }
  }

  // Resultado final. Atualiza o toast em andamento; se o operador já o fechou, cria um novo —
  // o resultado tem que aparecer de qualquer jeito.
  function fecharNotify(sucesso, mensagem) {
    const atual = notif
    notif = null
    const props = {
      spinner: false,
      timeout: 4000,
      color: sucesso ? 'green-5' : 'red-5',
      icon: sucesso ? 'done' : 'error',
      message: sucesso ? 'NFe transmitida com sucesso!' : 'Erro ao transmitir NFe',
      caption: mensagem,
      actions: [{ icon: 'close', color: 'white' }],
    }
    try {
      if (atual) atual(props)
      else Notify.create({ group: false, ...props })
    } catch (error) {
      log('error', 'falha ao fechar o notify', error)
      try {
        atual?.()
      } catch {
        /* já tentou de dois jeitos; o botão de fechar cobre o resto */
      }
    }
  }

  // Libera quem está esperando ANTES de mexer no notify: o cupom não pode depender do toast.
  function encerrar(dados, erro) {
    pararPolling()
    const res = resolver
    const rej = rejeitar
    resolver = rejeitar = null
    if (erro) rej?.(erro)
    else res?.(dados)
    fecharNotify(
      erro ? false : !!dados.sucesso,
      erro ? erro.message || '' : dados.xMotivo || dados.mensagem || '',
    )
  }

  const finalizar = (dados) => encerrar(dados, null)

  function falhar(erro) {
    log('error', erro.message)
    encerrar(null, erro)
  }

  async function consultarNota() {
    try {
      const { data } = await api.get(`/v1/nota-fiscal/${cod}`)
      return data?.data ?? data ?? null
    } catch (error) {
      log('warn', 'não foi possível consultar a nota', error)
      return null
    }
  }

  // Último recurso quando o progresso não chega a um estado terminal (teto de tempo, cache
  // expirado): pergunta à própria nota. Se já está AUT, é sucesso — o job pode ter terminado
  // sem que a gente tenha visto.
  async function encerrarPorConsulta(motivo) {
    const nota = await consultarNota()
    if (nota?.status === 'AUT') {
      log('warn', `${motivo} A nota já estava autorizada.`)
      finalizar({
        sucesso: true,
        nota,
        cStat: 100,
        xMotivo: 'Autorizada (confirmada por consulta)',
      })
    } else {
      falhar(new Error(`${motivo} Consulte a nota para ver o resultado.`))
    }
  }

  function agendarVerificacao() {
    timer = setTimeout(verificar, ATRASOS_MS[polls] ?? INTERVALO_MAX_MS)
  }

  async function verificar() {
    timer = null
    polls += 1
    if (Date.now() - inicio > TEMPO_MAX_MS) {
      await encerrarPorConsulta('Tempo esgotado acompanhando a transmissão.')
      return
    }

    try {
      const { data } = await api.get(url())
      if (errosSeguidos > 0) {
        // Voltou a conexão: força a etapa a ser reexibida por cima do "tentando de novo"
        errosSeguidos = 0
        etapaAtual = null
      }

      // Cache expirou (TTL 1h) ou nunca existiu
      if (!data || data.status === null || data.status === undefined) {
        await encerrarPorConsulta('Não foi possível acompanhar a transmissão.')
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
      // Erro de rede/API NÃO desiste: o job segue no worker e só o teto de tempo encerra.
      // Qualquer outra exceção é bug nosso e precisa aparecer no console.
      errosSeguidos += 1
      log(error?.isAxiosError ? 'warn' : 'error', 'falha na consulta do progresso', error)
      if (errosSeguidos === 1) atualizarNotify('Sem conexão para acompanhar, tentando de novo...')
      if (errosSeguidos % FALHAS_ENTRE_CONSULTAS === 0) {
        const nota = await consultarNota()
        if (nota?.status === 'AUT') {
          finalizar({
            sucesso: true,
            nota,
            cStat: 100,
            xMotivo: 'Autorizada (confirmada por consulta)',
          })
          return
        }
      }
    }

    // Só reagenda aqui: todo caminho terminal retornou acima. Encadear setTimeout em vez de
    // usar setInterval também garante que duas consultas nunca se sobreponham.
    if (pollingAtivo) agendarVerificacao()
  }

  // A trava é a cadeia, não o timer: durante o GET em voo o timer é null.
  function iniciarPolling() {
    if (pollingAtivo) return
    pollingAtivo = true
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
      // Sobrescrever resolver/rejeitar deixaria a Promise anterior pendente para sempre.
      if (transmitindo.value) {
        reject(new Error('Transmissão já em andamento para esta nota.'))
        return
      }
      resolver = resolve
      rejeitar = reject
      cod = codnotafiscal.value
      inicio = Date.now()
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
    if (transmitindo.value) return
    const atual = codnotafiscal.value
    try {
      const { data } = await api.get(`/v1/nota-fiscal/${atual}/transmitir`)
      if (data?.status !== 'processando') return
      cod = atual
      inicio = Date.now()
      etapaAtual = data.etapa ?? null
      abrirNotify(data.mensagem || 'Transmitindo...')
      iniciarPolling()
    } catch {
      /* silencioso: é só uma retomada oportunista */
    }
  }

  return { transmitindo, iniciarTransmissao, checarEmAndamento, pararPolling }
}
