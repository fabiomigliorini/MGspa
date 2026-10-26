<script setup>
import { computed, nextTick, onMounted, watch } from 'vue'
import { onBeforeRouteLeave, useRoute } from 'vue-router'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumeroInteligente, formataReal } from '@components/formatters'
import GeradorRascunho from 'components/requisicao/GeradorRascunho.vue'
import GeradorFiltros from 'components/requisicao/GeradorFiltros.vue'
import { requisicaoGeradorStore, COR_SITUACAO } from 'stores/requisicaoGerador'

// Gerador de requisição (TASK-258): os produtos da marca (sem marca no endereço, de todas),
// um por linha, com "3 de 8 · faltam 46 UN · R$ 380" e as variações que contam em cor. A
// pesquisa é a do F1 (barras, referência, variação...). O voltar da tela do produto devolve
// a lista como estava: pesquisa, páginas carregadas e posição.
const route = useRoute()
const sGerador = requisicaoGeradorStore()

const codestoquelocal = computed(() => Number(route.params.codestoquelocal))
const codmarca = computed(() => Number(route.params.codmarca) || null)
const marca = computed(() =>
  codmarca.value
    ? sGerador.marcas.find((m) => m.codmarca === codmarca.value)?.marca || 'Marca'
    : 'Todas as marcas',
)

const legenda = (p) =>
  [
    p.abc,
    `${p.abaixo} de ${p.trabalha}`,
    p.faltam > 0 && `faltam ${formataNumeroInteligente(p.faltam)} ${p.sigla}`,
    formataReal(p.furo),
  ]
    .filter(Boolean)
    .join(' · ')

const variacoesQueContam = (p) => p.variacoes.filter((v) => v.situacao !== 'ok')

// Quem carrega é o q-infinite-scroll: lista nova (ou pesquisa nova) troca a chave dele, que
// recomeça. A mesma lista de antes volta na posição em que estava.
function abrir() {
  sGerador.enderecoLista = route.fullPath
  if (!sGerador.abrirLista(codmarca.value)) {
    nextTick(() => window.scrollTo(0, sGerador.lista.scroll))
  }
}

const pesquisar = () => sGerador.recomecarLista()

async function carregarMais(_indice, done) {
  await sGerador.carregarProdutos(codmarca.value)
  done(!sGerador.lista.temMais)
}

watch(() => [sGerador.abc, sGerador.situacao, sGerador.ordem], abrir)

onBeforeRouteLeave(() => {
  sGerador.lista.scroll = window.scrollY
})

onMounted(async () => {
  await sGerador.carregarLojas()
  if (codmarca.value && !sGerador.marcas.length) sGerador.carregarMarcas()
  abrir()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card flat bordered>
        <q-card-section class="row items-center q-pb-none">
          <div class="col">
            <div class="text-grey-9 text-overline">
              Reposição do {{ sGerador.nomeLoja(codestoquelocal) }}
            </div>
            <div class="text-h6 text-weight-regular">{{ marca }}: qual produto?</div>
          </div>
          <GeradorRascunho />
        </q-card-section>
        <q-card-section>
          <GeradorFiltros
            v-model:busca="sGerador.lista.busca"
            rotulo-busca="Pesquisar produto (descrição, barras, referência)"
            @pesquisar="pesquisar"
          />
        </q-card-section>

        <q-infinite-scroll
          :key="`${sGerador.lista.chave}|${sGerador.lista.versao}`"
          :offset="300"
          @load="carregarMais"
        >
          <q-list separator>
            <q-item
              v-for="p in sGerador.lista.produtos"
              :key="p.codproduto"
              clickable
              :to="`/requisicao/gerador/${codestoquelocal}/produto/${p.codproduto}`"
            >
              <q-item-section>
                <q-item-label>{{ p.produto }}</q-item-label>
                <q-item-label caption :class="p.abaixo ? 'text-orange-9' : ''">
                  {{ legenda(p) }}
                </q-item-label>
                <q-item-label v-if="variacoesQueContam(p).length" caption class="ellipsis">
                  <span
                    v-for="(v, i) in variacoesQueContam(p)"
                    :key="v.codprodutovariacao"
                    :class="`text-${COR_SITUACAO[v.situacao]}`"
                    >{{ i ? ', ' : '' }}{{ v.variacao || 'sem variação' }}</span
                  >
                </q-item-label>
              </q-item-section>
              <q-item-section v-if="sGerador.pedidoDoProduto[p.codproduto]" side>
                <q-item-label class="text-primary">
                  <q-icon name="shopping_basket" />
                  {{ formataNumeroInteligente(sGerador.pedidoDoProduto[p.codproduto]) }}
                  {{ p.sigla }}
                </q-item-label>
                <q-item-label caption>no rascunho</q-item-label>
              </q-item-section>
              <q-item-section side><q-icon name="chevron_right" /></q-item-section>
            </q-item>
          </q-list>
          <template #loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="40px" />
            </div>
          </template>
        </q-infinite-scroll>

        <MgEmptyState
          v-if="!sGerador.carregandoProdutos && !sGerador.lista.produtos.length"
          icon="inventory_2"
          plain
          class="q-ma-md"
        >
          Nenhum produto nestes filtros.
        </MgEmptyState>
      </q-card>
    </div>
  </q-page>
</template>
