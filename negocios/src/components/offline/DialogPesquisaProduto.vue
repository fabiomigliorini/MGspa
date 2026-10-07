<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero } from '@components/formatters'
import { produtoStore } from 'stores/produto'
import MgInput from '@components/MgInput.vue'

// Pesquisa de produto em grade de cards, na base offline (F1 do PDV e do quiosque).
// Emite `select` com o produto escolhido; o que fazer com ele é de quem usa.
//
// Navegação por teclado (a mesma do @components/MgDialogPesquisaProduto): o foco
// fica no campo de pesquisa o tempo todo. Enter pesquisa; a partir daí as setas
// andam pela grade e o Enter escolhe o card em destaque. Esc volta da grade
// para o campo.
//
// Fecha sozinho depois de TEMPO_INATIVIDADE sem uso; qualquer toque, tecla ou
// rolagem reinicia a contagem.
const emit = defineEmits(['select'])

const TEMPO_INATIVIDADE = 60000 // 60s sem uso -> fecha a pesquisa
const EVENTOS_USO = ['pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll']

const $q = useQuasar()
const sProduto = produtoStore()

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

// O teclado só funciona com o foco no campo (o keydown sobe dele): clicar
// na grade ou no fundo devolve o foco para lá. O q-select é a exceção, porque
// tem navegação própria e abre o menu fora do dialog.
const refPesquisa = ref(null)
const manterFoco = (evento) => {
  if (!sProduto.dialogPesquisa) return
  if (evento.relatedTarget?.closest?.('.q-select, .q-menu')) return
  nextTick(() => refPesquisa.value?.focus())
}

const fechar = () => {
  sProduto.dialogPesquisa = false
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
//
// Só vale o mouse que MEXEU de verdade. Quando a janela abre (ou a grade
// rola) com o ponteiro parado em cima de um card, o navegador também dispara
// mousemove, mas na mesma posição -- e aí o destaque não pode pular para lá,
// senão as setas de lado deixam de andar no texto da pesquisa.
let ultimaPosicaoMouse = null
const aoMoverMouse = (evento, indice) => {
  const posicao = `${evento.screenX},${evento.screenY}`
  const parado = ultimaPosicaoMouse === null || posicao === ultimaPosicaoMouse
  ultimaPosicaoMouse = posicao
  if (parado) return
  focado.value = indice
}

const focar = (indice) => {
  const total = sProduto.resultadoPesquisa.length
  if (!total) return
  focado.value = Math.max(0, Math.min(indice, total - 1))
  nextTick(() => cardRefs.value[focado.value]?.scrollIntoView({ block: 'nearest' }))
}

// Um handler só, no card do diálogo: o keydown sobe do campo de pesquisa.
const aoTeclar = (evento) => {
  // o q-select tem navegação própria nas opções; não roubar as teclas dele
  if (evento.target?.closest?.('.q-select')) return

  const itens = sProduto.resultadoPesquisa
  const total = itens.length
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
    if (atual >= 0 && itens[atual]) escolher(itens[atual])
    else sProduto.pesquisar()
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

// Lista nova ou texto editado desfazem o destaque: o Enter tem que voltar a
// significar "pesquisar" e não "escolher".
watch(
  () => sProduto.resultadoPesquisa,
  () => {
    cardRefs.value = []
    focado.value = -1
  },
)
watch(
  () => sProduto.textoPesquisa,
  () => {
    focado.value = -1
  },
)

// ---- fecha sozinho depois de um tempo sem uso ----
let timerInatividade = null

const reiniciarInatividade = () => {
  clearTimeout(timerInatividade)
  timerInatividade = setTimeout(fechar, TEMPO_INATIVIDADE)
}

// captura no document: pega também o que acontece dentro do dialog e a
// rolagem, que não borbulha
const ligarInatividade = () => {
  EVENTOS_USO.forEach((ev) =>
    document.addEventListener(ev, reiniciarInatividade, { capture: true, passive: true }),
  )
  reiniciarInatividade()
}

const desligarInatividade = () => {
  EVENTOS_USO.forEach((ev) =>
    document.removeEventListener(ev, reiniciarInatividade, { capture: true }),
  )
  clearTimeout(timerInatividade)
}

watch(
  () => sProduto.dialogPesquisa,
  (aberto) => {
    if (aberto) {
      // abre sempre com o teclado no campo, sem card em destaque
      focado.value = -1
      ultimaPosicaoMouse = null
      ligarInatividade()
    } else {
      desligarInatividade()
    }
  },
  { immediate: true },
)

onUnmounted(desligarInatividade)
</script>

<template>
  <q-dialog v-model="sProduto.dialogPesquisa" maximized>
    <q-card class="column full-height no-wrap" @keydown="aoTeclar">
      <q-card-section class="bg-primary text-white col-auto">
        <div class="row q-col-gutter-sm">
          <MgInput
            ref="refPesquisa"
            outlined
            autofocus
            v-model="sProduto.textoPesquisa"
            label="Pesquisa"
            bg-color="white"
            class="col"
            @blur="manterFoco"
          >
            <template v-slot:append>
              <q-btn round flat icon="close" tabindex="-1" @click="sProduto.textoPesquisa = ''">
                <q-tooltip class="bg-accent">Limpar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="search" tabindex="-1" @click="sProduto.pesquisar()">
                <q-tooltip class="bg-accent">Pesquisar</q-tooltip>
              </q-btn>
              <q-btn round flat icon="logout" tabindex="-1" @click="fechar">
                <q-tooltip class="bg-accent">Fechar</q-tooltip>
              </q-btn>
            </template>
          </MgInput>
          <q-select
            outlined
            borderless
            v-model="sProduto.sortPesquisa"
            :options="['Alfabética', 'Preço', 'Código', 'Barras']"
            label="Ordem"
            bg-color="white"
            style="width: 130px"
            @update:model-value="sProduto.pesquisar()"
          />
        </div>
        <div class="text-caption q-mt-xs">
          Enter pesquisa · setas andam pelos produtos · Enter escolhe o que estiver em destaque
        </div>
      </q-card-section>

      <q-card-section class="q-pa-none q-ma-none col scroll">
        <div class="row q-pa-md q-col-gutter-md">
          <div
            v-for="(produto, i) in sProduto.resultadoPesquisa"
            :key="produto.codprodutobarra"
            :ref="(el) => setCardRef(el, i)"
            class="col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6"
          >
            <q-card
              v-ripple
              class="cursor-pointer full-height"
              :class="i === focado ? 'bg-blue-1' : ''"
              :style="estiloCard(i)"
              :bordered="i === focado"
              @mousemove="aoMoverMouse($event, i)"
              @click="escolher(produto)"
            >
              <q-img ratio="1" :src="sProduto.urlImagem(produto.codimagem)" />

              <q-card-section>
                <div class="absolute" style="top: 0; right: 5px; transform: translateY(-37px)">
                  <q-chip color="grey-2" text-color="grey-7">
                    {{ produto.sigla }}
                    <template v-if="produto.quantidade > 0">
                      C/{{ formataNumero(produto.quantidade, 0) }}
                    </template>
                  </q-chip>
                </div>

                <div class="text-h5">
                  <small class="text-grey-7">R$</small>
                  {{ formataNumero(produto.preco) }}
                </div>
                <div class="text-caption text-grey-7 ellipsis-2-lines">
                  {{ produto.barras }} | {{ produto.produto }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
