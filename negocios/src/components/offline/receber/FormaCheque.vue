<script setup>
// Passo do wizard: cheque. Etapas: CMC7 (leitor ou digitado) → bom para → emitente.
// Cheque não dá troco (o wizard já bloqueia valor acima do saldo) e exige cliente identificado.
import { ref, computed, watch } from 'vue'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { negocioStore } from 'stores/negocio'
import { formataData } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import { parseCmc7 } from '../../../utils/cmc7.js'

const emit = defineEmits(['concluido'])

const sNegocio = negocioStore()

const etapa = ref('cmc7')
const cmc7Texto = ref('')
const vencimento = ref(new Date().toISOString().substr(0, 10))
const cnpj = ref('')
const emitente = ref('')
const nomeRef = ref(null)

const valor = computed(() => sNegocio.receber.valor)
const consumidor = computed(() => sNegocio.negocio.codpessoa == 1)
const cmc7 = computed(() => parseCmc7(cmc7Texto.value))
const hoje = new Date().toISOString().substr(0, 10)

// leitor termina em 30 dígitos: se online, puxa o último emitente dessa conta (melhor esforço)
watch(
  () => cmc7.value.valido,
  async (valido) => {
    if (!valido || cnpj.value) {
      return
    }
    try {
      const { data } = await api.get('/v1/cheque/consulta-cmc7/' + cmc7.value.cmc7)
      const ultimo = data?.ultimo?.emitentes?.[0]
      if (ultimo) {
        cnpj.value = String(ultimo.cnpj ?? '')
        emitente.value = ultimo.emitente ?? ''
      }
    } catch {
      // sem permissão ou sem conexão: o operador digita o emitente
    }
  },
)

const avisar = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const confirmarCmc7 = () => {
  if (!cmc7.value.valido) {
    avisar('CMC7 inválido! Passe o cheque no leitor ou confira os 30 dígitos.')
    return
  }
  etapa.value = 'vencimento'
}

const confirmarVencimento = () => {
  if (!vencimento.value || vencimento.value < hoje) {
    avisar('Informe a data do cheque (hoje ou pré-datado)!')
    return
  }
  etapa.value = 'emitente'
}

const salvar = async () => {
  const cnpjDigitos = String(cnpj.value ?? '').replace(/\D/g, '')
  const nome = (emitente.value ?? '').trim()
  if (!nome) {
    nomeRef.value?.focus()
    return
  }
  if (cnpjDigitos && ![11, 14].includes(cnpjDigitos.length)) {
    avisar('CPF/CNPJ do emitente inválido!')
    return
  }
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_CHEQUE ?? 1020),
    tipo: 2, // tPag Cheque
    valorpagamento: valor.value,
    cmc7: cmc7.value.cmc7,
    chequevencimento: vencimento.value,
    chequecnpj: cnpjDigitos ? parseInt(cnpjDigitos) : null,
    chequeemitente: nome,
  })
  emit('concluido')
}

// devolve true quando consumiu a tecla; o resto é digitação nos campos
const tecla = (e) => {
  if (consumidor.value) {
    return false
  }
  if (e.key === 'Escape') {
    if (etapa.value === 'vencimento') {
      etapa.value = 'cmc7'
      return true
    }
    if (etapa.value === 'emitente') {
      etapa.value = 'vencimento'
      return true
    }
    return false
  }
  if (e.key !== 'Enter') {
    return false
  }
  switch (etapa.value) {
    case 'cmc7':
      confirmarCmc7()
      return true
    case 'vencimento':
      confirmarVencimento()
      return true
    case 'emitente':
      salvar()
      return true
  }
  return false
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <q-banner v-if="consumidor" rounded class="bg-red-1 text-red-10">
      Cheque só para cliente identificado. Informe o cliente (F10) e tente de novo.
    </q-banner>

    <template v-else-if="etapa === 'cmc7'">
      <q-input
        v-model="cmc7Texto"
        label="CMC7 (passe o cheque no leitor)"
        outlined
        autofocus
        class="q-mb-sm"
      />
      <div class="text-caption q-mb-md" :class="cmc7.valido ? 'text-green-8' : 'text-grey-7'">
        <template v-if="cmc7.valido">
          Banco {{ cmc7.banco }} · Ag {{ cmc7.agencia }} · CC {{ cmc7.contacorrente }} · Nº
          {{ cmc7.numero }}
        </template>
        <template v-else>{{ cmc7.cmc7.length }}/30 dígitos</template>
      </div>
      <div class="text-caption text-grey-7">Enter continua</div>
    </template>

    <template v-else-if="etapa === 'vencimento'">
      <div class="text-caption text-grey-7 q-mb-sm">Cheque nº {{ cmc7.numero }}</div>
      <MgInputData v-model="vencimento" label="Bom para" :min="hoje" autofocus class="q-mb-sm" />
      <div class="text-caption text-grey-7">
        Hoje = à vista; data futura = pré-datado (fica no controle de cheques). Enter continua
      </div>
    </template>

    <template v-else>
      <div class="text-caption text-grey-7 q-mb-sm">
        Cheque nº {{ cmc7.numero }} · bom para {{ formataData(vencimento) }}
      </div>
      <q-input v-model="cnpj" label="CPF/CNPJ do emitente" outlined autofocus class="q-mb-md" />
      <q-input ref="nomeRef" v-model="emitente" label="Nome do emitente" outlined class="q-mb-sm" />
      <div class="text-caption text-grey-7">Enter lança o cheque</div>
    </template>
  </div>
</template>
