import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { uid } from 'quasar'
import { db } from 'boot/db'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'

export const ETAPAS_EMBARQUE = ['TARA', 'BRUTO', 'FISCAL', 'DESPACHADO']

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
  const plantios = ref([])
  const veiculos = ref([])

  // Filtro de contrato: quando vindo do detalhe de um contrato, o pátio mostra
  // só os embarques daquele contrato (senão, caminhões de contratos diferentes
  // se confundem no kanban). null = mostra todos.
  const filtroCodcontrato = ref(null)

  const contratosAtivos = computed(() => contratos.value.filter((c) => !c.inativo))

  // Caminhões ativos (cache p/ autocomplete da placa, funciona offline) — igual
  // ao recebimento.
  const veiculosAtivos = computed(() => veiculos.value.filter((v) => !v.inativo))
  const veiculoPorId = (codveiculo) =>
    veiculos.value.find((v) => v.codveiculo === codveiculo) || null

  // Rótulo padrão de um contrato (nº — comprador), igual ao usado no diálogo.
  function rotuloContrato(codcontrato) {
    const c = contratos.value.find((x) => x.codcontrato === codcontrato)
    if (!c) return `Contrato ${codcontrato}`
    const pessoa = c.Pessoa?.fantasia || c.Pessoa?.pessoa || ''
    return pessoa ? `${c.contrato} — ${pessoa}` : `${c.contrato}`
  }

  const contratoFiltradoRotulo = computed(() =>
    filtroCodcontrato.value ? rotuloContrato(filtroCodcontrato.value) : null,
  )

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
    const grupos = { TARA: [], BRUTO: [], FISCAL: [], DESPACHADO: [] }
    // Etapas extintas caem na equivalente: PATIO→Tara, CLASSIFICACAO→Peso Bruto.
    const legado = { PATIO: 'TARA', CLASSIFICACAO: 'BRUTO' }
    const filtro = filtroCodcontrato.value
    for (const e of embarques.value) {
      if (e.inativo) continue
      const etapa = legado[e.etapa] || e.etapa
      if (!grupos[etapa]) continue
      if (filtro && !e.contratos?.some((c) => c.codcontrato === filtro)) continue
      grupos[etapa].push(e)
    }
    return grupos
  })

  // Saída de grãos não tem classificação/desconto: o líquido é só bruto - tara.
  function calcular(embarque) {
    const { pesobruto, pesotara } = embarque
    const temPesos =
      pesobruto !== null &&
      pesobruto !== undefined &&
      pesobruto !== '' &&
      pesotara !== null &&
      pesotara !== undefined &&
      pesotara !== ''
    if (!temPesos) return { pesoliquido: null, pesoliquidoseco: null }
    const liq = arredondar(Number(pesobruto) - Number(pesotara))
    return { pesoliquido: liq, pesoliquidoseco: liq }
  }

  async function carregarReferencias() {
    contratos.value = await db.contrato.toArray()
    culturas.value = await db.cultura.toArray()
    plantios.value = await db.plantio.toArray()
    veiculos.value = await db.veiculo.toArray()
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

  function nova(codcontrato = null) {
    return {
      uuid: uid(),
      codembarque: null,
      etapa: 'TARA',
      data: new Date().toISOString(),
      codveiculo: null,
      placa: null,
      placacarreta: null,
      codpessoamotorista: null,
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
      // Vindo de um contrato, já entra amarrado a ele (mesmo formato do addContrato).
      contratos: codcontrato
        ? [
            {
              codcontrato,
              quantidade: null,
              rotulo: rotuloContrato(codcontrato),
              numeronf: null,
              valornf: null,
            },
          ]
        : [],
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

  // Insere no cache local um veículo recém-cadastrado (cadastro rápido no
  // pátio), pra ficar disponível no autocomplete sem esperar a sync — igual ao
  // recebimento.
  async function adicionarVeiculo(veiculo) {
    await db.veiculo.put({ ...veiculo, sincronizado: Date.now() })
    veiculos.value = await db.veiculo.toArray()
  }

  return {
    embarques,
    contratos,
    contratosAtivos,
    filtroCodcontrato,
    rotuloContrato,
    contratoFiltradoRotulo,
    culturas,
    plantios,
    veiculos,
    veiculosAtivos,
    veiculoPorId,
    plantiosTalhao,
    embarquesPorEtapa,
    calcular,
    adicionarVeiculo,
    carregarReferencias,
    carregarEmbarques,
    sincronizar,
    nova,
    salvar,
    inativar,
  }
})
