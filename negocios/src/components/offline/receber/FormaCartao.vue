<script setup>
// Passo do wizard: cartão. Etapas: tipo → parcelas (crédito) → modo (maquininha ou manual)
// → [maquininha do manual] → [bandeira] → [autorização].
// Enviar para a maquininha cria o pedido e emite 'cobranca'; manual lança direto.
import { ref, computed, onMounted } from 'vue'
import { Notify } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { db } from 'boot/db'
import { formataNumero } from '@components/formatters'
import { calcularParcelas } from '../../../utils/parcelamento.js'
import { VISUAL } from '../../../utils/pagamento.js'
import cartoesManuais from '../../../data/cartoes-manuais.json'
import ListaOpcoes from './ListaOpcoes.vue'
import ListaFiltravel from './ListaFiltravel.vue'

const emit = defineEmits(['concluido', 'cobranca'])

const sNegocio = negocioStore()

const CODPESSOA_SAFRA = 20119
const CODPESSOA_STONE = 9993
const TIPOS = [
  { tecla: 1, valor: 1, label: 'Débito', ...VISUAL.debito },
  { tecla: 2, valor: 2, label: 'Crédito', ...VISUAL.credito },
  { tecla: 3, valor: 3, label: 'Voucher', ...VISUAL.voucher },
]

const listaRef = ref(null)
const etapa = ref('tipo')
const historico = ref([])
const enviando = ref(false)

const tipo = ref(null)
const plano = ref(null)
const parceiro = ref(null) // codpessoa do parceiro (manual) ou null (enviar p/ maquininha)
const maquineta = ref(null) // { serial, apelido }
const bandeira = ref(null)
const autorizacao = ref(null)

const posSaurus = ref([])
const posPagarMe = ref([])

const valor = computed(() => sNegocio.receber.valor)

onMounted(async () => {
  const loc = await db.estoqueLocal.get(sNegocio.negocio.codestoquelocal)
  posSaurus.value = loc?.SaurusPosS ?? []
  posPagarMe.value = loc?.PagarMePosS ?? []
})

// ---- maquininhas da filial; a padrão do PDV só vem pré-selecionada (pode estar com defeito) ----
const maquinetasEnvio = computed(() => [
  ...posSaurus.value.map((p) => ({
    valor: `saurus-${p.codsauruspdv}`,
    tipoMaquineta: 'saurus',
    nome: 'SafraPay',
    logo: '/logo-cartoes/Safra.jpg',
    pos: p,
  })),
  ...posPagarMe.value.map((p) => ({
    valor: `pagarme-${p.codpagarmepos}`,
    tipoMaquineta: 'pagarme',
    nome: 'Stone',
    logo: '/logo-cartoes/Stone.jpg',
    pos: p,
  })),
])

const valorPadrao = computed(() => {
  if (sNegocio.negocio.codestoquelocal != sNegocio.padrao.codestoquelocal) {
    return null
  }
  if (sNegocio.padrao.maquineta === 'saurus') {
    return `saurus-${sNegocio.padrao.codsauruspos}`
  }
  if (sNegocio.padrao.maquineta === 'pagarme') {
    return `pagarme-${sNegocio.padrao.codpagarmepos}`
  }
  return null
})

const posEscolhida = ref(null) // { tipoMaquineta, nome, pos }

// ---- opções por etapa ----
const planos = computed(() =>
  calcularParcelas({
    valor: valor.value,
    maximoParcelas: 18,
    maximoParcelasSemJuros: 6,
    valorMinimoParcela: 30,
  }).map((p, i) => ({
    ...p,
    tecla: i < 9 ? i + 1 : null,
    valor: p.parcelas,
    label: `${p.parcelas}x de R$ ${formataNumero(p.valorparcela)}`,
    caption: p.valorjuros
      ? `com juros · total R$ ${formataNumero(valor.value + p.valorjuros)}`
      : 'sem juros',
  })),
)

const nomeTipo = computed(() => TIPOS.find((t) => t.valor === tipo.value)?.label ?? '')

// o que o operador digita na maquininha: valor + juros do plano escolhido
const valorMaquininha = computed(
  () => Math.round((valor.value + (plano.value?.valorjuros || 0)) * 100) / 100,
)

