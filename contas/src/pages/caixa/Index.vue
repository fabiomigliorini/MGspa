<script setup>
// Itens do caixa (M13 doc-3): o que chips, ingressos e maquinetas de parceiros movimentaram por
// período, com os totais e os títulos de repasse, para o acerto com o parceiro. Controle à parte:
// portadores, transferências e períodos ficam na tela do portador (doc-4).
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useCaixaStore } from 'src/stores/caixaStore'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'

const store = useCaixaStore()

const opcoesItem = ref([])
onMounted(async () => {
  store.atualizar()
  try {
    const { data } = await api.get('v1/caixa-item')
    opcoesItem.value = data.data.map((i) => ({ value: i.codcaixaitem, label: i.item }))
  } catch {
    opcoesItem.value = []
  }
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm-6">
          <q-select
            v-model="store.codcaixaitem"
            :options="opcoesItem"
            label="Item"
            outlined
            emit-value
            map-options
            clearable
            @update:model-value="store.buscarItens()"
          />
        </div>
      </div>
      <q-card v-if="store.itens.totais.length" bordered flat class="q-mb-md">
        <q-list separator>
          <q-item v-for="t in store.itens.totais" :key="t.codcaixaitem">
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ t.item }}</q-item-label>
              <q-item-label caption>
                {{ t.sessoes }} período(s) · entrada {{ formataNumero(t.valorentrada) }} · saída
                {{ formataNumero(t.valorsaida) }}
                <template v-if="t.modo === 'M'">
                  · vendido {{ formataNumero(t.valorvendido) }}
                </template>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-bold">
                R$ {{ formataNumero(t.liquido) }}
              </q-item-label>
              <q-item-label caption>líquido</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
      <q-card v-if="store.itens.linhas.length" bordered flat>
        <q-list separator>
          <q-item
            v-for="l in store.itens.linhas"
            :key="l.codcaixaitemlancamento"
            :to="{
              name: 'portador-detalhe',
              params: { codportador: l.codportador, codportadorperiodo: l.codportadorperiodo },
            }"
          >
            <q-item-section>
              <q-item-label>{{ l.item }} · {{ l.portador }}</q-item-label>
              <q-item-label caption>
                {{ formataTimestamp(l.inicio, 2) }} · {{ l.filial }}
                <template v-if="!l.fim"> · aberto</template>
              </q-item-label>
              <q-item-label caption>
                <template v-if="l.modo === 'C'">
                  abertura {{ formataNumero(l.valorabertura ?? 0) }} · fechamento
                  {{ l.valorfechamento === null ? '—' : formataNumero(l.valorfechamento) }} ·
                </template>
                <template v-else-if="l.valorvendido !== null">
                  vendido {{ formataNumero(l.valorvendido) }} ·
                </template>
                entrada {{ formataNumero(l.valorentrada) }} · saída
                {{ formataNumero(l.valorsaida) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-bold">
                {{ l.liquido === null ? '—' : 'R$ ' + formataNumero(l.liquido) }}
              </q-item-label>
              <q-item-label v-if="l.titulo" caption>
                <a :href="`/titulo/${l.codtitulo}`" target="_blank" class="text-primary">{{
                  l.titulo
                }}</a>
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
      <MgEmptyState v-else-if="!store.carregandoItens" icon="inventory_2">
        Nenhum movimento de item do caixa no período.
      </MgEmptyState>
    </div>
    <q-inner-loading :showing="store.carregandoItens" color="primary" />
  </q-page>
</template>
