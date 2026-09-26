<script setup>
// Passo do wizard: vale compras. O cliente chegou com o papel: lê o VAL…, usa
// min(saldo, valor a receber). O wizard pula o passo 2 (valor) para esta forma:
// o valor utilizado é decidido aqui, olhando o saldo do vale.
//
// Os campos formam duas contas lado a lado, com o utilizado no meio abatendo das duas:
//   À Receber − utilizado = Saldo à Receber
//   Vale      − utilizado = Contra Vale
import { ref, computed, watch, onMounted } from 'vue'
import { Notify, debounce } from 'quasar'
import { negocioStore } from 'stores/negocio'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'

const emit = defineEmits(['concluido'])

const sNegocio = negocioStore()

const codigo = ref(null)
const titulo = ref(null)
const buscando = ref(false)
const utilizado = ref(null)

// veio da bipagem VAL… no input de barras: o código não se edita
const codigoLido = !!sNegocio.receber.codtituloVale

const valor = computed(() => sNegocio.receber.valor)

const saldoVale = computed(() => (titulo.value ? parseFloat(titulo.value.creditosaldo) : null))

// teto do utilizado: o que o vale tem e o que falta receber
const maximo = computed(() => {
  if (saldoVale.value === null) {
    return null
  }
  return Math.min(saldoVale.value, valor.value)
})

const arredondar = (v) => Math.round(v * 100) / 100

const saldoPagar = computed(() => arredondar(valor.value - (utilizado.value ?? 0)))

const saldoValeProjetado = computed(() =>
  saldoVale.value === null ? null : arredondar(saldoVale.value - (utilizado.value ?? 0)),
)

const avisar = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const buscar = debounce(async () => {
  titulo.value = null
  utilizado.value = null
  const cod = parseInt(String(codigo.value ?? '').replace(/\D/g, ''))
  if (!cod) {
    return
  }
  const jaUsado = (sNegocio.negocio.pagamentos ?? []).some((p) => p.codtitulo == cod)
  if (jaUsado) {
    avisar('Este vale já foi usado neste negócio!')
    return
  }
  buscando.value = true
  const ret = await sNegocio.buscarVale(cod)
  buscando.value = false
  if (!ret) {
    return
  }
  titulo.value = ret.data.data
  utilizado.value = maximo.value
}, 500)

watch(codigo, () => buscar())

onMounted(() => {
  if (codigoLido) {
    codigo.value = String(sNegocio.receber.codtituloVale)
  }
})

const salvar = async () => {
  if (!titulo.value) {
    return false
  }
  if (!utilizado.value || utilizado.value <= 0) {
    avisar('Informe o valor utilizado!')
    return false
  }
  if (utilizado.value > maximo.value) {
    avisar('Valor utilizado maior que o saldo do vale ou que o valor a receber!')
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
    valorpagamento: utilizado.value,
  })
  emit('concluido')
  return true
}

// devolve true quando consumiu a tecla; o resto é digitação nos campos
const tecla = (e) => {
  if (e.key === 'Enter') {
    salvar()
    return true
  }
  return false
}

const acao = computed(() => (titulo.value ? { label: 'Lançar (Enter)', executar: salvar } : null))

defineExpose({ tecla, acao })
</script>
<template>
  <div class="row q-col-gutter-md">
    <div class="col-12">
      <MgInput
        v-model="codigo"
        label="Código do vale"
        :autofocus="!codigoLido"
        :readonly="codigoLido"
        inputmode="numeric"
        :loading="buscando"
        :hint="titulo ? `${titulo.numero} · ${titulo.fantasia}` : undefined"
      />
    </div>

    <!-- esquerda: a venda / direita: o vale -->
    <div class="col-6">
      <MgInputValor
        :model-value="valor"
        label="À Receber"
        prefix="R$"
        readonly
        input-class="text-h6"
      />
    </div>
    <div class="col-6">
      <MgInputValor
        :model-value="saldoVale"
        label="Vale"
        prefix="R$"
        readonly
        input-class="text-h6"
      />
    </div>

    <!-- o utilizado abate das duas colunas -->
    <div class="col-12">
      <MgInputValor
        v-model="utilizado"
        label="Valor utilizado"
        prefix="R$"
        :min="0"
        :max="maximo"
        :readonly="!titulo"
        input-class="text-h5 text-weight-bold text-primary"
      />
    </div>

    <div class="col-6">
      <MgInputValor
        :model-value="titulo ? saldoPagar : null"
        label="Saldo à Receber"
        prefix="R$"
        readonly
        :input-class="
          'text-h6 text-weight-bold ' + (saldoPagar > 0 ? 'text-orange-10' : 'text-green-9')
        "
      />
    </div>
    <div class="col-6">
      <MgInputValor
        :model-value="saldoValeProjetado"
        label="Contra Vale"
        prefix="R$"
        readonly
        input-class="text-h6 text-grey-8"
      />
    </div>
  </div>
</template>
