<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { formataNumeroInteligente } from '@components/formatters'
import MgDialogPesquisaProduto from '@components/MgDialogPesquisaProduto.vue'

// Campo de código de barras no formato do PDV, para telas online.
//
// Multiplicador: digitar "5*" fixa a quantidade em 5 e limpa o campo; o
// código que vier em seguida entra com essa quantidade. Depois de cada
// produto a quantidade volta para 1.
//
// O código é resolvido no backend (v1/select/produto-barra/barras/{codigo}),
// que aceita a barra exata ou o código do produto com 6 dígitos, e só
// responde quando cai num produto único.
//
// Emite `select` com (produto, quantidade). O produto vem no mesmo shape do
// MgSelectProdutoBarra: value, label, barras, preco, codproduto, sigla...
const props = defineProps({
  label: { type: String, default: 'Código de barras' },
  // liga o botão de lupa, que abre a pesquisa em grade
  pesquisa: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  bgColor: { type: String, default: '' },
})

const emit = defineEmits(['select'])

const codigo = ref('')
const quantidade = ref(1)
const buscando = ref(false)
const dialogPesquisa = ref(false)

const prefixoQuantidade = computed(() => `${formataNumeroInteligente(quantidade.value)} x`)

const notificar = (type, message) =>
  Notify.create({ type, message, timeout: 3000, actions: [{ icon: 'close', color: 'white' }] })

// "5*" -> quantidade 5. Roda a cada tecla, igual ao PDV.
const aoDigitar = (valor) => {
  codigo.value = valor ?? ''
  const txt = codigo.value
  if (typeof txt !== 'string' || txt.length < 2) return
  if (txt.charAt(txt.length - 1) !== '*') return
  const quant = parseFloat(txt.substring(0, txt.length - 1).replace(',', '.'))
  if (isNaN(quant) || quant === 0) return
  quantidade.value = Math.abs(quant)
  codigo.value = ''
}

const entregar = (produto) => {
  emit('select', produto, quantidade.value)
  quantidade.value = 1
  codigo.value = ''
}

const buscar = async () => {
  const txt = (codigo.value || '').trim()
  if (!txt || buscando.value) return
  buscando.value = true
  try {
    const { data } = await api.get(`v1/select/produto-barra/barras/${encodeURIComponent(txt)}`)
    entregar(Array.isArray(data) ? data[0] : data?.data || data)
  } catch (error) {
    const status = error?.response?.status
    // 404 = não achou, 409 = o código cai em vários produtos (variação de
    // cor, por exemplo). Nos dois casos quem digitou quer é encontrar o
    // produto, então a pesquisa abre sozinha com o texto — sem toast
    // vermelho, porque a própria janela mostra o resultado.
    if ((status === 404 || status === 409) && props.pesquisa) {
      dialogPesquisa.value = true
      return
    }
    notificar('negative', error?.response?.data?.message || `Falha ao buscar o código ${txt}!`)
  } finally {
    buscando.value = false
  }
}

const escolhidoNaPesquisa = (produto) => entregar(produto)

// F1 abre a pesquisa de qualquer lugar da tela, não só com o cursor no
// campo. O preventDefault é o que impede a ajuda do navegador de abrir.
const atalho = (evento) => {
  if (evento.key !== 'F1' || !props.pesquisa || dialogPesquisa.value) return
  evento.preventDefault()
  dialogPesquisa.value = true
}

onMounted(() => window.addEventListener('keydown', atalho))
onUnmounted(() => window.removeEventListener('keydown', atalho))
</script>

<template>
  <div>
    <q-input
      :model-value="codigo"
      type="text"
      outlined
      :label="label"
      :prefix="prefixoQuantidade"
      :autofocus="autofocus"
      :bg-color="bgColor"
      :loading="buscando"
      inputmode="tel"
      autocomplete="off"
      input-class="text-right"
      @update:model-value="aoDigitar"
      @keydown.enter.prevent="buscar"
    >
      <template #append>
        <q-btn round flat icon="add" :disable="!codigo" @click="buscar">
          <q-tooltip>Adicionar</q-tooltip>
        </q-btn>
        <q-btn v-if="pesquisa" round flat icon="search" @click="dialogPesquisa = true">
          <q-tooltip>Pesquisar (F1)</q-tooltip>
        </q-btn>
      </template>
    </q-input>

    <MgDialogPesquisaProduto
      v-if="pesquisa"
      v-model="dialogPesquisa"
      :busca-inicial="codigo"
      @select="escolhidoNaPesquisa"
    />
  </div>
</template>
