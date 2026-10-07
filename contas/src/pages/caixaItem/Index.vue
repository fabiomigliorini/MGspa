<script setup>
// Itens do caixa (doc-4, "Itens do caixa"): o que se controla no portador em espécie além do
// dinheiro (chips, ingressos). Contam como cédula: entram e saem pela tela do
// período do portador e são contados junto com as cédulas. A maquineta de parceiro não se conta: o
// caixa lança o borderô e a tela dela tem a conta corrente com o parceiro. A lista só navega; as
// ações ficam na tela do item.
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import CaixaItemDialog from 'components/caixaItem/CaixaItemDialog.vue'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { items, loading } = storeToRefs(store)

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card v-if="items.length" bordered flat>
        <q-list separator>
          <q-item
            v-for="row in items"
            :key="row.codcaixaitem"
            clickable
            :to="{ name: 'caixa-item-detalhe', params: { codcaixaitem: row.codcaixaitem } }"
          >
            <q-item-section>
              <q-item-label :class="row.inativo ? 'text-strike text-grey-6' : ''">
                {{ row.item }}
              </q-item-label>
              <q-item-label v-if="row.modo === 'M'" caption>
                Maquineta de {{ row.pessoa }} · {{ row.filial }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
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
