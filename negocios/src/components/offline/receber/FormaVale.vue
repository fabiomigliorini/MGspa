<script setup>
// Passo do wizard: vale compras. Código digitado ou bipado (VAL…), consulta na API,
// usa min(saldo do vale, valor deste pagamento). Enter lança.
import { ref, computed, watch, onMounted } from 'vue'
import { Notify, debounce } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { formataNumero } from '@components/formatters'

const emit = defineEmits(['concluido'])

const sNegocio = negocioStore()

const codigo = ref(null)
const titulo = ref(null)
const buscando = ref(false)

const valor = computed(() => sNegocio.receber.valor)

// quanto do vale vai ser usado neste pagamento
const valorUsar = computed(() => {
  if (!titulo.value) {
    return null
  }
  return Math.min(parseFloat(titulo.value.creditosaldo), valor.value)
})

const falta = computed(() => {
  if (!valorUsar.value) {
    return 0
  }
  return Math.round((valor.value - valorUsar.value) * 100) / 100
})

const buscar = debounce(async () => {
  titulo.value = null
  const cod = parseInt(String(codigo.value ?? '').replace(/\D/g, ''))
  if (!cod) {
    return
  }
  const jaUsado = (sNegocio.negocio.pagamentos ?? []).some((p) => p.codtitulo == cod)
  if (jaUsado) {
    Notify.create({
      type: 'negative',
      message: 'Este vale já foi usado neste negócio!',
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  buscando.value = true
  const ret = await sNegocio.buscarVale(cod)
  buscando.value = false
  if (!ret) {
    return
  }
  titulo.value = ret.data.data
}, 500)

watch(codigo, () => buscar())

onMounted(() => {
  if (sNegocio.receber.codtituloVale) {
    codigo.value = String(sNegocio.receber.codtituloVale)
  }
})

const salvar = async () => {
  if (!titulo.value || !valorUsar.value) {
    return false
  }
  // vale identifica o cliente quando a venda está no consumidor final
  if (sNegocio.negocio.codpessoa == 1) {
    await sNegocio.informarPessoa(titulo.value.codpessoa, null)
  }
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_VALE),
    tipo: 12, // tPag Vale Presente
    codtitulo: titulo.value.codtitulo,
    valorpagamento: valorUsar.value,
  })
  emit('concluido')
  return true
}

// devolve true quando consumiu a tecla; o resto é digitação no campo do código
const tecla = (e) => {
  if (e.key === 'Enter') {
    return salvar() !== false
  }
  return false
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <q-input
      v-model="codigo"
      label="Código do vale"
      outlined
      autofocus
      inputmode="numeric"
      :loading="buscando"
      class="q-mb-md"
    />

    <template v-if="titulo">
      <q-list class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label caption>Vale</q-item-label>
            <q-item-label>{{ titulo.numero }} · {{ titulo.fantasia }}</q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label caption>Saldo do vale</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label class="text-h6 text-grey-8">
              R$ {{ formataNumero(titulo.creditosaldo) }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label caption>Usar neste pagamento</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label class="text-h5 text-primary text-weight-bolder">
              R$ {{ formataNumero(valorUsar) }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
      <q-banner v-if="falta > 0" rounded class="bg-orange-1 text-orange-10 q-mb-md">
        <div class="row items-center">
          <div class="col text-subtitle1 text-weight-bold">FALTA</div>
          <div class="col-auto text-h5 text-weight-bolder">R$ {{ formataNumero(falta) }}</div>
        </div>
      </q-banner>
      <div class="text-caption text-grey-7">Enter lança o vale</div>
    </template>
  </div>
</template>
