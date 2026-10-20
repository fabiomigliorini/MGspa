<script setup>
import { ref, shallowRef, computed, watch, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero } from '@components/formatters'

// Grade de cards da pesquisa de produto, comum à pesquisa do PDV (offline) e à
// das telas online (TASK-173). Recebe o resultado da pesquisa, uma linha por
// código de barras, e mostra UM card por produto. Escolhido o produto, abre
// uma janela com as variações à esquerda e as embalagens dela à direita, com
// o preço; a direita acompanha a variação em que o cursor está. A embalagem é
// sigla + quantidade: duas barras da mesma embalagem aparecem uma vez, e o que
// sai é a primeira delas -- o preço é o mesmo. Só entra o que veio na lista:
// quem pesquisou "verde" não vê a azul. Produto de uma opção só sai direto da
// grade, sem janela.
//
// Teclado na grade: o foco fica no campo de pesquisa de quem usa a grade, e o
// keydown dele vem para teclar(). Para baixo entra na grade, as setas andam,
// Enter abre o produto, Esc volta ao campo. teclar() devolve false para o que
// não é dela: o Enter sem card em destaque (pesquisar) e o Esc já no campo
// (fechar a pesquisa). Na janela do produto o teclado é dela: setas sobem e
// descem na coluna, Enter na variação passa para as embalagens, Enter na
// embalagem escolhe, Esc volta para as variações e, delas, fecha a janela.
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

// produto -> variação -> embalagem. Em cada nível, `barra` é a linha que o
// representa (a foto; na embalagem, o que sai). `precos` do produto são as
// embalagens de todas as variações dele, uma por linha no card.
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
        return { ...variacao, titulo: variacao.titulo || produto.titulo, embalagens }
      })
    const precos = new Map()
    variacoes.forEach((v) =>
      v.embalagens.forEach((e) => precos.has(e.chave) || precos.set(e.chave, e)),
    )
    return { ...produto, variacoes, precos: [...precos.values()].sort(porQuantidade) }
  })
})

// card de uma opção só (uma variação e uma embalagem): sai direto da grade, e
// o card mostra preço, embalagem e barras, como era o card de cada código de
// barras
const unica = (produto) => produto.precos.length === 1 && produto.variacoes.length === 1

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

