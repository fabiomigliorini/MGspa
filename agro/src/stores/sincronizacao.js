import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { db } from 'boot/db'

// Store de sincronizacao offline-first (espelha o negocios):
//  - PULL: baixa os cadastros de referencia pro Dexie (leitura offline)
//  - PUSH: envia as cargas pendentes (sincronizado = 0) pro backend
// Deteccao de offline e por erro (ERR_NETWORK), best-effort.
export const useSincronizacaoStore = defineStore('sincronizacao', () => {
  const sincronizando = ref(false)
  const online = ref(true)
  const ultimaSincronizacao = ref(null)

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

  // Plantios sao aninhados na safra (safra/{codsafra}/plantio). O endpoint
  // pagina (15/pagina), entao percorre todas as paginas — senao safras com
  // muitos talhoes ficam incompletas no cache.
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
    await puxarPlantios()
  }

  // Envia uma carga pro backend; o servidor recalcula pesos/descontos
  // (autoridade) e devolve o codcargacolheita + valores oficiais.
  async function enviarCarga(carga) {
    // Descarta linhas de talhão vazias (codplantio null) — o backend exige
    // codplantio e rejeitaria a carga inteira (422), travando a sincronização.
    const payload = { ...carga, plantios: (carga.plantios || []).filter((p) => p.codplantio) }
    const { data } = await api.post('v1/carga-colheita/sincronizar', payload, { skipLoading: true })
    await db.cargacolheita.update(carga.uuid, {
      codcargacolheita: data.codcargacolheita,
      sincronizado: 1,
      pesoliquido: data.pesoliquido,
      descontoumidade: data.descontoumidade,
      descontoimpureza: data.descontoimpureza,
      descontoavariados: data.descontoavariados,
      pesoliquidoseco: data.pesoliquidoseco,
    })
    return data
  }

  async function enviarCargasPendentes() {
    const pendentes = await db.cargacolheita.where('sincronizado').equals(0).toArray()
    for (const carga of pendentes) {
      // Uma carga inválida não pode abortar o ciclo nem impedir o pull das
      // referências (talhões). Só erro de rede interrompe a sincronização.
      try {
        await enviarCarga(carga)
      } catch (e) {
        if (e.code === 'ERR_NETWORK') throw e
        console.error('Falha ao sincronizar carga', carga.uuid, e?.response?.data || e)
      }
    }
  }

  // ---- Embarque (expedição) ----
  async function enviarEmbarque(embarque) {
    const { data } = await api.post('v1/embarque/sincronizar', embarque, { skipLoading: true })
    await db.embarque.update(embarque.uuid, {
      codembarque: data.codembarque,
      sincronizado: 1,
      pesoliquido: data.pesoliquido,
      descontoumidade: data.descontoumidade,
      descontoimpureza: data.descontoimpureza,
      descontoavariados: data.descontoavariados,
      pesoliquidoseco: data.pesoliquidoseco,
    })
    return data
  }

  async function enviarEmbarquesPendentes() {
    const pendentes = await db.embarque.where('sincronizado').equals(0).toArray()
    for (const embarque of pendentes) {
      await enviarEmbarque(embarque)
    }
  }

  // Roda o ciclo completo: empurra pendencias e puxa referencias.
  async function sincronizar() {
    if (sincronizando.value) return
    sincronizando.value = true
    try {
      await enviarCargasPendentes()
      await enviarEmbarquesPendentes()
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
    sincronizar,
    puxarReferencias,
    enviarCarga,
    enviarCargasPendentes,
    enviarEmbarque,
    enviarEmbarquesPendentes,
  }
})
