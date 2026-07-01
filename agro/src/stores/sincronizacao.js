import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { db } from 'boot/db'
import { notifyError } from 'src/utils/notify'

// Store de sincronizacao offline-first (espelha o negocios):
//  - PULL: baixa os cadastros de referencia + saldos pro Dexie (leitura offline)
//  - PUSH: envia as cargas pendentes (sincronizado = 0) pro backend
// Deteccao de offline e por erro (ERR_NETWORK), best-effort.
export const useSincronizacaoStore = defineStore('sincronizacao', () => {
  const sincronizando = ref(false)
  const online = ref(true)
  const ultimaSincronizacao = ref(null)
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
  async function puxarPlantios() {
    const safras = await db.safra.toArray()
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
    // Snapshot dos saldos por unidade (estoque depositado) p/ exibir offline.
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

  // Roda o ciclo completo: empurra pendencias e puxa referencias.
  async function sincronizar() {
    if (sincronizando.value) return
    sincronizando.value = true
    try {
      await enviarCargasPendentes()
      await puxarReferencias()
      online.value = true
      ultimaSincronizacao.value = Date.now()
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
