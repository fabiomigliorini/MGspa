<script setup>
// Os períodos de um caixa em que o item mexeu, do mais novo (o aberto, se mexeu) para o mais
// antigo, com rolagem infinita: o que tinha na abertura, o que entrou ou saiu sem venda (com cada
// lançamento e quem fez), o contado no fechamento e a diferença (fechamento − abertura − entradas;
// negativa é o que saiu sem lançamento: vendido ou levado para outro caixa).
// Cada um leva ao período (Ctrl+clique abre noutra aba sem fechar o popup). Em cima, três cards
// com os totais do servidor (a lista é paginada): as entradas de todos os períodos (o aberto
// também), o saldo (o mesmo de "Saldo nos caixas": contado no último fechamento + entradas depois) e
// a diferença dos fechados (o aberto ainda não foi contado); entradas + diferença = saldo.
import { onBeforeUnmount, ref } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { item, fechamentosDialog, fechamentosCaixa, fechamentos, fechamentosTotais } =
  storeToRefs(store)
const rolagem = ref(null)
// em tela cheia o q-infinite-scroll mede a lista durante a animação de abrir e não pede a 1ª
// página; ao terminar de abrir, ele confere de novo
const infinito = ref(null)
// no celular (grid e visibilidade do Quasar): cada período em bloco, a data em cima e os 4 números
// embaixo, cada um com o nome; o cabeçalho da tabela só a partir do sm
const colunas = ['Abertura', 'Entradas', 'Fechamento', 'Diferença']
const corDiferenca = (v) => (v < 0 ? 'text-red-8' : v > 0 ? 'text-green-8' : '')

// navegar para o período desmonta a tela; sem isto, o popup reabriria ao voltar
onBeforeUnmount(() => (fechamentosDialog.value = false))

const carregarMais = async (index, done) => {
  await store.carregarFechamentos()
  done(!store.fechamentosTemMais)
}
</script>

<template>
  <q-dialog v-model="fechamentosDialog" :maximized="$q.screen.xs" @show="infinito?.poll()">
    <q-card
      flat
      class="column no-wrap"
      :style="$q.screen.xs ? undefined : 'width: 700px; max-width: 95vw; max-height: 90vh'"
    >
      <q-card-section class="col-auto text-grey-9 text-overline text-uppercase">
        {{ item?.item }} · {{ fechamentosCaixa?.portador }}
      </q-card-section>
      <q-separator inset />
      <q-card-section v-if="fechamentosTotais" class="col-auto row q-col-gutter-md">
        <div
          v-for="c in [
            { label: 'Saldo', v: fechamentosTotais.saldo },
            { label: 'Entradas', v: fechamentosTotais.entradas },
            { label: 'Diferença', v: fechamentosTotais.diferenca, cor: true },
          ]"
          :key="c.label"
          class="col-4"
        >
          <q-card flat bordered class="text-center q-pa-sm full-height">
            <div class="text-caption text-grey-7">{{ c.label }}</div>
            <div class="text-h6" :class="c.cor ? corDiferenca(c.v.total) : ''">
              {{ formataNumero(c.v.total) }}
            </div>
            <div class="text-caption text-grey-7">{{ c.v.quantidade }} un.</div>
          </q-card>
        </div>
      </q-card-section>
      <div ref="rolagem" class="col scroll q-px-md">
        <q-infinite-scroll
          ref="infinito"
          :scroll-target="rolagem"
          :offset="250"
          @load="carregarMais"
        >
          <q-list separator>
            <q-item v-if="fechamentos.length" class="text-caption text-grey-7 gt-xs">
              <div class="full-width">
                <div class="row items-center q-col-gutter-x-sm">
                  <div class="col">Período</div>
                  <div v-for="c in colunas" :key="c" class="col-2 text-right">{{ c }}</div>
                </div>
              </div>
            </q-item>
            <q-item
              v-for="f in fechamentos"
              :key="f.codportadorperiodo"
              clickable
              :to="{
                name: 'portador-detalhe',
                params: { codportador: f.codportador, codportadorperiodo: f.codportadorperiodo },
              }"
            >
              <div class="full-width">
                <div class="row items-center q-col-gutter-x-sm">
                  <div class="col-12 col-sm">
                    <!-- fechado: a data do fim em azul; aberto: a do início em lilás -->
                    <q-item-label v-if="f.fim" class="text-primary">
                      {{ formataTimestamp(f.fim) }}
                    </q-item-label>
                    <q-item-label v-else class="text-purple-5">
                      {{ formataTimestamp(f.inicio) }}
                    </q-item-label>
                    <q-item-label v-if="f.fechamento" caption class="gt-xs">
                      {{ f.fechamento.detalhe || 'Nenhum' }}
                    </q-item-label>
                  </div>
                  <div class="col-3 col-sm-2 text-right">
                    <q-item-label caption class="xs ellipsis">Abertura</q-item-label>
                    <q-item-label>{{ formataNumero(f.abertura.total) }}</q-item-label>
                    <q-item-label caption>{{ f.abertura.quantidade }} un.</q-item-label>
                  </div>
                  <div class="col-3 col-sm-2 text-right">
                    <q-item-label caption class="xs ellipsis">Entradas</q-item-label>
                    <q-item-label>{{ formataNumero(f.entradas.total) }}</q-item-label>
                    <q-item-label caption>{{ f.entradas.quantidade }} un.</q-item-label>
                  </div>
                  <div class="col-3 col-sm-2 text-right">
                    <q-item-label caption class="xs ellipsis">Fechamento</q-item-label>
                    <template v-if="f.fechamento">
                      <q-item-label>{{ formataNumero(f.fechamento.total) }}</q-item-label>
                      <q-item-label caption>{{ f.fechamento.quantidade }} un.</q-item-label>
                    </template>
                    <q-item-label v-else caption>em aberto</q-item-label>
                  </div>
                  <div class="col-3 col-sm-2 text-right">
                    <q-item-label caption class="xs ellipsis">Diferença</q-item-label>
                    <template v-if="f.diferenca">
                      <q-item-label :class="corDiferenca(f.diferenca.total)">
                        {{ formataNumero(f.diferenca.total) }}
                      </q-item-label>
                      <q-item-label caption>{{ f.diferenca.quantidade }} un.</q-item-label>
                    </template>
                    <q-item-label v-else caption>em aberto</q-item-label>
                  </div>
                </div>
                <q-item-label
                  v-for="l in f.lancamentos"
                  :key="l.codportadormovimento"
                  caption
                  class="q-mt-xs gt-xs"
                >
                  {{ l.valor < 0 ? 'Saída' : 'Entrada' }} em {{ formataTimestamp(l.transacao) }} ·
                  {{ l.usuariocriacao }}: {{ l.detalhe }}
                  <template v-if="l.observacoes"> · {{ l.observacoes }}</template>
                </q-item-label>
              </div>
            </q-item>
          </q-list>
          <MgEmptyState v-if="!fechamentos.length && !store.fechamentosTemMais" icon="inventory_2">
            Nenhum período com movimento do item.
          </MgEmptyState>
          <template #loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="40px" />
            </div>
          </template>
        </q-infinite-scroll>
      </div>
      <q-separator inset />
      <q-card-actions align="right" class="col-auto">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
