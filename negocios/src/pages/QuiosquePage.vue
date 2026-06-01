<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero } from '@components/formatters'
import { quiosqueStore } from 'stores/quiosque'
import { produtoStore } from 'stores/produto'

const $q = useQuasar()
const sQuiosque = quiosqueStore()
const sProduto = produtoStore()

const alternarTelaCheia = () => $q.fullscreen.toggle()

const TEMPO_ESPERA = 60000 // 60s sem uso -> volta pra tela de espera
const TEMPO_NAO_ENCONTRADO = 8000 // limpa o "nao encontrado" mais rapido

// ---- estado de exibicao ----
const slide = ref(0)
let timerEspera = null

const produto = computed(() => sQuiosque.produto)
const detalhe = computed(() => sQuiosque.detalhe)

const imagens = computed(() => {
  const arr = (detalhe.value?.imagens || []).filter((c) => c != null)
  if (arr.length) return arr
  if (produto.value?.codimagem != null) return [produto.value.codimagem]
  return [null]
})

const embalagens = computed(() => detalhe.value?.embalagens || [])
const temEmbalagens = computed(() => embalagens.value.length > 1)

const estoquelocais = computed(() => detalhe.value?.estoquelocais || [])
const codprodutovariacaoSelecionada = computed(() => detalhe.value?.codprodutovariacao ?? null)
const variacoes = computed(() => {
  const arr = (detalhe.value?.variacoes || []).slice()
  const sel = codprodutovariacaoSelecionada.value
  if (sel != null) {
    // variacao consultada em primeiro (sort estavel mantem o resto na ordem)
    arr.sort((a, b) => {
      if (a.codprodutovariacao === sel) return -1
      if (b.codprodutovariacao === sel) return 1
      return 0
    })
  }
  return arr
})
const temEstoque = computed(() => estoquelocais.value.length > 0 && variacoes.value.length > 0)

const descricaoLinhas = computed(() => {
  const txt = detalhe.value?.descricaosite
  if (!txt) return []
  return txt
    .split('\n')
    .map((l) => l.replace(/^[\s\-•*]+/, '').trim())
    .filter((l) => l.length)
})

const trilha = computed(() => {
  const d = detalhe.value
  if (!d) return []
  return [
    d.secaoproduto?.secaoproduto,
    d.familiaproduto?.familiaproduto,
    d.grupoproduto?.grupoproduto,
    d.subgrupoproduto?.subgrupoproduto,
    d.marca?.marca,
  ].filter((x) => !!x)
})

const urlImagem = (codimagem) => sProduto.urlImagem(codimagem)

// ---- timer da tela de espera ----
const agendarEspera = (ms) => {
  if (timerEspera) clearTimeout(timerEspera)
  timerEspera = setTimeout(() => sQuiosque.limpar(), ms)
}

// ---- consulta ----
const consultar = (barras) => {
  slide.value = 0
  sQuiosque.consultar(barras)
  agendarEspera(TEMPO_ESPERA)
}

watch(
  () => produto.value?.codprodutobarra,
  () => {
    slide.value = 0
  },
)

watch(
  () => sQuiosque.semResultado,
  (v) => {
    if (v) agendarEspera(TEMPO_NAO_ENCONTRADO)
  },
)

// ---- pesquisa (reusa o dialog F1 do negocios) ----
const abrirPesquisa = () => {
  sProduto.textoPesquisa = ''
  sProduto.resultadoPesquisa = []
  sProduto.dialogPesquisa = true
}

const selecionarDoDialog = (barras) => {
  sProduto.dialogPesquisa = false
  consultar(barras)
}

// ---- leitor de codigo de barras via keydown global (sem input) ----
let buffer = ''
let ultimaTecla = 0

const onKeydown = (e) => {
  // enquanto o dialog de pesquisa estiver aberto, o campo cuida da digitacao
  if (sProduto.dialogPesquisa) {
    return
  }

  if (e.key === 'F1') {
    e.preventDefault()
    abrirPesquisa()
    return
  }

  if (e.key === 'Enter') {
    const txt = buffer.trim()
    buffer = ''
    if (txt.length) {
      consultar(txt)
    }
    return
  }

  // acumula apenas caracteres imprimiveis; reseta se houver pausa longa (tecla avulsa)
  if (e.key.length === 1) {
    const agora = performance.now()
    if (agora - ultimaTecla > 500) {
      buffer = ''
    }
    // tira o foco de botoes pra o Enter do leitor nao ser interpretado como clique
    const ativo = document.activeElement
    if (ativo && ativo !== document.body && typeof ativo.blur === 'function') {
      ativo.blur()
    }
    ultimaTecla = agora
    buffer += e.key
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  if (timerEspera) clearTimeout(timerEspera)
})
</script>

