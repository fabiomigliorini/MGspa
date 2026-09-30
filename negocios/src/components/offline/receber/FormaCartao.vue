<script setup>
// Passo do wizard: cartão. Etapas: tipo → parcelas (crédito) → modo (maquininha ou manual)
// → parceiro → [maquineta do parceiro, pulada se única] → [bandeira] → [autorização].
// Enviar para a maquininha cria o pedido e emite 'cobranca'; manual lança direto.
// As maquinetas vêm do cadastro único (contas → Maquinetas) pelo estoque local.
import { ref, computed, onMounted } from 'vue'
import { Notify } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { db } from 'boot/db'
import MgInput from '@components/MgInput.vue'
import { formataNumero } from '@components/formatters'
import { calcularParcelas } from '../../../utils/parcelamento.js'
import { VISUAL } from '../../../utils/pagamento.js'
import cartoesManuais from '../../../data/cartoes-manuais.json'
import ListaOpcoes from './ListaOpcoes.vue'
import ListaFiltravel from './ListaFiltravel.vue'

const emit = defineEmits(['concluido', 'cobranca'])

const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()
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
const maquineta = ref(null) // maquineta do cartão manual (MaquinetaS do estoque local)
const bandeira = ref(null)
const autorizacao = ref(null)

// maquinetas ativas da filial + compartilhadas
const maquinetas = ref([])

const valor = computed(() => sNegocio.receber.valor)

onMounted(async () => {
  let loc = await db.estoqueLocal.get(sNegocio.negocio.codestoquelocal)
  // PDV atualizado que ainda não sincronizou o estoque local depois do cadastro de maquinetas
  if (loc && !loc.MaquinetaS) {
    await sSinc.silentSincronizarEstoqueLocal()
    loc = await db.estoqueLocal.get(sNegocio.negocio.codestoquelocal)
  }
  maquinetas.value = loc?.MaquinetaS ?? []
})

// ---- maquininhas integradas da filial; a padrão do PDV só vem pré-selecionada (pode estar
// com defeito). Quem recebe a cobrança Saurus é o PDV Saurus (codsauruspdv), então dois pinpads
// ativos no mesmo PDV Saurus são uma opção só.
const maquinetasEnvio = computed(() => {
  const todas = maquinetas.value
    .filter((m) => m.integracao === 'P' || m.integracao === 'S')
    .map((m) =>
      m.integracao === 'S'
        ? {
            valor: `saurus-${m.codsauruspdv}`,
            tipoMaquineta: 'saurus',
            nome: 'SafraPay',
            logo: '/logo-cartoes/Safra.jpg',
            pos: m,
          }
        : {
            valor: `pagarme-${m.codpagarmepos}`,
            tipoMaquineta: 'pagarme',
            nome: 'Stone',
            logo: '/logo-cartoes/Stone.jpg',
            pos: m,
          },
    )
  return todas.filter((m, i) => todas.findIndex((o) => o.valor === m.valor) === i)
})

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

