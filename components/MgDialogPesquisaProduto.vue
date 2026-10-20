<script setup>
import MgInput from '@components/MgInput.vue'
import MgGradeProduto from '@components/MgGradeProduto.vue'
import MgSelect from '@components/MgSelect.vue'
import { ref, watch, nextTick } from 'vue'
import { api } from 'src/services/api'

// Pesquisa de produto em grade de cards, no formato da pesquisa do PDV.
// Diferença: esta é sempre online -- lê v1/select/produto-barra/pesquisa
// direto, sem base offline. Emite o código de barras escolhido no mesmo shape
// do select.
//
// A grade, o agrupamento por produto e a navegação por teclado são do
// @components/MgGradeProduto, o mesmo da pesquisa do PDV: o foco fica no campo
// de pesquisa o tempo todo; Enter pesquisa, as setas andam pela grade, Enter
// abre o produto, numa janela com as variações e as embalagens.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  // texto já digitado no campo de barras, para abrir com a busca pronta
  buscaInicial: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'select'])

// produtos por página, não barras: a página traz todas as barras de cada um
const PER_PAGE = 20

const busca = ref('')
// o texto que gerou a lista que está na tela
const buscaPesquisada = ref('')

// O rótulo é o que aparece; o valor é o que o backend entende.
//
// Relevância é o default (value null = o backend usa a ordem dele). Não dá
// para começar em Alfabética como o PDV faz: lá o texto é casado por
// substring, aqui por similaridade, que é frouxa de propósito -- em ordem
// alfabética o topo de "caneta azul" vira "# Uso Consumo Diversos 96081000
// Caneta", que está no resultado mas não é o que se procura.
const ORDENS = [
  { label: 'Relevância', value: null },
  { label: 'Alfabética', value: 'alfabetica' },
  { label: 'Preço', value: 'preco' },
  { label: 'Código', value: 'codigo' },
  { label: 'Barras', value: 'barras' },
]
const ordem = ref(ORDENS[0])
const itens = ref([])
const carregando = ref(false)
const pagina = ref(1)
const temMais = ref(false)
const pesquisou = ref(false)

const grade = ref(null)

// O teclado só funciona com o foco no campo (o keydown sobe dele): clicar
// na grade ou no fundo devolve o foco para lá. O q-select é a exceção, porque
// tem navegação própria e abre o menu fora do dialog; e a janela do produto
// que a grade abre, que tem teclado próprio.
const refPesquisa = ref(null)
const manterFoco = (evento) => {
  if (!props.modelValue) return
  if (evento.relatedTarget?.closest?.('.q-select, .q-menu')) return
  if (grade.value?.aberto) return
  nextTick(() => refPesquisa.value?.focus())
}

const fechar = () => emit('update:modelValue', false)

async function pesquisar(proximaPagina = false) {
  if (carregando.value) return
  carregando.value = true
  try {
    pagina.value = proximaPagina ? pagina.value + 1 : 1
    const { data } = await api.get('v1/select/produto-barra/pesquisa', {
      params: { busca: busca.value, page: pagina.value, ordem: ordem.value?.value },
    })
    const rows = Array.isArray(data) ? data : data?.data || []
    // a página seguinte entra na MESMA lista, para a grade não recomeçar
    if (proximaPagina) {
      itens.value.push(...rows)
    } else {
      itens.value = rows
      buscaPesquisada.value = busca.value
    }
    temMais.value = new Set(rows.map((r) => r.codproduto)).size === PER_PAGE
    pesquisou.value = true
  } finally {
    carregando.value = false
  }
}

const carregarMais = async (done) => {
  if (!temMais.value) {
    done(true)
    return
  }
  await pesquisar(true)
  done(!temMais.value)
}

const escolher = (barra) => {
  emit('select', barra)
  fechar()
}

// Um handler só, no card do diálogo: o keydown sobe do campo de pesquisa.
const aoTeclar = (evento) => {
  // o q-select tem navegação própria nas opções; não roubar as teclas dele
  if (evento.target?.closest?.('.q-select')) return
  if (grade.value?.teclar(evento)) return
  if (evento.key === 'Enter') {
    evento.preventDefault()
    pesquisar()
  }
}

// Editar o texto desfaz o destaque: a lista na tela passou a ser de outra
// busca, e o Enter tem que voltar a significar "pesquisar" e não "escolher".
watch(busca, (valor) => {
  if (valor !== buscaPesquisada.value) grade.value?.soltar()
})

// Ao abrir: se veio termo novo do campo de barras, pesquisa ele. Senão,
// reabre como estava -- texto, ordem e resultados. O componente nunca é
// destruído (o q-dialog só esconde), então o estado anterior continua nos
// refs; o cache é não jogar fora. A grade, que fica dentro do q-dialog, nasce
// de novo: volta para os produtos, sem destaque.
watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return

    const termo = props.buscaInicial || ''
    if (termo && termo !== buscaPesquisada.value) {
      busca.value = termo
      pesquisar()
      return
    }

    // primeira abertura sem termo alcança aqui com a lista vazia
    if (!itens.value.length && busca.value) pesquisar()
  },
)
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    maximized
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <q-card class="column full-height" @keydown="aoTeclar">
      <q-card-section class="bg-primary text-white col-auto">
        <div class="row q-col-gutter-sm">
          <MgInput
            ref="refPesquisa"
            v-model="busca"
            outlined
            autofocus
            bg-color="white"
            label="Pesquisa"
            class="col"
            @blur="manterFoco"
          >
            <template #append>
              <q-btn round flat icon="close" tabindex="-1" @click="busca = ''">
                <q-tooltip>Limpar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="search" tabindex="-1" @click="pesquisar()">
                <q-tooltip>Pesquisar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="logout" tabindex="-1" @click="fechar">
                <q-tooltip>Fechar</q-tooltip>
              </q-btn>
            </template>
          </MgInput>
          <MgSelect
            v-model="ordem"
            bg-color="white"
            label="Ordem"
            :options="ORDENS"
            style="width: 150px"
            @update:model-value="pesquisar()"
          />
        </div>
        <div class="text-caption q-mt-xs">
          Enter pesquisa · setas andam pelos produtos · Enter abre o que estiver em destaque · Esc
          volta
        </div>
      </q-card-section>

      <q-card-section class="q-pa-none col scroll">
        <MgGradeProduto
          ref="grade"
          :barras="itens"
          :tem-mais="temMais"
          @select="escolher"
          @carregar="carregarMais"
        />

        <div v-if="pesquisou && !itens.length" class="text-center text-grey-6 q-py-xl">
          <q-icon name="search_off" size="40px" class="text-grey-4 q-mb-sm" />
          <div>Nenhum produto encontrado. Melhore sua pesquisa.</div>
        </div>
        <div v-if="!pesquisou && !carregando" class="text-center text-grey-6 q-py-xl">
          <q-icon name="search" size="40px" class="text-grey-4 q-mb-sm" />
          <div>Digite o que procura e tecle Enter.</div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