const opcoesModo = computed(() => {
  const sincronizado = sNegocio.negocio.sincronizado
  const opcoes = []
  // padrão do PDV primeiro, depois as demais da filial
  const envio = [...maquinetasEnvio.value].sort((a, b) =>
    a.valor === valorPadrao.value ? -1 : b.valor === valorPadrao.value ? 1 : 0,
  )
  envio.forEach((m) => {
    opcoes.push({
      ...m,
      label: `${m.pos.apelido} · ${m.nome}`,
      caption:
        m.valor === valorPadrao.value
          ? 'Enviar para a maquininha (padrão do PDV)'
          : 'Enviar para a maquininha',
      icone: 'point_of_sale',
      cor: VISUAL.cartao.cor,
      desabilitado: !sincronizado,
      motivo: 'Negócio ainda não sincronizado com o servidor',
    })
  })
  if (!envio.length) {
    opcoes.push({
      valor: 'sem-maquineta',
      label: 'Enviar para a maquininha',
      icone: 'point_of_sale',
      cor: VISUAL.cartao.cor,
      desabilitado: true,
      motivo: 'Nenhuma maquininha cadastrada nesta filial',
    })
  }
  // Manual sempre primeiro (tecla 0); maquininhas 1..9, padrão do PDV primeiro e já selecionada
  return [
    {
      tecla: 0,
      valor: 'manual',
      label: 'Manual',
      caption: 'Digitar a autorização da maquininha',
      icone: 'edit_note',
      cor: VISUAL.cartao.cor,
    },
    ...opcoes.map((o, i) => ({ ...o, tecla: i < 9 ? i + 1 : null })),
  ]
})

// parceiro do cartão manual (Stone, SafraPay, Brasil Card…); só os que aceitam o tipo escolhido
const opcoesParceiro = computed(() =>
  cartoesManuais.map((pes, i) => ({
    tecla: i < 9 ? i + 1 : null,
    valor: pes.codpessoa,
    label: pes.apelido,
    logo: pes.logo,
    desabilitado: !pes.tipos.some((t) => t.tipo === tipo.value),
    motivo: `Não aceita ${nomeTipo.value}`,
  })),
)

const parceiroAtual = computed(() => cartoesManuais.find((p) => p.codpessoa === parceiro.value))

const opcoesMaquineta = computed(() => {
  const lista = parceiro.value === CODPESSOA_SAFRA ? posSaurus.value : posPagarMe.value
  const recentes = sNegocio.maquinetasRecentes.filter((m) => m.codpessoa === parceiro.value)
  const cadastradas = lista
    .filter((p) => !recentes.some((r) => r.serial === p.serial))
    .map((p) => ({
      valor: p.serial ?? `sem-serial-${p.codsauruspdv ?? p.codpagarmepos}`,
      label: p.apelido,
      caption: p.serial ?? 'sem serial cadastrado',
      serial: p.serial,
      icone: 'point_of_sale',
      cor: VISUAL.cartao.cor,
      desabilitado: !p.serial,
      motivo: 'Sem serial cadastrado — digite o serial acima',
    }))
  return [
    ...recentes.map((r) => ({
      valor: r.serial,
      label: r.apelido ?? r.serial,
      caption: `${r.serial} · usada recentemente`,
      serial: r.serial,
      icone: 'history',
      cor: VISUAL.cartao.cor,
    })),
    ...cadastradas,
  ]
})

const opcoesBandeira = computed(() =>
  (parceiroAtual.value?.bandeiras ?? []).map((b, i) => ({
    tecla: i < 9 ? i + 1 : i === 9 ? 0 : null,
    valor: b.bandeira,
    label: b.apelido,
    logo: `/bandeiras/${b.bandeira}.svg`,
  })),
)

// ---- navegação entre etapas ----
const irPara = (proxima) => {
  historico.value.push(etapa.value)
  etapa.value = proxima
}

const voltarEtapa = () => {
  if (!historico.value.length) {
    return false
  }
  etapa.value = historico.value.pop()
  return true
}

const escolherTipo = (opcao) => {
  tipo.value = opcao.valor
  // crédito com mais de uma parcela possível pergunta; com uma só já segue em 1x
  if (tipo.value === 2 && planos.value.length > 1) {
    irPara('parcelas')
    return
  }
  if (tipo.value === 2) {
    plano.value = planos.value[0]
    irPara('modo')
    return
  }
  plano.value = { parcelas: 1, valorjuros: 0, valorparcela: valor.value }
  irPara('modo')
}

const escolherPlano = (opcao) => {
  plano.value = opcao
  irPara('modo')
}

const escolherModo = (opcao) => {
  if (opcao.tipoMaquineta) {
    parceiro.value = null
    posEscolhida.value = opcao
    enviar()
    return
  }
  irPara('parceiro')
}

const escolherParceiro = (opcao) => {
  parceiro.value = opcao.valor
  if (parceiro.value === CODPESSOA_SAFRA || parceiro.value === CODPESSOA_STONE) {
    irPara('maquineta')
    return
  }
  maquineta.value = null
  depoisDaMaquineta()
}

