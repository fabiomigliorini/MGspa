import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { db } from 'boot/db'
import { notifyError, notifyWarning } from 'src/utils/notify'
import { extrairErro } from 'src/utils/extrairErro'
import {
  normalizarCargaDoServidor,
  ETAPAS_ABERTAS,
  CAMPOS_MOTORISTA_SEM_CADASTRO,
} from 'src/utils/carga'
import { lerUltimaSincronizacao, gravarUltimaSincronizacao } from 'src/utils/cacheReferencias'

// Store de sincronizacao offline-first (espelha o negocios):
//  - PULL: baixa os cadastros de referencia + saldos pro Dexie (leitura offline)
//  - PUSH: envia as cargas pendentes (sincronizado = 0) pro backend
// Deteccao de offline e por erro de rede (ehFalhaDeRede), best-effort.
// O pull pesado (cadastros + plantios) so refaz quando o cache esta "velho"
// (TTL); o snapshot de saldos e leve e roda sempre.
// Roda sozinha: ao abrir as telas, a cada minuto e quando a rede volta
// (composables/useSincronizacaoAutomatica, montado no MainLayout).
const TTL_SINCRONIZACAO = 5 * 60 * 1000 // 5 min

// Toda chamada do sync vai com skipNotify: quem avisa o operador e esta store
// (uma vez, na transicao pra erro), nao o interceptor a cada requisicao — senao
// o ciclo automatico offline dispara "Erro de conexao" todo minuto.
const OPCOES = { skipLoading: true, skipNotify: true }

// Sem resposta do servidor = rede (offline, socket morto, timeout de 15s do
// api.js). Timeout NAO e rejeicao: a carga fica pendente e sobe no proximo ciclo;
// antes ela ganhava `syncerro` e nunca mais era reenviada sozinha.
export function ehFalhaDeRede(e) {
  return !e?.response && ['ERR_NETWORK', 'ECONNABORTED', 'ETIMEDOUT'].includes(e?.code)
}

