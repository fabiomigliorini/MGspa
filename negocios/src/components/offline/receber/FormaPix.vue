<script setup>
// Passo do wizard: PIX. Etapa 1 = QR Code (cobrança no banco) ou chave manual;
// etapa 2 (só QR) = conta que recebe. QR emite 'cobranca' para o wizard abrir o dialog do PixCob.
import { ref, computed, onMounted } from 'vue'
import { negocioStore } from 'stores/negocio'
import { pixStore } from 'stores/pix'
import { db } from 'boot/db'
import ListaOpcoes from './ListaOpcoes.vue'
import { VISUAL } from '../../../utils/pagamento.js'

const emit = defineEmits(['concluido', 'cobranca'])

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

// contas da filial do negócio primeiro; o separador só aparece quando há dos dois grupos
const opcoesConta = computed(() => {
  const daFilial = portadores.value.filter((p) => p.codfilial === codfilial.value)
  const outras = portadores.value.filter((p) => p.codfilial !== codfilial.value)
  const agrupar = daFilial.length > 0 && outras.length > 0
  return [...daFilial, ...outras].map((p, i) => ({
    tecla: i < 9 ? i + 1 : null,
    valor: p.codportador,
    label: p.banco,
    caption:
      p.codfilial === codfilial.value || !p.filial
        ? `Conta ${p.conta}-${p.contadigito}`
        : `Conta ${p.conta}-${p.contadigito} · ${p.filial}`,
    logo: `/bancos/${p.codbanco}.svg`,
    icone: 'account_balance',
    cor: VISUAL.pix.cor,
    grupo: agrupar ? (p.codfilial === codfilial.value ? 'Da filial' : 'Outras filiais') : null,
  }))
})

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
    <template v-else>
      <lista-opcoes
        ref="listaRef"
        :opcoes="opcoesConta"
        :inicial="sNegocio.padrao.codportador"
        @escolher="(o) => qr(o.valor)"
      />
    </template>
    <q-inner-loading :showing="criando" />
  </div>
</template>
