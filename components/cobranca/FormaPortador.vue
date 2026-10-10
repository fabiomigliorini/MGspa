<script setup>
// Passo do wizard no contas: de/para qual portador o dinheiro anda.
// Dinheiro: cofre, troco ou Caixa Financeiro (o troco e o desconto já vêm calculados em `base`).
// Banco: como andou (transferência/TED, depósito, boleto) → conta. PIX pela chave não: é só da
// venda (parcela a receber); o PIX que entra chega pelo banco e se amarra pelo "Já recebido".
// Cartão da empresa: qual cartão (portador de cartão de crédito da empresa).
import { ref, computed } from 'vue'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import ListaOpcoes from './ListaOpcoes.vue'
import ListaFiltravel from './ListaFiltravel.vue'
import { MEIO, VISUAL } from './pagamento.js'
import { opcoesPortador } from './portadores.js'

const props = defineProps({
  // pagamento em dinheiro já calculado pelo wizard (principal, desconto, troco)
  base: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['pagamento'])

const sCobranca = cobrancaStore()
const listaRef = ref(null)

const MEIOS_BANCO = [
  { tecla: 1, valor: MEIO.TRANSFERENCIA, label: 'Transferência / TED', ...VISUAL.banco },
  { tecla: 2, valor: MEIO.DEPOSITO, label: 'Depósito', ...VISUAL.banco },
  { tecla: 3, valor: MEIO.BOLETO, label: 'Boleto', ...VISUAL.boleto },
]

const forma = sCobranca.forma
const etapa = ref(forma === 'banco' ? 'meio' : 'portador')
const meio = ref(null)

const TIPOS = { dinheiro: ['E'], banco: ['B', 'A'], cartaoEmpresa: ['C'] }
const opcoes = computed(() => opcoesPortador(sCobranca, TIPOS[forma] ?? []))

const titulo = computed(() => {
  if (forma === 'dinheiro') return 'Cofre, troco ou caixa do dinheiro'
  if (forma === 'cartaoEmpresa') return 'Cartão da empresa'
  return 'Conta do banco'
})

const escolherMeio = (opcao) => {
  meio.value = opcao.valor
  etapa.value = 'portador'
}

const escolherPortador = (opcao) => {
  const destino = { codportador: opcao.valor, portador: opcao.label }
  if (forma === 'dinheiro') {
    emit('pagamento', { ...props.base, ...destino })
    return
  }
  emit('pagamento', {
    meio: forma === 'cartaoEmpresa' ? MEIO.CREDITO : meio.value,
    principal: sCobranca.valor,
    ...destino,
  })
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape' && etapa.value === 'portador' && forma === 'banco') {
    etapa.value = 'meio'
    return true
  }
  return !!listaRef.value?.tecla(e)
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <template v-if="etapa === 'meio'">
      <lista-opcoes ref="listaRef" :opcoes="MEIOS_BANCO" @escolher="escolherMeio" />
    </template>
    <template v-else>
      <div class="text-subtitle1 text-grey-8 q-mb-sm">{{ titulo }}</div>
      <div v-if="!opcoes.length" class="text-grey-6 text-italic q-pa-sm">
        Nenhum portador disponível
      </div>
      <lista-filtravel
        v-else
        ref="listaRef"
        :opcoes="opcoes"
        label="Portador (nome, banco ou filial)"
        @escolher="escolherPortador"
      />
    </template>
  </div>
</template>
