<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero } from '@components/formatters'

// Pesquisa de produto em grade de cards, no formato da pesquisa do PDV.
// Diferença: esta é sempre online -- lê v1/select/produto-barra direto,
// sem base offline. Emite o produto escolhido no mesmo shape do select.
//
// Navegação por teclado: o foco fica no campo de pesquisa o tempo todo.
// Enter pesquisa; a partir daí as setas andam pela grade e o Enter escolhe
// o card em destaque. Esc volta da grade para o campo.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  // texto já digitado no campo de barras, para abrir com a busca pronta
  buscaInicial: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const PER_PAGE = 20
const $q = useQuasar()

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

// -1 = nenhum card em destaque, o teclado ainda é do campo de pesquisa
const focado = ref(-1)
const cardRefs = ref([])
const setCardRef = (el, i) => {
  if (el) cardRefs.value[i] = el
}

// quantos cards cabem por linha -- tem que bater com as classes col-* do
// template, senão as setas de cima/baixo pulam errado
const porLinha = computed(() => {
  switch ($q.screen.name) {
    case 'xs':
      return 2
    case 'sm':
    case 'md':
      return 4
    default:
      return 6
  }
})

const fechar = () => emit('update:modelValue', false)

async function pesquisar(proximaPagina = false) {
  if (carregando.value) return
  carregando.value = true
  try {
    pagina.value = proximaPagina ? pagina.value + 1 : 1
    const { data } = await api.get('v1/select/produto-barra', {
      params: { busca: busca.value, page: pagina.value, ordem: ordem.value?.value },
    })
    const rows = Array.isArray(data) ? data : data?.data || []
    if (proximaPagina) {
      itens.value = itens.value.concat(rows)
    } else {
      itens.value = rows
      cardRefs.value = []
      focado.value = -1
      buscaPesquisada.value = busca.value
    }
    temMais.value = rows.length === PER_PAGE
    pesquisou.value = true
  } finally {
    carregando.value = false
  }
}

const carregarMais = async (indice, done) => {
  if (!temMais.value) {
    done(true)
    return
  }
  await pesquisar(true)
  done(!temMais.value)
}

const escolher = (produto) => {
  emit('select', produto)
  fechar()
}

// Zoom de 15% no card em destaque. Quasar nao tem classe de escala, entao
// vai inline. O z-index sobe para o card crescer por cima dos vizinhos.
const estiloCard = (i) =>
  i === focado.value
    ? 'transition: transform .15s; transform: scale(1.15); position: relative; z-index: 1'
    : 'transition: transform .15s'

// Mouse e teclado mexem no MESMO destaque. Aqui não se usa focar(), porque
// ele rola o card para dentro da tela -- sob o ponteiro isso seria um
// solavanco, e o card já está visível de qualquer forma.
const aoPassarMouse = (indice) => {
  focado.value = indice
}

const focar = (indice) => {
  const total = itens.value.length
  if (!total) return
  focado.value = Math.max(0, Math.min(indice, total - 1))
  nextTick(() => cardRefs.value[focado.value]?.scrollIntoView({ block: 'nearest' }))
}

// Um handler só, no card do diálogo: o keydown sobe do campo de pesquisa.
const aoTeclar = (evento) => {
  // o q-select tem navegação própria nas opções; não roubar as teclas dele
  if (evento.target?.closest?.('.q-select')) return

  const total = itens.value.length
  const atual = focado.value

  // Esc com card em destaque só sai da grade. O stopPropagation é o que
  // impede o q-dialog de fechar junto -- ele escuta o Esc lá no document.
  if (evento.key === 'Escape' && atual >= 0) {
    evento.preventDefault()
    evento.stopPropagation()
    focado.value = -1
    return
  }
  if (evento.key === 'Enter') {
    evento.preventDefault()
    if (atual >= 0 && itens.value[atual]) escolher(itens.value[atual])
    else pesquisar()
    return
  }
  if (!total) return

  // com nenhum card em destaque, só para baixo entra na grade -- assim as
  // setas de lado continuam movendo o cursor dentro do texto digitado
  if (atual < 0) {
    if (evento.key === 'ArrowDown') {
      evento.preventDefault()
      focar(0)
    }
    return
  }

  switch (evento.key) {
    case 'ArrowRight':
      evento.preventDefault()
      focar(atual + 1)
      break
    case 'ArrowLeft':
      evento.preventDefault()
      if (atual === 0) focado.value = -1
      else focar(atual - 1)
      break
    case 'ArrowDown':
      evento.preventDefault()
      focar(atual + porLinha.value)
      break
    case 'ArrowUp':
      evento.preventDefault()
      if (atual < porLinha.value) focado.value = -1
      else focar(atual - porLinha.value)
      break
  }
}

// Editar o texto desfaz o destaque: a lista na tela passou a ser de outra
// busca, e o Enter tem que voltar a significar "pesquisar" e não "escolher".
watch(busca, (valor) => {
  if (valor !== buscaPesquisada.value) focado.value = -1
})

// Ao abrir: se veio termo novo do campo de barras, pesquisa ele. Senão,
// reabre exatamente como estava -- texto, ordem, resultados e destaque.
// O componente nunca é destruído (o q-dialog só esconde), então o estado
// anterior continua nos refs; o cache é não jogar fora.
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
          <q-input v-model="busca" outlined autofocus bg-color="white" label="Pesquisa" class="col">
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
          </q-input>
          <q-select
            v-model="ordem"
            outlined
            bg-color="white"
            label="Ordem"
            :options="ORDENS"
            style="width: 150px"
            @update:model-value="pesquisar()"
          />
        </div>
        <div class="text-caption q-mt-xs">
          Enter pesquisa · setas andam pelos produtos · Enter escolhe o que estiver em destaque
        </div>
      </q-card-section>

      <q-card-section class="q-pa-none col scroll">
        <q-infinite-scroll @load="carregarMais" :offset="250">
          <div class="row q-pa-md q-col-gutter-md">
            <div
              v-for="(produto, i) in itens"
              :key="produto.codprodutobarra"
              :ref="(el) => setCardRef(el, i)"
              class="col-xs-6 col-sm-3 col-md-3 col-lg-2 col-xl-2"
            >
              <q-card
                v-ripple
                class="cursor-pointer"
                :class="i === focado ? 'bg-blue-1' : ''"
                :style="estiloCard(i)"
                :bordered="i === focado"
                @mouseenter="aoPassarMouse(i)"
                @click="escolher(produto)"
              >
                <q-img ratio="1" :src="produto.imagem" />
                <q-card-section>
                  <div class="absolute" style="top: 0; right: 5px; transform: translateY(-37px)">
                    <q-chip color="grey-2" text-color="grey-7">
                      {{ produto.sigla }}
                    </q-chip>
                  </div>
                  <div class="text-h5">
                    <small class="text-grey-7">R$</small>
                    {{ formataNumero(produto.preco) }}
                  </div>
                  <div class="text-caption text-grey-7">
                    {{ produto.barras }} | {{ produto.descricao }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <div v-if="pesquisou && !itens.length" class="text-center text-grey-6 q-py-xl">
            <q-icon name="search_off" size="40px" class="text-grey-4 q-mb-sm" />
            <div>Nenhum produto encontrado. Melhore sua pesquisa.</div>
          </div>
          <div v-if="!pesquisou && !carregando" class="text-center text-grey-6 q-py-xl">
            <q-icon name="search" size="40px" class="text-grey-4 q-mb-sm" />
            <div>Digite o que procura e tecle Enter.</div>
          </div>

          <template #loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="32px" />
            </div>
          </template>
        </q-infinite-scroll>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
