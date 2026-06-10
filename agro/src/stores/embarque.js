import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { uid } from 'quasar'
import { db } from 'boot/db'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { descontoKg } from 'src/utils/desconto'

export const ETAPAS_EMBARQUE = ['PATIO', 'TARA', 'CLASSIFICACAO', 'BRUTO', 'FISCAL', 'DESPACHADO']

function arredondar(v, casas = 3) {
  const f = 10 ** casas
  return Math.round((Number(v) + Number.EPSILON) * f) / f
}

// Store do pátio de expedição (embarque) — offline-first, espelha a de carga.
// Cultura (p/ desconto) vem do contrato. Contratos/plantios vêm do cache Dexie.
export const useEmbarqueStore = defineStore('embarque', () => {
  const sincronizacao = useSincronizacaoStore()

  const embarques = ref([])
  const contratos = ref([])
  const culturas = ref([])
  const faixas = ref([])
  const plantios = ref([])

  const contratosAtivos = computed(() => contratos.value.filter((c) => !c.inativo))

  const plantiosTalhao = computed(() =>
    plantios.value
      .filter((p) => !p.inativo)
      .map((p) => ({
        ...p,
        rotulo: `${p.talhao ?? 'Talhão ' + p.codplantio}${
          p.Safra?.safra ? ' (' + p.Safra.safra + ')' : ''
        }`,
      })),
  )

  const embarquesPorEtapa = computed(() => {
    const grupos = { PATIO: [], TARA: [], CLASSIFICACAO: [], BRUTO: [], FISCAL: [], DESPACHADO: [] }
    for (const e of embarques.value) {
      if (!e.inativo && grupos[e.etapa]) grupos[e.etapa].push(e)
    }
    return grupos
  })

  function faixasDoEmbarque(embarque) {
    const cod = embarque.contratos?.[0]?.codcontrato
    const ct = contratos.value.find((c) => c.codcontrato === cod)
    return faixas.value.filter((f) => f.codcultura === ct?.codcultura)
  }

  function calcular(embarque) {
    const { pesobruto, pesotara } = embarque
    const temPesos =
      pesobruto !== null && pesobruto !== undefined && pesobruto !== '' &&
      pesotara !== null && pesotara !== undefined && pesotara !== ''
    if (!temPesos) {
      return {
        pesoliquido: null,
        descontoumidade: null,
        descontoimpureza: null,
        descontoavariados: null,
        pesoliquidoseco: null,
      }
    }
    const fx = faixasDoEmbarque(embarque)
    const liq = arredondar(Number(pesobruto) - Number(pesotara))
    const du = descontoKg(fx, 'UMIDADE', embarque.umidade, liq)
    const di = descontoKg(fx, 'IMPUREZA', embarque.impureza, liq)
    const da = descontoKg(fx, 'AVARIADOS', embarque.avariados, liq)
    const seco = arredondar(liq - Number(du || 0) - Number(di || 0) - Number(da || 0))
    return { pesoliquido: liq, descontoumidade: du, descontoimpureza: di, descontoavariados: da, pesoliquidoseco: seco }
  }

  async function carregarReferencias() {
    contratos.value = await db.contrato.toArray()
    culturas.value = await db.cultura.toArray()
    faixas.value = await db.tabeladesconto.toArray()
    plantios.value = await db.plantio.toArray()
  }

  async function carregarEmbarques() {
    const arr = await db.embarque.toArray()
    embarques.value = arr.sort((a, b) => (a.data < b.data ? 1 : -1))
  }

  async function sincronizar() {
    await sincronizacao.sincronizar()
    await carregarReferencias()
    await carregarEmbarques()
  }

  function nova() {
    return {
      uuid: uid(),
      codembarque: null,
      etapa: 'PATIO',
      data: new Date().toISOString(),
      placa: null,
      placacarreta: null,
      motorista: null,
      pesotara: null,
      pesobruto: null,
      pesoliquido: null,
      umidade: null,
      impureza: null,
      avariados: null,
      descontoumidade: null,
      descontoimpureza: null,
      descontoavariados: null,
      pesoliquidoseco: null,
      aprovado: null,
      observacao: null,
      contratos: [],
      origens: [],
      sincronizado: 0,
    }
  }

  async function salvar(embarque) {
    Object.assign(embarque, calcular(embarque), { sincronizado: 0 })
    const plain = JSON.parse(JSON.stringify(embarque))
    await db.embarque.put(plain)
    await carregarEmbarques()
    sincronizacao
      .enviarEmbarque(JSON.parse(JSON.stringify(embarque)))
      .then(() => carregarEmbarques())
      .catch(() => {})
    return embarque
  }

  async function inativar(embarque) {
    embarque.inativo = new Date().toISOString()
    await salvar(embarque)
  }

  return {
    embarques,
    contratos,
    contratosAtivos,
    culturas,
    faixas,
    plantios,
    plantiosTalhao,
    embarquesPorEtapa,
    calcular,
    faixasDoEmbarque,
    carregarReferencias,
    carregarEmbarques,
    sincronizar,
    nova,
    salvar,
    inativar,
  }
})
