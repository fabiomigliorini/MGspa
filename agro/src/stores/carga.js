import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { uid } from 'quasar'
import { db } from 'boot/db'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { calcularCarga } from 'src/utils/desconto'

export const ETAPAS = ['BRUTO', 'CLASSIFICACAO', 'TARA', 'FINALIZADO']

// Store das cargas de colheita (pátio) — lê/grava no Dexie (offline-first) e
// dispara a sincronização em background. Os cadastros (safra/plantio/cultura/
// faixas) vêm do cache Dexie pra funcionar sem internet.
export const useCargaStore = defineStore('carga', () => {
  const sincronizacao = useSincronizacaoStore()

  const cargas = ref([])
  const safras = ref([])
  const plantios = ref([])
  const culturas = ref([])
  const variedades = ref([])
  const faixas = ref([])
  const veiculos = ref([])
  const codsafraAtiva = ref(null)

  // Caminhões ativos (cache p/ autocomplete da placa, funciona offline).
  const veiculosAtivos = computed(() => veiculos.value.filter((v) => !v.inativo))
  const veiculoPorId = (codveiculo) =>
    veiculos.value.find((v) => v.codveiculo === codveiculo) || null

  const safraAtiva = computed(
    () => safras.value.find((s) => s.codsafra === codsafraAtiva.value) || null,
  )

  const culturaAtiva = computed(
    () => culturas.value.find((c) => c.codcultura === safraAtiva.value?.codcultura) || null,
  )

  const faixasDaSafra = computed(() =>
    faixas.value.filter((f) => f.codcultura === safraAtiva.value?.codcultura),
  )

  // Plantios da safra ativa com rótulo pronto (talhão — variedade)
  const plantiosDaSafra = computed(() =>
    plantios.value
      .filter((p) => p.codsafra === codsafraAtiva.value && !p.inativo)
      .map((p) => ({
        ...p,
        rotulo: `${p.talhao ?? 'Talhão ' + p.codplantio}${
          p.variedade?.variedade ? ' — ' + p.variedade.variedade : ''
        }`,
      })),
  )

  const cargasPorEtapa = computed(() => {
    const grupos = { BRUTO: [], CLASSIFICACAO: [], TARA: [], FINALIZADO: [] }
    for (const c of cargas.value) {
      if (!c.inativo && grupos[c.etapa]) grupos[c.etapa].push(c)
    }
    return grupos
  })

  const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

  // Colhido (kg líquido seco) por plantio/talhão — rateia cada carga pelo % dos
  // seus talhões. Base do cálculo de produtividade na tela da safra.
  const colhidoPorPlantio = computed(() => {
    const mapa = {}
    for (const c of cargas.value) {
      if (c.inativo) continue
      const seco = Number(c.pesoliquidoseco) || 0
      if (!seco) continue
      for (const p of c.plantios || []) {
        const pct = Number(p.percentual) || 0
        mapa[p.codplantio] = (mapa[p.codplantio] || 0) + seco * (pct / 100)
      }
    }
    return mapa
  })

  // Resumo da safra: por plantio (área, colhido, produtividade sc/ha) + totais.
  const produtividade = computed(() => {
    const porPlantio = plantiosDaSafra.value.map((p) => {
      const colhidoKg = colhidoPorPlantio.value[p.codplantio] || 0
      const area = Number(p.areaplantada) || 0
      const sacas = colhidoKg / pesosaca.value
      return {
        ...p,
        colhidoKg,
        sacas,
        produtividade: area > 0 ? sacas / area : 0,
      }
    })
    const areaTotal = porPlantio.reduce((s, p) => s + (Number(p.areaplantada) || 0), 0)
    const colhidoKg = porPlantio.reduce((s, p) => s + p.colhidoKg, 0)
    const sacas = colhidoKg / pesosaca.value
    return {
      porPlantio,
      areaTotal,
      colhidoKg,
      sacas,
      produtividadeMedia: areaTotal > 0 ? sacas / areaTotal : 0,
    }
  })

  async function carregarReferencias() {
    safras.value = await db.safra.toArray()
    culturas.value = await db.cultura.toArray()
    variedades.value = await db.variedade.toArray()
    faixas.value = await db.tabeladesconto.toArray()
    plantios.value = await db.plantio.toArray()
    veiculos.value = await db.veiculo.toArray()

    if (!codsafraAtiva.value && safras.value.length) {
      const ativa = safras.value.find((s) => !s.inativo) || safras.value[0]
      codsafraAtiva.value = ativa.codsafra
    }
  }

  async function carregarCargas() {
    if (!codsafraAtiva.value) {
      cargas.value = []
      return
    }
    const arr = await db.cargacolheita.where('codsafra').equals(codsafraAtiva.value).toArray()
    cargas.value = arr.sort((a, b) => (a.data < b.data ? 1 : -1))
  }

  async function definirSafra(codsafra) {
    codsafraAtiva.value = codsafra
    await carregarCargas()
  }

  // Sincroniza (pull cadastros + push pendências) e recarrega tudo. Best-effort.
  async function sincronizar() {
    await sincronizacao.sincronizar()
    await carregarReferencias()
    await carregarCargas()
  }

  function nova() {
    return {
      uuid: uid(),
      codcargacolheita: null,
      codsafra: codsafraAtiva.value,
      etapa: 'BRUTO',
      data: new Date().toISOString(),
      codveiculo: null,
      placa: null,
      codpessoamotorista: null,
      motorista: null,
      pesobruto: null,
      tara: null,
      pesoliquido: null,
      umidade: null,
      impureza: null,
      avariados: null,
      descontoumidade: null,
      descontoimpureza: null,
      descontoavariados: null,
      pesoliquidoseco: null,
      observacao: null,
      plantios: [],
      sincronizado: 0,
    }
  }

  // Grava a carga (recalcula local p/ exibir offline) e tenta sincronizar.
  async function salvar(carga) {
    // Descarta linhas de talhao vazias (sem codplantio) — nao persistir lixo
    // que o backend rejeitaria na sincronizacao.
    carga.plantios = (carga.plantios || []).filter((p) => p.codplantio)
    // Rateio dos talhoes: 1 talhao => 100% (campo % fica oculto na UI).
    // Com mistura (2+), mantem os percentuais informados pelo usuario.
    if (carga.plantios.length === 1) carga.plantios[0].percentual = 100
    const calc = calcularCarga(carga, faixasDaSafra.value)
    Object.assign(carga, calc, { sincronizado: 0 })
    await db.cargacolheita.put({ ...toPlain(carga) })
    await carregarCargas()
    sincronizacao
      .enviarCarga({ ...toPlain(carga) })
      .then(() => carregarCargas())
      .catch(() => {})
    return carga
  }

  async function inativar(carga) {
    carga.inativo = new Date().toISOString()
    await salvar(carga)
  }

  // Insere no cache local um veículo recém-cadastrado (cadastro rápido no
  // pátio), pra ficar disponível no autocomplete sem esperar a próxima sync.
  async function adicionarVeiculo(veiculo) {
    await db.veiculo.put({ ...veiculo, sincronizado: Date.now() })
    veiculos.value = await db.veiculo.toArray()
  }

  return {
    ETAPAS,
    cargas,
    safras,
    plantios,
    culturas,
    variedades,
    faixas,
    veiculos,
    veiculosAtivos,
    veiculoPorId,
    codsafraAtiva,
    safraAtiva,
    culturaAtiva,
    faixasDaSafra,
    plantiosDaSafra,
    cargasPorEtapa,
    pesosaca,
    colhidoPorPlantio,
    produtividade,
    carregarReferencias,
    carregarCargas,
    definirSafra,
    sincronizar,
    nova,
    salvar,
    inativar,
    adicionarVeiculo,
  }
})

// Remove reatividade/proxies antes de gravar no Dexie (IndexedDB nao aceita Proxy).
function toPlain(obj) {
  return JSON.parse(JSON.stringify(obj))
}
