<script setup>
import { computed, onMounted } from 'vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo } from '@components/formatters'
import { useEstoqueLocalStore } from 'src/stores/estoqueLocalStore'
import EstoqueLocalDialog from 'components/estoque-local/EstoqueLocalDialog.vue'

// Locais de estoque (TASK-55, etapa 2): a lista só navega; editar e inativar ficam no detalhe.
const sLocal = useEstoqueLocalStore()

// ativos primeiro, depois os inativos, cada grupo pelo código
const lista = computed(() =>
  [...sLocal.locais].sort(
    (a, b) => (a.inativo ? 1 : 0) - (b.inativo ? 1 : 0) || a.codestoquelocal - b.codestoquelocal,
  ),
)

onMounted(() => sLocal.carregarLista())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <MgEmptyState v-if="!sLocal.carregando && !lista.length" icon="warehouse">
        Nenhum local de estoque.
      </MgEmptyState>

      <q-card v-else flat bordered>
        <q-list separator>
          <q-item
            v-for="l in lista"
            :key="l.codestoquelocal"
            clickable
            :to="{ name: 'estoque-local-detalhe', params: { id: l.codestoquelocal } }"
          >
            <q-item-section avatar>
              <q-avatar color="brown-6" text-color="white" font-size="14px">{{ l.sigla }}</q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis" :class="{ 'text-strike text-grey-6': l.inativo }">
                {{ l.estoquelocal }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataCodigo(l.codestoquelocal) }} · {{ l.filial }}
                <template v-if="l.deposito"> · depósito</template>
                <template v-if="!l.controlaestoque"> · não controla estoque</template>
              </q-item-label>
              <q-item-label v-if="l.impressora" caption class="ellipsis">
                <q-icon name="print" /> {{ l.impressora }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="sLocal.novo()">
        <q-tooltip anchor="center left" self="center right">Novo local de estoque</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <EstoqueLocalDialog />
  </q-page>
</template>