export const useSincronizacaoStore = defineStore('sincronizacao', () => {
  const sincronizando = ref(false)
  const online = ref(true)
  // Persistido no localStorage p/ o TTL sobreviver a reload (F5).
  const ultimaSincronizacao = ref(lerUltimaSincronizacao())
  const ultimoCiclo = ref(null) // fim do ultimo ciclo completo sem erro (tooltip do header)
  const erro = ref(null) // mensagem da ultima falha que nao foi de rede; null = ok
  const saldosUnidades = ref([]) // snapshot do estoque por unidade armazenadora

  // Ultima pagina da resposta. O Laravel embrulha o Resource em `data` e joga a
  // paginacao em `meta` — ler `data.last_page` dava undefined e o laco parava na
  // PRIMEIRA pagina (50 itens, MgModel::$perPage). Sumia contrato/talhao/carga do
  // 51o em diante. O fallback cobre endpoint que devolve array cru (ex.: v1/veiculo).
  function ultimaPagina(data) {
    return data?.meta?.last_page ?? data?.last_page ?? 1
  }

  // Baixa todas as paginas de um endpoint e regrava a tabela Dexie. Depois PODA
  // o que nao voltou do servidor: bulkPut so insere/atualiza, e uma linha
  // apagada la (ou vinda de outro ambiente) ficava no cache pra sempre —
  // aparecia em select, mapa e listagem como se existisse. Como todo registro
  // deste pull recebe o mesmo `sincronizado`, o que ficou com timestamp menor e
  // fantasma. So poda depois de TODAS as paginas terem vindo (um pull
  // interrompido nao apaga nada).
  async function puxarTabela(endpoint, tabelaDexie) {
    const todos = []
    let page = 1
    let last = 1
    do {
      const { data } = await api.get(endpoint, { ...OPCOES, params: { page } })
      const itens = Array.isArray(data) ? data : (data.data ?? [])
      todos.push(...itens)
      last = ultimaPagina(data)
      page++
    } while (page <= last)

    const sincronizado = Date.now()
    await tabelaDexie.bulkPut(todos.map((i) => ({ ...i, sincronizado })))
    await tabelaDexie.where('sincronizado').below(sincronizado).delete()
  }

  // Plantios sao aninhados na safra (safra/{codsafra}/plantio). Pagina (15/pag).
  // So as safras ativas: a UI so usa a safra ativa (plantiosDaSafra), varrer as
  // inativas so multiplicava requisicoes (o plantio?page=1 repetido).
  // Poda igual a puxarTabela, mas so entre as safras ativas puxadas — plantio de
  // safra inativa nao e baixado e nao pode ser apagado por isso (uma carga
  // antiga ainda resolve o rotulo do talhao por ele).
  async function puxarPlantios() {
    const safras = (await db.safra.toArray()).filter((s) => !s.inativo)
    const sincronizado = Date.now()
    for (const s of safras) {
      let page = 1
      let last = 1
      do {
        const { data } = await api.get(`v1/safra/${s.codsafra}/plantio`, {
          ...OPCOES,
          params: { page },
        })
        const itens = Array.isArray(data) ? data : (data.data ?? [])
        await db.plantio.bulkPut(itens.map((i) => ({ ...i, sincronizado })))
        last = ultimaPagina(data)
        page++
      } while (page <= last)
    }
    await db.plantio
      .where('codsafra')
      .anyOf(safras.map((s) => s.codsafra))
      .filter((p) => (p.sincronizado || 0) < sincronizado)
      .delete()
  }

  async function puxarReferencias() {
    await puxarTabela('v1/cultura', db.cultura)
    await puxarTabela('v1/variedade', db.variedade)
    await puxarTabela('v1/parametro-classificacao', db.parametroclassificacao)
    await puxarTabela('v1/fazenda', db.fazenda)
    await puxarTabela('v1/talhao', db.talhao)
    await puxarTabela('v1/safra', db.safra)
    await puxarTabela('v1/contrato', db.contrato)
    await puxarTabela('v1/veiculo', db.veiculo)
    await puxarTabela('v1/unidade-armazenadora', db.unidadearmazenadora)
    await puxarPlantios()
  }

  // Snapshot dos saldos por unidade (estoque depositado) p/ exibir/avisar offline.
  // Fica FORA do TTL: e 1 requisicao leve e o dado mais volatil (saldo de contrato
  // no CargaForm); nao e persistido no Dexie, entao rodamos sempre.
  async function puxarSaldos() {
    const { data } = await api.get('v1/movimento-grao/saldos-unidades', OPCOES)
    saldosUnidades.value = Array.isArray(data) ? data : []
  }

  // Fila por uuid (TASK-180): encadeia na promessa em voo daquela carga, se
  // houver, e só então dispara — nunca dois POSTs da MESMA carga no ar ao
  // mesmo tempo (o board pode chamar salvar() a cada bloco e o ciclo
  // automático pode cair por cima). `.catch(() => {})` no elo anterior é só
  // pra sequenciar: uma falha não pode emperrar as próximas tentativas.
  const envioEmVoo = new Map()

  function enviarCargaPorUuid(uuid) {
    const anterior = (envioEmVoo.get(uuid) || Promise.resolve()).catch(() => {})
    const atual = anterior.then(() => enviarCargaUma(uuid))
    envioEmVoo.set(uuid, atual)
    atual.catch(() => {}).finally(() => {
      if (envioEmVoo.get(uuid) === atual) envioEmVoo.delete(uuid)
    })
    return atual
  }

  // Envia UMA carga pro backend; o servidor recalcula pesos/descontos e GERA o
  // extrato (autoridade), devolvendo o codcarga + valores oficiais + a versão
  // gravada. Lê o Dexie na hora de enviar (não recebe o objeto capturado) pra
  // sempre mandar o que há de mais atual, mesmo depois de esperar na fila.
  async function enviarCargaUma(uuid) {
    const carga = await db.carga.get(uuid)
    if (!carga || carga.sincronizado === 1) return undefined
    const revisaoEnviada = carga.revisao
    // `percentual` é rateio só-do-front; o backend usa o `liquido` (kg) já rateado.
    const payload = {
      ...carga,
      pontos: (carga.pontos || []).map((p) => {
        const ponto = { ...p }
        delete ponto.percentual
        return ponto
      }),
    }
    let resp
    try {
      ;({ data: resp } = await api.post('v1/carga/sincronizar', payload, OPCOES))
    } catch (e) {
      // Conflito de versão (TASK-180): outro aparelho sincronizou esta carga
      // antes de nós — raro. Não é rejeição — o servidor já manda a versão
      // atual no corpo; adotamos ela e só avisamos, sem guardar o que foi
      // digitado (NÃO marca syncerro, senão o ciclo automático para de tentar).
      if (e?.response?.status === 409) {
        const oficial = e.response.data?.carga ?? {}
        const norm = normalizarCargaDoServidor(oficial)
        await db.carga.update(uuid, { ...norm, sincronizado: 1, syncerro: null })
        notifyWarning(`Carga ${norm.placa || ''} alterada em outro aparelho; a versão daqui foi atualizada.`)
        return oficial
      }
      throw e
    }
    // O Resource embrulha o registro em { data: {...} } (Laravel default).
    const oficial = resp?.data ?? resp ?? {}
    await db.transaction('rw', db.carga, async () => {
      const atual = await db.carga.get(uuid)
      if (!atual) return
      // Reeditou enquanto o envio estava no ar (revisão mudou): só aprende
      // codcarga/versão, mantém pendente — a edição nova sai na PRÓXIMA
      // chamada da fila, já com a versão certa (critério #4 da TASK-180).
      if (atual.revisao !== revisaoEnviada) {
        await db.carga.update(uuid, {
          codcarga: oficial.codcarga ?? atual.codcarga,
          versao: oficial.versao ?? atual.versao,
        })
        return
      }
      // Só sobrescreve o que o backend REALMENTE devolveu. Uma resposta parcial/vazia
      // (ex.: dedup de POSTs concorrentes no api.js) NÃO pode zerar o liquido/codcarga
      // já calculados localmente — senão a carga finalizada fica "— kg / 0 sc".
      const patch = { sincronizado: 1, syncerro: null }
      if (oficial.codcarga != null) {
        const norm = normalizarCargaDoServidor(oficial)
        patch.codcarga = norm.codcarga
        patch.versao = norm.versao
        patch.placa = norm.placa
        patch.motorista = norm.motorista
        for (const campo of CAMPOS_MOTORISTA_SEM_CADASTRO) patch[campo] = norm[campo]
        patch.codveiculo = norm.codveiculo
        patch.codpessoamotorista = norm.codpessoamotorista
        patch.classificacao = norm.classificacao
        for (const campo of ['bruto', 'desconto', 'liquido']) {
          if (oficial[campo] != null) patch[campo] = oficial[campo]
        }
      }
      await db.carga.update(uuid, patch)
    })
    return oficial
  }

  // PULL de cargas (listagem multi-dispositivo): baixa as cargas do dia e faz
  // merge no Dexie. NÃO reusa puxarTabela porque, pra carga, `sincronizado` é
  // flag 0/1 (não timestamp) e um bulkPut cego sobrescreveria edições locais
  // pendentes. Regras do merge: nunca tocar em linha local `sincronizado === 0`;
  // as puxadas entram como `sincronizado: 1` (servidor é a autoridade). Casa pela
  // PK `uuid` (o backend preserva o uuid do cliente no upsert).
  //
  // Com dia filtrado, puxa TAMBÉM as cargas ainda no pátio de qualquer dia: um
  // caminhão que chegou ontem em outro dispositivo e não finalizou tem que
  // aparecer hoje. O endpoint só filtra etapa por igualdade → uma chamada por
  // etapa aberta (poucas linhas cada).
  //
  // O pátio é físico e mistura safras (milho e soja no mesmo dia), então nada
  // aqui filtra por safra.
  //
  // Sem dia filtrado, as finalizadas vêm só da PRIMEIRA página das mais recentes
  // (50, `-data`): o pátio mostra as últimas 30 (LIMITE_FINALIZADAS_SEM_DATA) e o
  // histórico completo é a tela de Romaneios, que consulta o servidor. Antes
  // baixava a temporada inteira de cada safra ativa, página por página, a cada
  // abertura (TASK-169).
  async function puxarCargas(dataIso) {
    const pendentes = new Set(
      (await db.carga.where('sincronizado').equals(0).toArray()).map((c) => c.uuid),
    )
    if (dataIso) {
      await puxarPaginasCarga({ data: dataIso }, pendentes)
    } else {
      await puxarPaginasCarga({ sort: '-data' }, pendentes, { soPrimeira: true })
    }
    for (const etapa of ETAPAS_ABERTAS) {
      await puxarPaginasCarga({ etapa }, pendentes)
    }
  }

  async function puxarPaginasCarga(filtro, pendentes, { soPrimeira = false } = {}) {
    const params = { ...filtro, page: 1 }
    let last = 1
    do {
      const { data } = await api.get('v1/carga', { ...OPCOES, params })
      const itens = Array.isArray(data) ? data : (data.data ?? [])
      const gravar = itens
        .filter((c) => c.uuid && !pendentes.has(c.uuid))
        .map((c) => ({ ...normalizarCargaDoServidor(c), sincronizado: 1 }))
      if (gravar.length) await db.carga.bulkPut(gravar)
      last = soPrimeira ? 1 : ultimaPagina(data)
      params.page++
    } while (params.page <= last)
  }

  async function enviarCargasPendentes() {
    const pendentes = await db.carga.where('sincronizado').equals(0).toArray()
    for (const carga of pendentes) {
      // Carga já rejeitada pelo servidor fica marcada (`syncerro`) e NÃO é
      // re-tentada — senão o mesmo 422/500 re-dispara toast a cada ciclo de sync,
      // pra sempre. Ela volta ao fluxo quando o operador editar (salvar limpa o erro).
      if (carga.syncerro) continue
      // 422 (excede contrato, rateio não fecha) / 500: marca e segue; o registro
      // fica pendente até o operador ajustar. Só falha de rede interrompe o ciclo.
      try {
        await enviarCargaPorUuid(carga.uuid)
      } catch (e) {
        if (ehFalhaDeRede(e)) throw e
        const msg = e?.response?.data?.message || 'Rejeitado pelo servidor'
        await db.carga.update(carga.uuid, { syncerro: msg })
        notifyError(e)
        console.error('Falha ao sincronizar carga', carga.uuid, e?.response?.data || e)
      }
    }
  }

  // Roda o ciclo: empurra pendencias (sempre), refaz o pull pesado so quando o
  // cache esta "velho" (> TTL) ou quando forcado, e atualiza os saldos sempre.
  // `force` (botao "Sincronizar") ignora o TTL; o ciclo automatico chama sem force.
  //
  // Nunca relanca: quem chama segue recarregando do Dexie o que ja foi gravado.
  // Falha de rede so marca offline (o ciclo automatico tenta de novo). Outra
  // falha (500, 404, erro do Dexie) grava `erro` e avisa UMA vez — o ciclo roda
  // todo minuto e um toast por minuto do mesmo erro nao ajuda ninguem. Antes a
  // falha subia e as telas a engoliam com .catch(() => {}): cadastro velho sem
  // aviso nenhum.
  async function sincronizar({ force = false } = {}) {
    if (sincronizando.value) return
    sincronizando.value = true
    try {
      await enviarCargasPendentes()
      // Lê do storage, não da ref: salvar um cadastro (api.js) apaga a validade
      // lá, e a ref em memória ainda diria que o cache está fresco.
      const ultima = lerUltimaSincronizacao()
      const desatualizado = !ultima || Date.now() - ultima >= TTL_SINCRONIZACAO
      if (force || desatualizado) {
        await puxarReferencias()
        ultimaSincronizacao.value = Date.now()
        gravarUltimaSincronizacao(ultimaSincronizacao.value)
      }
      await puxarSaldos()
      online.value = true
      erro.value = null
      ultimoCiclo.value = Date.now()
    } catch (e) {
      if (ehFalhaDeRede(e)) {
        online.value = false
      } else {
        online.value = true
        if (!erro.value) notifyError(e, 'Falha ao sincronizar')
        erro.value = extrairErro(e, 'Falha ao sincronizar')
        console.error('Falha na sincronizacao', e)
      }
    } finally {
      sincronizando.value = false
    }
  }

  return {
    sincronizando,
    online,
    ultimaSincronizacao,
    ultimoCiclo,
    erro,
    saldosUnidades,
    sincronizar,
    puxarReferencias,
    puxarCargas,
    enviarCargaPorUuid,
    enviarCargasPendentes,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSincronizacaoStore, import.meta.hot))
}
