<script setup>
// Passo do wizard: PIX. Etapa 1 = QR Code (cobrança no banco) ou chave manual;
// etapa 2 (só QR) = conta que recebe; etapa 3 (opcional) = contas das outras filiais.
// QR emite 'cobranca' para o wizard abrir o dialog do PixCob.
import { ref, computed, onMounted } from 'vue'
import { negocioStore } from 'stores/negocio'
import { pixStore } from 'stores/pix'
import { db } from 'boot/db'
import ListaOpcoes from './ListaOpcoes.vue'
import { VISUAL } from '../../../utils/pagamento.js'

const emit = defineEmits(['concluido', 'cobranca'])

// As duas contas que abrem a listagem saem por banco, não por codportador: o Sicredi é a
// conta da empresa mãe (vale para qualquer PDV) e o BB é sempre o da filial do negócio.
// Mesma convenção do PixService no backend, que já acha o BB da filial por codbanco.
const CODBANCO_SICREDI = 748
const CODBANCO_BB = 1

const sNegocio = negocioStore()
const sPix = pixStore()

const listaRef = ref(null)
const etapa = ref('modo')
const portadores = ref([])
const codfilial = ref(null)
const criando = ref(false)

const valor = computed(() => sNegocio.receber.valor)

onMounted(async () => {
  const loc = await db.estoqueLocal.get(sNegocio.negocio.codestoquelocal)
  if (loc?.codfilial) {
    codfilial.value = loc.codfilial
    portadores.value = await sPix.carregarPortadores(loc.codfilial)
  }
})

const opcoesModo = computed(() => {
  let motivoQr = null
  if (!sNegocio.negocio.sincronizado) {
    motivoQr = 'Negócio ainda não sincronizado com o servidor'
  } else if (!portadores.value.length) {
    motivoQr = 'Nenhuma conta PIX disponível (sem conexão?)'
  }
  return [
    {
      tecla: 1,
      valor: 'qr',
      label: 'QR Code',
      caption: 'Gera a cobrança no banco e confirma sozinho',
      icone: 'qr_code',
      cor: VISUAL.pix.cor,
      desabilitado: !!motivoQr,
      motivo: motivoQr,
    },
    {
      tecla: 2,
      valor: 'chave',
      label: 'Pela chave (manual)',
      caption: 'Cliente já transferiu; lança como depósito',
      icone: 'key',
      cor: VISUAL.pix.cor,
    },
  ]
})

const doBanco = (codbanco) =>
  portadores.value.find((p) => p.codbanco === codbanco && p.codfilial === codfilial.value) ??
  portadores.value.find((p) => p.codbanco === codbanco)

// Sicredi da empresa mãe serve qualquer filial; o BB tem que ser o da filial do negócio.
// Numeração sem buracos: sem Sicredi, o BB da filial assume o 0.
const primarios = computed(() =>
  [
    doBanco(CODBANCO_SICREDI),
    portadores.value.find((p) => p.codbanco === CODBANCO_BB && p.codfilial === codfilial.value),
  ].filter(Boolean),
)

const outros = computed(() => portadores.value.filter((p) => !primarios.value.includes(p)))

const montarOpcao = (p, tecla) => ({
  tecla,
  valor: p.codportador,
  label: p.banco,
  // a filial vem sempre, mesmo na conta da própria filial: o operador confere de quem é a conta
  caption: p.filial
    ? `Conta ${p.conta}-${p.contadigito} · ${p.filial}`
    : `Conta ${p.conta}-${p.contadigito}`,
  logo: `/bancos/${p.codbanco}.svg`,
  icone: 'account_balance',
  cor: VISUAL.pix.cor,
})

// abre só com as contas do dia a dia; as demais ficam atrás do pivô
const opcoesConta = computed(() => {
  const itens = primarios.value.map((p, i) => montarOpcao(p, i))
  if (outros.value.length) {
    itens.push({
      tecla: null,
      valor: '__outras',
      label: 'Selecione portador de outra filial',
      icone: 'more_horiz',
      cor: 'grey-6',
    })
  }
  return itens
})

// continua a contagem de onde a listagem principal parou
const opcoesOutras = computed(() =>
  outros.value.map((p, i) =>
    montarOpcao(p, primarios.value.length + i < 10 ? primarios.value.length + i : null),
  ),
)

const escolherModo = (opcao) => {
  if (opcao.valor === 'chave') {
    chave()
    return
  }
  if (portadores.value.length === 1) {
    qr(portadores.value[0].codportador)
    return
  }
  etapa.value = 'conta'
}

const escolherConta = (opcao) => {
  if (opcao.valor === '__outras') {
    etapa.value = 'outras'
    return
  }
  qr(opcao.valor)
}

const chave = async () => {
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_PIXCHAVE),
    tipo: 16, // Depósito Bancário
    valorpagamento: valor.value,
    parcelas: 1,
    valorparcela: valor.value,
  })
  emit('concluido')
}

const qr = async (codportador) => {
  if (criando.value) {
    return
  }
  criando.value = true
  const cob = await sNegocio.criarPixCob(valor.value, codportador)
  criando.value = false
  if (!cob) {
    return
  }
  emit('cobranca', { tipo: 'pix', dados: cob })
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape') {
    if (etapa.value === 'outras') {
      etapa.value = 'conta'
      return true
    }
    if (etapa.value === 'conta') {
      etapa.value = 'modo'
      return true
    }
    return false
  }
  return !!listaRef.value?.tecla(e)
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <template v-if="etapa === 'modo'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesModo" @escolher="escolherModo" />
    </template>
    <template v-else-if="etapa === 'conta'">
      <lista-opcoes
        ref="listaRef"
        :opcoes="opcoesConta"
        :inicial="sNegocio.padrao.codportador"
        @escolher="escolherConta"
      />
    </template>
    <template v-else>
      <lista-opcoes
        ref="listaRef"
        :opcoes="opcoesOutras"
        :inicial="sNegocio.padrao.codportador"
        @escolher="(o) => qr(o.valor)"
      />
    </template>
    <q-inner-loading :showing="criando" />
  </div>
</template>
