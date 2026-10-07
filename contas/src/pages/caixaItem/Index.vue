<script setup>
// Itens do caixa (doc-4, "Itens do caixa"): o que se controla no portador em espécie além do
// dinheiro (chips, ingressos). Contam como cédula: entram e saem pela tela do
// período do portador e são contados junto com as cédulas. A maquineta de parceiro não se conta: o
// caixa lança o borderô e a tela dela tem a conta corrente com o parceiro. Um card para a cédula e
// um para cada parceiro (as maquinetas dele), com o saldo gravado de cada item (cédula: quantidade
// e valor nos caixas; maquineta: o que devemos ao parceiro) e o total do que aparece; os inativos
// só com o toggle do card. A lista só
// navega; as ações ficam na tela do item (o Recalcular saldos refaz o gravado de todos).
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import CaixaItemDialog from 'components/caixaItem/CaixaItemDialog.vue'
import { formataNumero } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { items, loading, salvando } = storeToRefs(store)

// o toggle de inativos de cada card, pela chave do card (sem a chave, desligado)
const inativos = ref({})

const montar = (chave, titulo, modo, doCard) => {
  const linhas = inativos.value[chave] ? doCard : doCard.filter((i) => !i.inativo)
  return {
    chave,
    titulo,
    modo,
    linhas,
    total: linhas.reduce((t, i) => t + (i.saldo ?? 0), 0),
    quantidade: linhas.reduce((t, i) => t + (i.saldoquantidade ?? 0), 0),
  }
}

// a cédula primeiro; depois um card por parceiro (codpessoa), em ordem de nome
const cards = computed(() => {
  const cedulas = items.value.filter((i) => i.modo === 'C')
  const parceiros = new Map()
  items.value
    .filter((i) => i.modo === 'M')
    .forEach((i) => {
      if (!parceiros.has(i.codpessoa)) parceiros.set(i.codpessoa, { pessoa: i.pessoa, itens: [] })
      parceiros.get(i.codpessoa).itens.push(i)
    })
  return [
    ...(cedulas.length ? [montar('C', 'Cédula (chips, ingressos)', 'C', cedulas)] : []),
    ...[...parceiros.entries()]
      .sort(([, a], [, b]) => (a.pessoa ?? '').localeCompare(b.pessoa ?? ''))
      .map(([codpessoa, p]) => montar(`M${codpessoa}`, p.pessoa, 'M', p.itens)),
  ]
})

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <template v-if="items.length">
        <div class="row justify-end q-mb-sm">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="refresh"
            :loading="salvando"
            @click="store.recalcularSaldos"
          >
            <q-tooltip>Recalcular saldos</q-tooltip>
          </q-btn>
        </div>

        <div class="row q-col-gutter-md">
          <div v-for="c in cards" :key="c.chave" class="col-12 col-sm-4">
            <q-card bordered flat>
              <q-list separator>
                <q-item>
                  <q-item-section>
                    <q-item-label class="text-grey-7">{{ c.titulo }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-toggle
                      :model-value="!!inativos[c.chave]"
                      label="Inativos"
                      left-label
                      @update:model-value="(v) => (inativos[c.chave] = v)"
                    />
                  </q-item-section>
                </q-item>
                <q-item
                  v-for="row in c.linhas"
                  :key="row.codcaixaitem"
                  clickable
                  :to="{ name: 'caixa-item-detalhe', params: { codcaixaitem: row.codcaixaitem } }"
                >
                  <q-item-section>
                    <q-item-label :class="row.inativo ? 'text-strike text-grey-6' : ''">
                      {{ row.item }}
                    </q-item-label>
                    <q-item-label v-if="c.modo === 'M'" caption>{{ row.filial }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label :class="row.saldo < 0 ? 'text-red-8' : 'text-grey-9'">
                      R$ {{ formataNumero(row.saldo) }}
                    </q-item-label>
                    <q-item-label v-if="c.modo === 'C'" caption>
                      {{ row.saldoquantidade ?? 0 }} un.
                    </q-item-label>
                    <q-tooltip>
                      {{
                        c.modo === 'M' ? 'Saldo a pagar ao parceiro' : 'Em caixa, somando os caixas'
                      }}
                    </q-tooltip>
                  </q-item-section>
                </q-item>
                <q-item v-if="!c.linhas.length">
                  <q-item-section class="text-grey-7">Nenhum item ativo.</q-item-section>
                </q-item>
                <q-item v-else>
                  <q-item-section>
                    <q-item-label class="text-weight-bold">Total</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold"
                      :class="c.total < 0 ? 'text-red-8' : 'text-grey-9'"
                    >
                      R$ {{ formataNumero(c.total) }}
                    </q-item-label>
                    <q-item-label v-if="c.modo === 'C'" caption>
                      {{ c.quantidade }} un.
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>
      </template>
      <MgEmptyState v-else-if="!loading" icon="inventory_2">Nenhum item do caixa.</MgEmptyState>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="store.abrirNovo">
        <q-tooltip anchor="center left" self="center right">Novo item</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <CaixaItemDialog />
  </q-page>
</template>