<template>
  <q-page class="column" style="background: linear-gradient(160deg, #f4f6fb 0%, #e7ecf5 100%)">
    <!-- indicador de atualizacao (discreto, canto inferior esquerdo) -->
    <q-page-sticky position="bottom-left" :offset="[18, 18]">
      <q-chip v-if="sQuiosque.atualizando" color="primary" text-color="white" class="shadow-2">
        <q-spinner class="q-mr-sm" size="18px" />
        atualizando…
      </q-chip>
    </q-page-sticky>

    <!-- controles do operador: FAB que expande os 3 botoes -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab
        icon="menu"
        active-icon="close"
        direction="up"
        color="primary"
        vertical-actions-align="right"
      >
        <q-fab-action color="white" text-color="primary" icon="search" @click="abrirPesquisa">
          <q-tooltip anchor="center left" self="center right">Pesquisar produto (F1)</q-tooltip>
        </q-fab-action>
        <q-fab-action
          color="white"
          text-color="primary"
          :icon="$q.fullscreen.isActive ? 'fullscreen_exit' : 'fullscreen'"
          @click="alternarTelaCheia"
        >
          <q-tooltip anchor="center left" self="center right">
            {{ $q.fullscreen.isActive ? 'Sair da tela cheia' : 'Tela cheia (F11)' }}
          </q-tooltip>
        </q-fab-action>
        <q-fab-action color="white" text-color="primary" icon="point_of_sale" :to="{ path: '/' }">
          <q-tooltip anchor="center left" self="center right">Voltar ao PDV</q-tooltip>
        </q-fab-action>
      </q-fab>
    </q-page-sticky>

    <!-- ===================== TELA DE ESPERA ===================== -->
    <div
      v-if="!produto && !sQuiosque.semResultado"
      class="col column flex-center text-center q-pa-xl"
    >
      <q-avatar
        size="200px"
        color="white"
        text-color="primary"
        class="shadow-5 q-mb-xl animated infinite pulse"
      >
        <q-icon name="qr_code_scanner" size="120px" />
      </q-avatar>

      <div class="text-h3 text-weight-bold text-grey-9 q-mb-md">Consulta de Preços</div>
      <div class="text-h5 text-weight-light text-grey-7">
        Passe o código de barras do produto no leitor
      </div>

      <div class="row items-center justify-center q-mt-xl q-gutter-md text-primary">
        <q-icon name="mdi-barcode-scan" size="64px" />
        <q-icon name="arrow_forward" size="48px" class="animated infinite pulse" />
        <q-icon name="point_of_sale" size="64px" />
      </div>
    </div>

    <!-- ===================== NAO ENCONTRADO ===================== -->
    <div
      v-else-if="sQuiosque.semResultado"
      class="col column flex-center text-center text-grey-7 q-pa-xl"
    >
      <q-icon name="search_off" color="grey-5" size="150px" />
      <div class="text-h4 text-weight-bold text-grey-8 q-mt-lg">Produto não localizado</div>
      <div class="text-h6 text-weight-light q-mt-sm">{{ sQuiosque.barrasConsultada }}</div>
      <div class="text-subtitle1 q-mt-lg">Passe outro produto no leitor</div>
    </div>

    <!-- ===================== PRODUTO ===================== -->
    <div v-else class="col q-pa-md">
      <div class="row q-col-gutter-md items-stretch">
        <!-- coluna FOTO (destaque, fica fixa enquanto rola) -->
        <div class="col-12 col-md-7">
          <div style="position: sticky; top: 16px">
            <q-card flat class="column rounded-borders shadow-5" style="overflow: hidden; height: 90vh">
              <!-- nome do produto acima das imagens -->
              <q-card-section class="q-pb-sm">
                <q-chip
                  v-if="produto.inativo"
                  color="negative"
                  text-color="white"
                  icon="block"
                  class="q-mb-xs q-ml-none"
                >
                  Produto Inativo
                </q-chip>
                <div class="text-h4 text-weight-bold text-grey-9">
                  {{ produto.produto }}
                </div>
                <div class="row q-gutter-md q-mt-xs text-grey-6">
                  <div class="text-subtitle2"><q-icon name="tag" /> {{ produto.codproduto }}</div>
                  <div class="text-subtitle2">
                    <q-icon name="mdi-barcode" /> {{ produto.barras }}
                  </div>
                </div>
              </q-card-section>
              <q-separator />

              <q-carousel
                v-model="slide"
                animated
                infinite
                :autoplay="imagens.length > 1 ? 4500 : false"
                transition-prev="slide-right"
                transition-next="slide-left"
                :navigation="imagens.length > 1"
                :arrows="imagens.length > 1"
                control-color="primary"
                class="col bg-white"
                style="min-height: 0"
              >
                <q-carousel-slide
                  v-for="(codimagem, idx) in imagens"
                  :key="idx"
                  :name="idx"
                  class="column flex-center q-pa-md"
                >
                  <q-img :src="urlImagem(codimagem)" fit="contain" class="full-width full-height">
                    <template v-slot:error>
                      <div class="column flex-center full-height bg-white text-grey-4">
                        <q-icon name="image_not_supported" size="96px" />
                      </div>
                    </template>
                  </q-img>
                </q-carousel-slide>
              </q-carousel>
            </q-card>
          </div>
        </div>

        <!-- coluna INFORMACOES -->
        <div class="col-12 col-md-5 column q-gutter-md">
          <!-- PRECO (destaque) + EMBALAGENS no mesmo card, full width (alinhado) -->
          <q-card flat class="rounded-borders shadow-3" style="overflow: hidden">
            <!-- preco -->
            <div class="q-pa-lg" style="background: linear-gradient(135deg, #eafaf1 0%, #d2f2e0 100%)">
              <div class="text-overline text-green-9" style="opacity: 0.7">Preço à vista</div>
              <div class="row items-baseline no-wrap">
                <div class="text-h3 text-weight-medium text-green-9 q-pr-xs">R$</div>
                <div
                  class="text-weight-bold text-green-10"
                  style="font-size: clamp(4rem, 9vw, 8rem); line-height: 0.95"
                >
                  {{ formataNumero(produto.preco) }}
                </div>
                <q-space />
                <div class="text-h5 text-weight-light text-green-9 self-center">
                  {{ produto.sigla }}
                </div>
              </div>
            </div>

            <!-- embalagens (faixa menor, logo abaixo do preco) -->
            <div
              v-if="temEmbalagens"
              class="q-px-lg q-py-md row items-center q-gutter-x-lg q-gutter-y-xs"
            >
              <div class="text-overline text-grey-6">Embalagens</div>
              <div v-for="(emb, idx) in embalagens" :key="idx" class="row items-baseline no-wrap">
                <span class="text-body2 text-grey-7 q-pr-xs">
                  {{ emb.unidademedida
                  }}<span v-if="emb.quantidade"> C/{{ formataNumero(emb.quantidade, 0) }}</span>
                </span>
                <span class="text-subtitle1 text-weight-bold text-grey-9">
                  {{ formataNumero(emb.preco) }}
                  <q-icon v-if="emb.precocalculado" name="star" color="amber-7" size="xs">
                    <q-tooltip>Preço calculado</q-tooltip>
                  </q-icon>
                </span>
              </div>
            </div>
          </q-card>

          <!-- ESTOQUE (2o mais importante, logo abaixo do preco - rolavel) -->
          <q-card v-if="temEstoque" flat class="rounded-borders shadow-2">
            <q-card-section class="q-pb-none">
              <div class="text-overline text-grey-6">Estoque</div>
            </q-card-section>
            <div class="scroll" style="max-height: 40vh">
              <q-markup-table flat wrap-cells>
                <thead>
                  <tr>
                    <th class="text-left" v-if="variacoes.length > 1">Variação</th>
                    <th
                      v-for="local in estoquelocais"
                      :key="local.codestoquelocal"
                      class="text-right"
                    >
                      {{ local.estoquelocal }}
                    </th>
                    <th class="text-right text-weight-bold">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="v in variacoes"
                    :key="v.codprodutovariacao"
                    :class="v.codprodutovariacao === codprodutovariacaoSelecionada ? 'bg-green-1' : ''"
                  >
                    <td
                      class="text-left"
                      v-if="variacoes.length > 1"
                      :class="
                        v.codprodutovariacao === codprodutovariacaoSelecionada
                          ? 'text-weight-bold text-green-9'
                          : ''
                      "
                    >
                      {{ v.variacao || '—' }}
                    </td>
                    <td
                      v-for="local in estoquelocais"
                      :key="local.codestoquelocal"
                      class="text-right"
                      :class="
                        (v.saldos[local.codestoquelocal] || 0) > 0 ? 'text-grey-9' : 'text-grey-5'
                      "
                    >
                      {{ formataNumero(v.saldos[local.codestoquelocal] || 0, 0) }}
                    </td>
                    <td
                      class="text-right text-weight-bold"
                      :class="v.saldo > 0 ? 'text-positive' : 'text-grey-5'"
                    >
                      {{ formataNumero(v.saldo, 0) }}
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>
            </div>
          </q-card>

          <!-- DESCRICAO DO SITE -->
          <q-card v-if="descricaoLinhas.length" flat class="rounded-borders shadow-2">
            <q-card-section>
              <div class="text-overline text-grey-6 q-mb-sm">Sobre o produto</div>
              <div
                v-for="(linha, i) in descricaoLinhas"
                :key="i"
                class="row no-wrap items-start q-py-xs"
              >
                <q-icon name="check_circle" color="primary" size="18px" class="q-mr-sm q-mt-xs" />
                <div class="text-body1 text-grey-8">{{ linha }}</div>
              </div>
            </q-card-section>
          </q-card>

          <!-- TRILHA / CATEGORIAS -->
          <div v-if="trilha.length" class="row items-center q-gutter-xs">
            <template v-for="(t, idx) in trilha" :key="idx">
              <q-chip color="white" text-color="grey-7" :label="t" class="shadow-1" />
              <q-icon
                v-if="idx < trilha.length - 1"
                name="chevron_right"
                color="grey-5"
                size="sm"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== DIALOG DE PESQUISA (reuso do F1) ===================== -->
    <q-dialog v-model="sProduto.dialogPesquisa" maximized>
      <q-card>
        <q-card-section class="bg-primary text-white">
          <div class="row q-col-gutter-sm">
            <q-input
              outlined
              autofocus
              v-model="sProduto.textoPesquisa"
              label="Pesquisa"
              bg-color="white"
              class="col"
              @keydown.enter.prevent="sProduto.pesquisar()"
            >
              <template v-slot:append>
                <q-btn round flat icon="close" @click="sProduto.textoPesquisa = ''">
                  <q-tooltip class="bg-accent">Limpar</q-tooltip>
                </q-btn>
                <q-btn round flat icon="search" @click="sProduto.pesquisar()">
                  <q-tooltip class="bg-accent">Pesquisar</q-tooltip>
                </q-btn>
                <q-btn round flat icon="logout" @click="sProduto.dialogPesquisa = false">
                  <q-tooltip class="bg-accent">Fechar</q-tooltip>
                </q-btn>
              </template>
            </q-input>
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
        </q-card-section>

        <q-card-section class="q-pa-none q-ma-none">
          <div class="row q-pa-md q-col-gutter-md">
            <template
              v-for="prod in sProduto.resultadoPesquisa"
              v-bind:key="prod.codprodutobarra"
            >
              <div class="col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6">
                <q-card
                  v-ripple
                  class="cursor-pointer q-hoverable"
                  @click="selecionarDoDialog(prod.barras)"
                >
                  <span class="q-focus-helper"></span>
                  <q-img ratio="1" :src="sProduto.urlImagem(prod.codimagem)" />
                  <q-card-section>
                    <div class="absolute" style="top: 0; right: 5px; transform: translateY(-37px)">
                      <q-chip color="grey-2" text-color="grey-7">
                        {{ prod.sigla }}
                        <template v-if="prod.quantidade > 0">
                          C/{{ formataNumero(prod.quantidade, 0) }}
                        </template>
                      </q-chip>
                    </div>
                    <div class="text-h5">
                      <small class="text-grey-7">R$</small>
                      {{ formataNumero(prod.preco) }}
                    </div>
                    <div class="text-caption text-grey-7">
                      {{ prod.barras }} | {{ prod.produto }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </template>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>