const escolherMaquineta = (opcao) => {
  maquineta.value = { serial: opcao.serial, apelido: opcao.digitado ? null : opcao.label }
  depoisDaMaquineta()
}

const depoisDaMaquineta = () => {
  const bandeiras = parceiroAtual.value.bandeiras
  if (bandeiras.length === 1) {
    bandeira.value = bandeiras[0].bandeira
    irPara('autorizacao')
    return
  }
  irPara('bandeira')
}

const escolherBandeira = (opcao) => {
  bandeira.value = opcao.valor
  irPara('autorizacao')
}

// ---- ações finais ----
const enviar = async () => {
  if (enviando.value) {
    return
  }
  enviando.value = true
  let pedido
  let tipoCobranca
  if (posEscolhida.value.tipoMaquineta === 'saurus') {
    tipoCobranca = 'saurus'
    pedido = await sNegocio.criarSaurusPedido(
      posEscolhida.value.pos.codsauruspdv,
      valor.value,
      plano.value.valorparcela,
      plano.value.valorjuros,
      tipo.value,
      plano.value.parcelas,
      true,
    )
  } else {
    tipoCobranca = 'pagarme'
    pedido = await sNegocio.criarPagarMePedido(
      posEscolhida.value.pos.codpagarmepos,
      valor.value,
      plano.value.valorparcela,
      plano.value.valorjuros,
      tipo.value,
      plano.value.parcelas,
      true,
    )
  }
  enviando.value = false
  if (!pedido) {
    return
  }
  emit('cobranca', { tipo: tipoCobranca, dados: pedido })
}

const salvarManual = async () => {
  const aut = (autorizacao.value ?? '').trim()
  if (!aut) {
    Notify.create({
      type: 'negative',
      message: 'Informe o código de autorização!',
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  const tipoManual = parceiroAtual.value.tipos.find((t) => t.tipo === tipo.value)
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_CARTAOMANUAL),
    tipo: tipoManual.tpag,
    valorpagamento: valor.value,
    valorjuros: plano.value.valorjuros || null,
    codpessoa: parceiro.value,
    bandeira: bandeira.value,
    autorizacao: aut,
    parcelas: plano.value.parcelas,
    valorparcela: plano.value.valorparcela,
    serialmaquineta: maquineta.value?.serial ?? null,
  })
  if (maquineta.value?.serial) {
    sNegocio.registrarMaquinetaRecente({ ...maquineta.value, codpessoa: parceiro.value })
  }
  emit('concluido')
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape') {
    return voltarEtapa()
  }
  switch (etapa.value) {
    case 'autorizacao':
      if (e.key === 'Enter') {
        salvarManual()
        return true
      }
      return false
    default:
      return !!listaRef.value?.tecla(e)
  }
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <template v-if="etapa === 'tipo'">
      <lista-opcoes ref="listaRef" :opcoes="TIPOS" @escolher="escolherTipo" />
    </template>

    <template v-else-if="etapa === 'parcelas'">
      <div style="max-height: 40vh; overflow-y: auto">
        <lista-opcoes ref="listaRef" :opcoes="planos" @escolher="escolherPlano" />
      </div>
    </template>

    <template v-else-if="etapa === 'modo'">
      <lista-opcoes
        ref="listaRef"
        :opcoes="opcoesModo"
        :inicial="valorPadrao"
        @escolher="escolherModo"
      />
    </template>

    <template v-else-if="etapa === 'parceiro'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesParceiro" @escolher="escolherParceiro" />
    </template>

    <template v-else-if="etapa === 'maquineta'">
      <lista-filtravel
        ref="listaRef"
        :opcoes="opcoesMaquineta"
        label="Maquininha (apelido ou serial)"
        permitir-digitado
        @escolher="escolherMaquineta"
      />
    </template>

    <template v-else-if="etapa === 'bandeira'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesBandeira" @escolher="escolherBandeira" />
    </template>

    <template v-else-if="etapa === 'autorizacao'">
      <div class="text-right q-mb-md">
        <div class="text-h2 text-weight-bold text-primary">
          R$ {{ formataNumero(valorMaquininha) }}
        </div>
        <div class="text-subtitle1 text-grey-7">
          Passar na maquininha · {{ nomeTipo }}
          <template v-if="plano?.parcelas > 1"> · {{ plano.parcelas }}x</template>
          <template v-if="plano?.valorjuros"> · com juros</template>
        </div>
      </div>
      <q-input
        v-model="autorizacao"
        label="Código de autorização"
        outlined
        autofocus
        maxlength="20"
      />
      <div class="text-caption text-grey-7 q-mt-sm">Enter lança o pagamento</div>
    </template>

    <q-inner-loading :showing="enviando" />
  </div>
</template>
