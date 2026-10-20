<script setup>
import { ref, shallowRef, computed, watch, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero } from '@components/formatters'

// Grade de cards da pesquisa de produto, comum à pesquisa do PDV (offline) e à
// das telas online (TASK-173). Recebe o resultado da pesquisa, uma linha por
// código de barras, e mostra UM card por produto. A escolha vai em etapas, que
// colapsam quando há uma opção só: 1ª o produto, 2ª a variação (se houver mais
// de uma), 3ª a embalagem (se houver mais de uma). A embalagem é sigla +
// quantidade: duas barras da mesma embalagem aparecem uma vez, e o que sai é a
// primeira delas -- o preço é o mesmo. Só entra o que veio na lista: quem
// pesquisou "verde" não vê a azul.
//
// Teclado: o foco fica no campo de pesquisa de quem usa a grade, e o keydown
// dele vem para teclar(). Para baixo entra na grade, as setas andam pelas
// opções da etapa, Enter avança e Esc volta uma etapa (da 1ª, volta ao campo).
// teclar() devolve false para o que não é dela: o Enter sem card em destaque
// (pesquisar) e o Esc já no campo (fechar a janela).
const props = defineProps({
  // uma linha por código de barras, na ordem em que os produtos aparecem; o
  // catálogo do PDV chama a descrição completa de `produto`, o select de
  // `descricao`
  barras: { type: Array, default: () => [] },
  // url da foto de uma linha
  imagem: { type: Function, default: (barra) => barra.imagem },
  // liga o infinite scroll, que pede a página seguinte com `carregar`
  temMais: { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'carregar'])

const $q = useQuasar()

const rotuloEmbalagem = (barra) =>
  barra.quantidade > 0 ? `${barra.sigla} C/${formataNumero(barra.quantidade, 0)}` : barra.sigla

const porQuantidade = (a, b) =>
  (Number(a.barra.quantidade) || 0) - (Number(b.barra.quantidade) || 0) ||
  String(a.barra.sigla).localeCompare(String(b.barra.sigla))

// produto -> variação -> embalagem. Cada nível é um card: `barra` é a linha
// que o representa (a foto, e o que sai quando é opção única) e `precos` são
// as embalagens debaixo dele, uma por linha no card.
const produtos = computed(() => {
  const mapa = new Map()
  for (const barra of props.barras) {
    let produto = mapa.get(barra.codproduto)
    if (!produto) {
      produto = { chave: barra.codproduto, titulo: barra.nome, barra, variacoes: new Map() }
      mapa.set(barra.codproduto, produto)
    }
    const textoVariacao = barra.variacao || ''
    let variacao = produto.variacoes.get(textoVariacao)
    if (!variacao) {
      variacao = { chave: textoVariacao, titulo: textoVariacao, barra, embalagens: new Map() }
      produto.variacoes.set(textoVariacao, variacao)
    }
    const chaveEmbalagem = `${barra.sigla}|${barra.quantidade ?? ''}`
    if (!variacao.embalagens.has(chaveEmbalagem)) {
      variacao.embalagens.set(chaveEmbalagem, {
        chave: chaveEmbalagem,
        titulo: rotuloEmbalagem(barra),
        barra,
      })
    }
  }

  return [...mapa.values()].map((produto) => {
    const variacoes = [...produto.variacoes.values()]
      .sort((a, b) => a.chave.localeCompare(b.chave))
      .map((variacao) => {
        const embalagens = [...variacao.embalagens.values()].sort(porQuantidade)
        return {
          ...variacao,
          titulo: variacao.titulo || produto.titulo,
          embalagens,
          precos: embalagens,
        }
      })
    const precos = new Map()
    variacoes.forEach((v) =>
      v.embalagens.forEach((e) => precos.has(e.chave) || precos.set(e.chave, e)),
    )
    return { ...produto, variacoes, precos: [...precos.values()].sort(porQuantidade) }
  })
})

// etapa em que a grade está: nenhum aberto = produtos
const produtoAberto = shallowRef(null)
const variacaoAberta = shallowRef(null)
// card de onde se entrou em cada etapa, para o Esc voltar o destaque nele
let indiceProduto = -1
let indiceVariacao = -1

const opcoes = computed(() => {
  if (variacaoAberta.value) return variacaoAberta.value.embalagens
  if (produtoAberto.value) return produtoAberto.value.variacoes
  return produtos.value
})

// a embalagem é o seu próprio preço
const precosDe = (opcao) => opcao.precos || [opcao]

// card de uma opção só: mostra preço, embalagem e barras, como era o card de
// cada código de barras
const unica = (opcao) => precosDe(opcao).length === 1 && !(opcao.variacoes?.length > 1)

// -1 = nenhum card em destaque, o teclado ainda é do campo de pesquisa
const focado = ref(-1)
const raiz = ref(null)
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

const focar = (indice) => {
  const total = opcoes.value.length
  if (!total) return
  focado.value = Math.max(0, Math.min(indice, total - 1))
  nextTick(() => cardRefs.value[focado.value]?.scrollIntoView({ block: 'nearest' }))
}

// Etapa nova começa no topo, com a primeira opção em destaque: o Enter de
// novo já avança.
const entrarEtapa = () => {
  cardRefs.value = []
  focado.value = 0
  nextTick(() => raiz.value?.scrollIntoView({ block: 'start' }))
}

const escolher = (embalagem) => {
  emit('select', embalagem.barra)
  // a janela fecha; se reabrir, a grade volta para os produtos
  produtoAberto.value = null
  variacaoAberta.value = null
}

const abrirVariacao = (variacao, indice) => {
  if (variacao.embalagens.length === 1) {
    escolher(variacao.embalagens[0])
    return
  }
  indiceVariacao = indice
  variacaoAberta.value = variacao
  entrarEtapa()
}

const abrirProduto = (produto, indice) => {
  indiceProduto = indice
  produtoAberto.value = produto
  // uma variação só: pula direto para as embalagens dela
  if (produto.variacoes.length === 1) abrirVariacao(produto.variacoes[0], 0)
  else entrarEtapa()
}

const avancar = (opcao, indice) => {
  if (variacaoAberta.value) escolher(opcao)
  else if (produtoAberto.value) abrirVariacao(opcao, indice)
  else abrirProduto(opcao, indice)
}

// volta uma etapa; a de variações é pulada se o produto só tem uma
const voltar = () => {
  cardRefs.value = []
  if (variacaoAberta.value && produtoAberto.value.variacoes.length > 1) {
    variacaoAberta.value = null
    focar(indiceVariacao)
    return
  }
  variacaoAberta.value = null
  produtoAberto.value = null
  focar(indiceProduto)
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
// rola, ou muda de etapa) com o ponteiro parado em cima de um card, o
// navegador também dispara mousemove, mas na mesma posição -- e aí o destaque
// não pode pular para lá, senão as setas de lado deixam de andar no texto da
// pesquisa.
let ultimaPosicaoMouse = null
const aoMoverMouse = (evento, indice) => {
  const posicao = `${evento.screenX},${evento.screenY}`
  const parado = ultimaPosicaoMouse === null || posicao === ultimaPosicaoMouse
  ultimaPosicaoMouse = posicao
  if (parado) return
  focado.value = indice
}

const teclar = (evento) => {
  const total = opcoes.value.length
  const atual = focado.value

  // O stopPropagation é o que impede o q-dialog de fechar junto -- ele escuta
  // o Esc lá no document.
  if (evento.key === 'Escape') {
    if (!produtoAberto.value && atual < 0) return false
    evento.preventDefault()
    evento.stopPropagation()
    if (produtoAberto.value) voltar()
    else focado.value = -1
    return true
  }
  if (evento.key === 'Enter') {
    if (atual < 0 || !opcoes.value[atual]) return false
    evento.preventDefault()
    avancar(opcoes.value[atual], atual)
    return true
  }
  if (!total) return false

  // com nenhum card em destaque, só para baixo entra na grade -- assim as
  // setas de lado continuam movendo o cursor dentro do texto digitado
  if (atual < 0) {
    if (evento.key !== 'ArrowDown') return false
    evento.preventDefault()
    focar(0)
    return true
  }

  switch (evento.key) {
    case 'ArrowRight':
      focar(atual + 1)
      break
    case 'ArrowLeft':
      if (atual === 0) focado.value = -1
      else focar(atual - 1)
      break
    case 'ArrowDown':
      focar(atual + porLinha.value)
      break
    case 'ArrowUp':
      if (atual < porLinha.value) focado.value = -1
      else focar(atual - porLinha.value)
      break
    default:
      return false
  }
  evento.preventDefault()
  return true
}

// texto da pesquisa editado: o Enter tem que voltar a significar "pesquisar"
const soltar = () => {
  focado.value = -1
}

// Lista nova (outra pesquisa) recomeça nos produtos, sem destaque. A página
// seguinte do infinite scroll entra na MESMA lista e não passa por aqui.
watch(
  () => props.barras,
  () => {
    produtoAberto.value = null
    variacaoAberta.value = null
    cardRefs.value = []
    focado.value = -1
  },
)

defineExpose({ teclar, soltar })
</script>

<template>
  <div ref="raiz">
    <div v-if="produtoAberto" class="row items-center no-wrap q-px-md q-pt-md">
      <q-btn flat round icon="arrow_back" color="grey-7" tabindex="-1" @click="voltar">
        <q-tooltip>Voltar (Esc)</q-tooltip>
      </q-btn>
      <div class="text-subtitle1 q-ml-sm ellipsis">
        {{ produtoAberto.titulo }}
        <template v-if="variacaoAberta && produtoAberto.variacoes.length > 1">
          · {{ variacaoAberta.titulo }}
        </template>
      </div>
    </div>

    <q-infinite-scroll
      :disable="!temMais || !!produtoAberto"
      :offset="250"
      @load="(indice, done) => emit('carregar', done)"
    >
      <div class="row q-pa-md q-col-gutter-md">
        <div
          v-for="(opcao, i) in opcoes"
          :key="opcao.chave"
          :ref="(el) => setCardRef(el, i)"
          class="col-xs-6 col-sm-3 col-md-3 col-lg-2 col-xl-2"
        >
          <q-card
            v-ripple
            class="cursor-pointer full-height"
            :class="i === focado ? 'bg-blue-1' : ''"
            :style="estiloCard(i)"
            :bordered="i === focado"
            @mousemove="aoMoverMouse($event, i)"
            @click="avancar(opcao, i)"
          >
            <q-img ratio="1" :src="imagem(opcao.barra)" />

            <q-card-section v-if="unica(opcao)">
              <div class="absolute" style="top: 0; right: 5px; transform: translateY(-37px)">
                <q-chip color="grey-2" text-color="grey-7">
                  {{ precosDe(opcao)[0].titulo }}
                </q-chip>
              </div>
              <div class="text-h5">
                <small class="text-grey-7">R$</small>
                {{ formataNumero(opcao.barra.preco) }}
              </div>
              <div class="text-caption text-grey-7 ellipsis-2-lines">
                {{ opcao.barra.barras }} | {{ opcao.barra.descricao ?? opcao.barra.produto }}
              </div>
            </q-card-section>

            <q-card-section v-else>
              <div
                v-if="opcao.variacoes?.length > 1"
                class="absolute"
                style="top: 0; right: 5px; transform: translateY(-37px)"
              >
                <q-chip color="grey-2" text-color="grey-7">
                  {{ opcao.variacoes.length }} variações
                </q-chip>
              </div>
              <div
                v-for="preco in opcao.precos"
                :key="preco.chave"
                class="row no-wrap justify-between"
              >
                <span class="text-grey-7 ellipsis q-mr-sm">{{ preco.titulo }}</span>
                <span class="text-no-wrap">
                  <small class="text-grey-7">R$</small>
                  {{ formataNumero(preco.barra.preco) }}
                </span>
              </div>
              <div class="text-caption text-grey-7 ellipsis-2-lines q-mt-xs">
                {{ opcao.titulo }}
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>
  </div>
</template>