const focar = (indice) => {
  const total = produtos.value.length
  if (!total) return
  focado.value = Math.max(0, Math.min(indice, total - 1))
  nextTick(() => cardRefs.value[focado.value]?.scrollIntoView({ block: 'nearest' }))
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
const mexeu = (evento) => {
  const posicao = `${evento.screenX},${evento.screenY}`
  const parado = ultimaPosicaoMouse === null || posicao === ultimaPosicaoMouse
  ultimaPosicaoMouse = posicao
  return !parado
}

const aoMoverMouse = (evento, indice) => {
  if (mexeu(evento)) focado.value = indice
}

// ---- janela do produto: variações à esquerda, embalagens à direita ----
const dialogProduto = ref(false)
const produtoAberto = shallowRef(null)
// coluna em que está o cursor: 'variacao' ou 'embalagem'
const coluna = ref('variacao')
const cursorVariacao = ref(0)
const cursorEmbalagem = ref(0)
const variacaoAtual = computed(() => produtoAberto.value?.variacoes[cursorVariacao.value])
const umaVariacao = computed(() => produtoAberto.value?.variacoes.length === 1)

const refsVariacao = []
const refsEmbalagem = []
const colunaEmbalagem = ref(null)
const secaoProduto = ref(null)
const rolar = (refs, indice) =>
  nextTick(() => refs[indice]?.$el?.scrollIntoView({ block: 'nearest' }))

const escolher = (embalagem) => {
  dialogProduto.value = false
  emit('select', embalagem.barra)
}

const abrirProduto = (produto) => {
  if (unica(produto)) {
    escolher(produto.precos[0])
    return
  }
  produtoAberto.value = produto
  cursorVariacao.value = 0
  cursorEmbalagem.value = 0
  // de uma variação só, a esquerda mostra só ela e o cursor já começa na direita
  coluna.value = produto.variacoes.length > 1 ? 'variacao' : 'embalagem'
  ultimaPosicaoMouse = null
  dialogProduto.value = true
}

const moverVariacao = (indice) => {
  const total = produtoAberto.value.variacoes.length
  const novo = Math.max(0, Math.min(indice, total - 1))
  if (novo !== cursorVariacao.value) cursorEmbalagem.value = 0
  cursorVariacao.value = novo
  rolar(refsVariacao, novo)
}

const moverEmbalagem = (indice) => {
  const total = variacaoAtual.value.embalagens.length
  cursorEmbalagem.value = Math.max(0, Math.min(indice, total - 1))
  rolar(refsEmbalagem, cursorEmbalagem.value)
}

// No celular as embalagens ficam embaixo das variações: quem escolheu uma
// variação lá no fim da lista não as veria sem rolar. Rola a seção até o fim,
// com a margem de baixo, para a última embalagem não ficar colada na borda; e
// na volta, até a variação. No desktop, com as colunas lado a lado, o fim da
// seção pode ser o fim das variações -- ali só se garante que as embalagens
// estejam à vista, e se já estão nada se mexe.
const irParaEmbalagens = () => {
  coluna.value = 'embalagem'
  cursorEmbalagem.value = 0
  nextTick(() => {
    const secao = secaoProduto.value?.$el
    if ($q.screen.xs) secao?.scrollTo({ top: secao.scrollHeight, behavior: 'smooth' })
    else colunaEmbalagem.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  })
}

const voltarParaVariacoes = () => {
  coluna.value = 'variacao'
  rolar(refsVariacao, cursorVariacao.value)
}

// Enter (ou clique) na variação: com uma embalagem só já escolhe; senão o
// cursor passa para as embalagens
const entrarVariacao = () => {
  const embalagens = variacaoAtual.value.embalagens
  if (embalagens.length === 1) {
    escolher(embalagens[0])
    return
  }
  irParaEmbalagens()
}

const clicarVariacao = (indice) => {
  moverVariacao(indice)
  entrarVariacao()
}

const teclarProduto = (evento) => {
  const naEmbalagem = coluna.value === 'embalagem'
  switch (evento.key) {
    case 'ArrowDown':
    case 'ArrowUp': {
      const passo = evento.key === 'ArrowDown' ? 1 : -1
      if (naEmbalagem) moverEmbalagem(cursorEmbalagem.value + passo)
      else moverVariacao(cursorVariacao.value + passo)
      break
    }
    case 'ArrowRight':
      if (naEmbalagem) return
      irParaEmbalagens()
      break
    case 'ArrowLeft':
      if (!naEmbalagem || umaVariacao.value) return
      voltarParaVariacoes()
      break
    case 'Enter':
      if (naEmbalagem) escolher(variacaoAtual.value.embalagens[cursorEmbalagem.value])
      else entrarVariacao()
      break
    case 'Escape':
      // das variações (ou quando só há uma) o Esc segue e o q-dialog fecha;
      // das embalagens volta para as variações, e o stopPropagation é o que
      // impede o q-dialog de fechar junto -- ele escuta o Esc lá no window
      if (!naEmbalagem || umaVariacao.value) return
      evento.stopPropagation()
      voltarParaVariacoes()
      break
    default:
      return
  }
  evento.preventDefault()
}

const aoMoverVariacao = (evento, indice) => {
  if (!mexeu(evento)) return
  coluna.value = 'variacao'
  if (indice !== cursorVariacao.value) cursorEmbalagem.value = 0
  cursorVariacao.value = indice
}

const aoMoverEmbalagem = (evento, indice) => {
  if (!mexeu(evento)) return
  coluna.value = 'embalagem'
  cursorEmbalagem.value = indice
}

const teclar = (evento) => {
  const total = produtos.value.length
  const atual = focado.value

  // O stopPropagation é o que impede o q-dialog de fechar junto -- ele escuta
  // o Esc lá no window.
  if (evento.key === 'Escape') {
    if (atual < 0) return false
    evento.preventDefault()
    evento.stopPropagation()
    focado.value = -1
    return true
  }
  if (evento.key === 'Enter') {
    if (atual < 0 || !produtos.value[atual]) return false
    evento.preventDefault()
    abrirProduto(produtos.value[atual])
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

// Lista nova (outra pesquisa) recomeça sem destaque. A página seguinte do
// infinite scroll entra na MESMA lista e não passa por aqui.
watch(
  () => props.barras,
  () => {
    dialogProduto.value = false
    cardRefs.value = []
    focado.value = -1
  },
)

// aberto: com a janela do produto aberta o foco é dela, quem usa a grade não
// pode puxá-lo de volta para o campo de pesquisa
defineExpose({ teclar, soltar, aberto: dialogProduto })
</script>

<template>
  <div>
    <q-infinite-scroll
      :disable="!temMais"
      :offset="250"
      @load="(indice, done) => emit('carregar', done)"
    >
      <div class="row q-pa-md q-col-gutter-md">
        <div
          v-for="(produto, i) in produtos"
          :key="produto.chave"
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
            @click="abrirProduto(produto)"
          >
            <q-img ratio="1" :src="imagem(produto.barra)" />

            <q-card-section v-if="unica(produto)">
              <div class="absolute" style="top: 0; right: 5px; transform: translateY(-37px)">
                <q-chip color="grey-2" text-color="grey-7">
                  {{ produto.precos[0].titulo }}
                </q-chip>
              </div>
              <div class="text-h5">
                <small class="text-grey-7">R$</small>
                {{ formataNumero(produto.barra.preco) }}
              </div>
              <div class="text-caption text-grey-7 ellipsis-2-lines">
                {{ produto.barra.barras }} | {{ produto.barra.descricao ?? produto.barra.produto }}
              </div>
            </q-card-section>

            <q-card-section v-else>
              <div
                v-if="produto.variacoes.length > 1"
                class="absolute"
                style="top: 0; right: 5px; transform: translateY(-37px)"
              >
                <q-chip color="grey-2" text-color="grey-7">
                  {{ produto.variacoes.length }} variações
                </q-chip>
              </div>
              <div
                v-for="preco in produto.precos"
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
                {{ produto.titulo }}
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

    <!-- Janela do produto. O foco fica no card (autofocus + tabindex): os itens
         não pegam o foco no clique (mousedown.prevent), senão o Enter, que o
         q-item clickable transforma em clique, cairia no item clicado por
         último e não no do cursor. -->
    <q-dialog v-model="dialogProduto">
      <q-card
        v-if="produtoAberto"
        flat
        autofocus
        tabindex="-1"
        class="no-outline"
        style="width: 600px; max-width: 95vw"
        @keydown="teclarProduto"
      >
        <q-card-section class="text-h6">{{ produtoAberto.titulo }}</q-card-section>
        <!-- rola a seção, não o card: a barra nativa do card cobre o canto arredondado -->
        <q-card-section ref="secaoProduto" class="q-pt-none scroll" style="max-height: 70vh">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <div class="text-overline text-grey-7">Variação</div>
              <q-list>
                <q-item
                  v-for="(variacao, i) in produtoAberto.variacoes"
                  :key="variacao.chave"
                  :ref="(el) => (refsVariacao[i] = el)"
                  clickable
                  tabindex="-1"
                  class="rounded-borders"
                  :active="i === cursorVariacao"
                  :active-class="coluna === 'variacao' ? 'bg-blue-1' : 'bg-grey-3'"
                  @mousedown.prevent
                  @mousemove="aoMoverVariacao($event, i)"
                  @click="clicarVariacao(i)"
                >
                  <q-item-section avatar>
                    <q-avatar rounded size="56px">
                      <img :src="imagem(variacao.barra)" />
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>{{ variacao.titulo }}</q-item-section>
                </q-item>
              </q-list>
            </div>
            <div ref="colunaEmbalagem" class="col-12 col-sm-6">
              <div class="text-overline text-grey-7">Embalagem</div>
              <q-list>
                <q-item
                  v-for="(embalagem, i) in variacaoAtual.embalagens"
                  :key="embalagem.chave"
                  :ref="(el) => (refsEmbalagem[i] = el)"
                  clickable
                  tabindex="-1"
                  class="rounded-borders"
                  :active="coluna === 'embalagem' && i === cursorEmbalagem"
                  active-class="bg-blue-1"
                  @mousedown.prevent
                  @mousemove="aoMoverEmbalagem($event, i)"
                  @click="escolher(embalagem)"
                >
                  <q-item-section>
                    <q-item-label>{{ embalagem.titulo }}</q-item-label>
                    <q-item-label caption>{{ embalagem.barra.barras }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label class="text-subtitle1 text-grey-9">
                      <small class="text-grey-7">R$</small>
                      {{ formataNumero(embalagem.barra.preco) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>