// Ordem da etapa: 0 = maquininha padrão do PDV (já pré-selecionada), 1 = Manual e, embaixo
// do cabeçalho "Outras Maquinetas", as demais da filial em 2, 3, 4…
// Sem padrão configurado (ou negócio de outro estoque local) não há o que destacar: Manual
// volta ao 0 e as maquininhas contam de 1, sob o cabeçalho "Maquinetas".
const opcoesModo = computed(() => {
  const sincronizado = sNegocio.negocio.sincronizado
  const padrao = maquinetasEnvio.value.find((m) => m.valor === valorPadrao.value) ?? null
  const outras = maquinetasEnvio.value.filter((m) => m !== padrao)

  // `grupo` só nas "outras": o ListaOpcoes abre o cabeçalho na 1a opção cujo grupo muda
  const maquininha = (m, grupo = null) => ({
    ...m,
    label: `${m.pos.apelido} · ${m.nome}`,
    caption: m === padrao ? 'Maquineta Padrão do PDV' : 'Enviar para a maquineta',
    icone: 'point_of_sale',
    cor: VISUAL.cartao.cor,
    grupo,
    desabilitado: !sincronizado,
    motivo: 'Negócio ainda não sincronizado com o servidor',
  })

  const opcoes = [
    ...(padrao ? [maquininha(padrao)] : []),
    {
      valor: 'manual',
      label: 'Manual',
      caption: 'Digitar a autorização da maquininha',
      icone: 'edit_note',
      cor: VISUAL.cartao.cor,
    },
    ...outras.map((m) => maquininha(m, padrao ? 'Outras Maquinetas' : 'Maquinetas')),
  ]

  if (!maquinetasEnvio.value.length) {
    opcoes.push({
      valor: 'sem-maquineta',
      label: 'Enviar para a maquininha',
      icone: 'point_of_sale',
      cor: VISUAL.cartao.cor,
      desabilitado: true,
      motivo: 'Nenhuma maquininha cadastrada nesta filial',
    })
  }

  // tecla = posição na tela; só os dez primeiros cabem em um dígito
  return opcoes.map((o, i) => ({ ...o, tecla: i < 10 ? i : null }))
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

// maquinetas do parceiro escolhido, as usadas recentemente neste PDV primeiro
const maquinetasParceiro = computed(() => {
  const lista = maquinetas.value.filter((m) => m.codpessoa === parceiro.value)
  const ordem = (m) => {
    const i = sNegocio.maquinetasRecentes.indexOf(m.codmaquineta)
    return i === -1 ? Infinity : i
  }
  return [...lista].sort((a, b) => ordem(a) - ordem(b))
})

const opcoesMaquineta = computed(() => {
  if (!maquinetasParceiro.value.length) {
    return [
      {
        valor: 'sem-maquineta',
        label: `Nenhuma maquineta ${parceiroAtual.value?.apelido ?? ''} nesta filial`,
        icone: 'point_of_sale',
        cor: VISUAL.cartao.cor,
        desabilitado: true,
        motivo: 'Cadastre em Contas → Maquinetas',
      },
    ]
  }
  return maquinetasParceiro.value.map((m) => {
    const recente = sNegocio.maquinetasRecentes.includes(m.codmaquineta)
    const detalhe = m.serial ?? (m.compartilhada ? 'todas as filiais' : null)
    return {
      valor: m.codmaquineta,
      label: m.apelido,
      caption: [detalhe, recente ? 'usada recentemente' : null].filter(Boolean).join(' · '),
      serial: m.serial,
      icone: recente ? 'history' : 'point_of_sale',
      cor: VISUAL.cartao.cor,
    }
  })
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

// maquineta obrigatória; com uma só (ex.: acesso de site) ela vem sozinha
const escolherParceiro = (opcao) => {
  parceiro.value = opcao.valor
  maquineta.value = null
  if (maquinetasParceiro.value.length === 1) {
    maquineta.value = maquinetasParceiro.value[0]
    depoisDaMaquineta()
    return
  }
  irPara('maquineta')
}

const escolherMaquineta = (opcao) => {
  maquineta.value = maquinetasParceiro.value.find((m) => m.codmaquineta === opcao.valor) ?? null
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
  if (!maquineta.value) {
    Notify.create({
      type: 'negative',
      message: 'Escolha a maquineta!',
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
    codmaquineta: maquineta.value.codmaquineta,
    maquineta: maquineta.value.apelido,
  })
  sNegocio.registrarMaquinetaRecente(maquineta.value.codmaquineta)
  emit('concluido')
}

// botão do rodapé do wizard: só as etapas que terminam em lançamento têm ação
const acao = computed(() =>
  etapa.value === 'autorizacao' ? { label: 'Lançar (Enter)', executar: salvarManual } : null,
)

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

defineExpose({ tecla, acao })
</script>
<template>
  <div>
    <template v-if="etapa === 'tipo'">
      <lista-opcoes ref="listaRef" :opcoes="TIPOS" @escolher="escolherTipo" />
    </template>

    <template v-else-if="etapa === 'parcelas'">
      <lista-opcoes ref="listaRef" :opcoes="planos" @escolher="escolherPlano" />
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
        @escolher="escolherMaquineta"
      />
    </template>

    <template v-else-if="etapa === 'bandeira'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesBandeira" @escolher="escolherBandeira" />
    </template>

    <template v-else-if="etapa === 'autorizacao'">
      <div class="text-center q-mb-md">
        <div class="text-h2 text-weight-bold text-primary">
          R$ {{ formataNumero(valorMaquininha) }}
        </div>
        <div class="text-subtitle1 text-grey-7">
          Passar na maquininha · {{ nomeTipo }}
          <template v-if="plano?.parcelas > 1"> · {{ plano.parcelas }}x</template>
          <template v-if="plano?.valorjuros"> · com juros</template>
        </div>
      </div>
      <div class="row justify-center">
        <div class="col-12 col-sm-6">
          <MgInput v-model="autorizacao" label="Código de autorização" autofocus maxlength="20" />
          <div class="text-caption text-grey-7 text-center q-mt-sm">Enter lança o pagamento</div>
        </div>
      </div>
    </template>

    <q-inner-loading :showing="enviando" />
  </div>
</template>
