<script setup>
// As maquinetas no padrão do painel dos portadores (doc-4): card por filial, agrupadas por
// adquirente, com a situação dos períodos da conferência do cartão. A linha abre a maquineta;
// editar, parear, juntar, inativar e excluir ficam no cabeçalho dela.
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataTimestamp } from '@components/formatters'
import MaquinetaDialog from 'components/maquineta/MaquinetaDialog.vue'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'
import {
  maquinetaIntegracaoLabel,
  maquinetaIntegracaoColor,
} from 'src/constants/maquinetaIntegracao'

const store = useMaquinetaStore()
const { filiais, loading } = storeToRefs(store)

const pendencias = (m) => m.periodos?.pendentes || m.periodos?.semBordero

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card v-for="f in filiais" :key="f.codfilial ?? 0" flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-weight-medium">{{ f.filial }}</q-item-label>
          </q-item-section>
        </q-item>
        <q-list v-for="a in f.adquirentes" :key="a.codpessoa ?? 0" separator>
          <q-separator />
          <q-item-label header class="q-py-sm text-weight-medium text-primary">
            {{ a.adquirente ?? 'Sem adquirente' }}
          </q-item-label>
          <q-item
            v-for="m in a.maquinetas"
            :key="m.codmaquineta"
            clickable
            :to="{ name: 'maquineta-detalhe', params: { codmaquineta: m.codmaquineta } }"
          >
            <q-item-section avatar>
              <q-icon name="contactless" :color="maquinetaIntegracaoColor(m.integracao)" />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="m.inativo ? 'text-strike text-grey-6' : ''">
                {{ m.apelido }}
                <q-badge v-if="m.periodos?.aberto" color="green-7" class="q-ml-sm" label="Aberto" />
              </q-item-label>
              <q-item-label caption>
                {{ maquinetaIntegracaoLabel(m.integracao) }}
                <template v-if="m.serial"> · {{ m.serial }}</template>
                <template v-if="m.compartilhada"> · Todas as filiais</template>
              </q-item-label>
              <q-item-label v-if="m.periodos?.aberto" caption>
                Aberto desde {{ formataTimestamp(m.periodos.aberto, 0) }}
              </q-item-label>
              <q-item-label v-if="pendencias(m)" caption>
                <q-badge
                  v-if="m.periodos.pendentes"
                  color="amber-10"
                  class="q-mr-xs"
                  :label="`${m.periodos.pendentes} período(s) pendente(s)`"
                />
                <q-badge
                  v-if="m.periodos.semBordero"
                  color="orange-8"
                  :label="`${m.periodos.semBordero} sem borderô`"
                />
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-5" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <MgEmptyState v-if="!loading && !filiais.length" icon="contactless">
        Nenhuma maquineta encontrada.
      </MgEmptyState>
    </div>

    <q-inner-loading :showing="loading" color="primary" />

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="store.abrirNovo()">
        <q-tooltip anchor="center left" self="center right">Nova maquineta</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <MaquinetaDialog />
  </q-page>
</template>
