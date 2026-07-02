import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { db } from 'boot/db'
import { notifyError } from 'src/utils/notify'

// Store de sincronizacao offline-first (espelha o negocios):
//  - PULL: baixa os cadastros de referencia + saldos pro Dexie (leitura offline)
//  - PUSH: envia as cargas pendentes (sincronizado = 0) pro backend
// Deteccao de offline e por erro (ERR_NETWORK), best-effort.
// O pull pesado (cadastros + plantios) so refaz quando o cache esta "velho"
// (TTL); o snapshot de saldos e leve e roda sempre.
const TTL_SINCRONIZACAO = 5 * 60 * 1000 // 5 min
const CHAVE_ULTIMA_SINC = 'agro:ultimaSincronizacao'

export const useSincronizacaoStore = defineStore('sincronizacao', () => {
  const sincronizando = ref(false)
  const online = ref(true)
  // Persistido no localStorage p/ o TTL sobreviver a reload (F5).
  const ultimaSincronizacao = ref(Number(localStorage.getItem(CHAVE_ULTIMA_SINC)) || null)
  const saldosUnidades = ref([]) // snapshot do estoque por unidade armazenadora

  // Baixa todas as paginas de um endpoint e regrava a tabela Dexie.
  async function puxarTabela(endpoint, tabelaDexie) {
    const todos = []
    let page = 1
    let last = 1
    do {
      const { data } = await api.get(endpoint, { params: { page }, skipLoading: true })
      const itens = Array.isArray(data) ? data : (data.data ?? [])
      todos.push(...itens)
      last = data.last_page ?? 1
      page++
    } while (page <= last)

    const sincronizado = Date.now()
    await tabelaDexie.bulkPut(todos.map((i) => ({ ...i, sincronizado })))
  }

  // Plantios sao aninhados na safra (safra/{codsafra}/plantio). Pagina (15/pag).
  // So as safras ativas: a UI so usa a safra ativa (plantiosDaSafra), varrer as
  // inativas so multiplicava requisicoes (o plantio?page=1 repetido).
  async function puxarPlantios() {
    const safras = (await db.safra.toArray()).filter((s) => !s.inativo)
    const sincronizado = Date.now()
    for (const s of safras) {
      let page = 1
      let last = 1
      do {
        const { data } = await api.get(`v1/safra/${s.codsafra}/plantio`, {
          params: { page },
          skipLoading: true,
        })
        const itens = Array.isArray(data) ? data : (data.data ?? [])
        await db.plantio.bulkPut(itens.map((i) => ({ ...i, sincronizado })))
        last = data.last_page ?? 1
        page++
      } while (page <= last)
    }
  }

  async function puxarReferencias() {
    await puxarTabela('v1/cultura', db.cultura)
    await puxarTabela('v1/variedade', db.variedade)
    await puxarTabela('v1/tabela-desconto', db.tabeladesconto)
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
  // no CargaDialog); nao e persistido no Dexie, entao rodamos sempre.
  async function puxarSaldos() {
    const { data } = await api.get('v1/movimento-grao/saldos-unidades', { skipLoading: true })
    saldosUnidades.value = Array.isArray(data) ? data : []
  }

  // Envia uma carga pro backend; o servidor recalcula pesos/descontos e GERA o
  // extrato (autoridade), devolvendo o codcarga + valores oficiais.
  async function enviarCarga(carga) {
    // `percentual` é rateio só-do-front; o backend usa o `liquido` (kg) já rateado.
    const payload = {
      ...carga,
      pontos: (carga.pontos || []).map((p) => {
        const ponto = { ...p }
        delete ponto.percentual
        return ponto
      }),
    }
    const { data: resp } = await api.post('v1/carga/sincronizar', payload, { skipLoading: true })
    // O Resource embrulha o registro em { data: {...} } (Laravel default).
    const oficial = resp.data ?? resp
    await db.carga.update(carga.uuid, {
      codcarga: oficial.codcarga,
      sincronizado: 1,
      bruto: oficial.bruto,
      desconto: oficial.desconto,
      liquido: oficial.liquido,
      descontoumidade: oficial.descontoumidade,
      descontoimpureza: oficial.descontoimpureza,
      descontoavariados: oficial.descontoavariados,
    })
    return oficial
  }

  async function enviarCargasPendentes() {
    const pendentes = await db.carga.where('sincronizado').equals(0).toArray()
    for (const carga of pendentes) {
      // Uma carga rejeitada (422: excede contrato, rateio nao fecha) nao pode
      // abortar o ciclo nem se perder em silencio. Mostra o erro e segue; o
      // registro fica pendente ate o operador ajustar. So rede interrompe.
      try {
        await enviarCarga(carga)
      } catch (e) {
        if (e.code === 'ERR_NETWORK') throw e
        notifyError(e)
        console.error('Falha ao sincronizar carga', carga.uuid, e?.response?.data || e)
      }
    }
  }

  // Roda o ciclo: empurra pendencias (sempre), refaz o pull pesado so quando o
  // cache esta "velho" (> TTL) ou quando forcado, e atualiza os saldos sempre.
  // `force` (botao "Sincronizar") ignora o TTL; onMounted chama sem force.
  async function sincronizar({ force = false } = {}) {
    if (sincronizando.value) return
    sincronizando.value = true
    try {
      await enviarCargasPendentes()
      const desatualizado =
        !ultimaSincronizacao.value || Date.now() - ultimaSincronizacao.value >= TTL_SINCRONIZACAO
      if (force || desatualizado) {
        await puxarReferencias()
        ultimaSincronizacao.value = Date.now()
        localStorage.setItem(CHAVE_ULTIMA_SINC, String(ultimaSincronizacao.value))
      }
      await puxarSaldos()
      online.value = true
    } catch (e) {
      if (e.code === 'ERR_NETWORK') online.value = false
      else throw e
    } finally {
      sincronizando.value = false
    }
  }

  return {
    sincronizando,
    online,
    ultimaSincronizacao,
    saldosUnidades,
    sincronizar,
    puxarReferencias,
    enviarCarga,
    enviarCargasPendentes,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSincronizacaoStore, import.meta.hot))
}
