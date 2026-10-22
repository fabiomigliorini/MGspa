<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { formataCodigo } from '@components/formatters'
import { useEstoqueLocalStore } from 'src/stores/estoqueLocalStore'
import EstoqueLocalDialog from 'components/estoque-local/EstoqueLocalDialog.vue'

// Detalhe do local de estoque (TASK-55): dados, impressora da via de separação e, na etapa
// 11, os indicadores de qualidade do estoque dele como requisitado.
const route = useRoute()
const sLocal = useEstoqueLocalStore()

watch(
  () => route.params.id,
  (id) => id && sLocal.carregarLocal(id),
  { immediate: true },
)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="sLocal.carregandoLocal && !sLocal.local" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <template v-else-if="sLocal.local">
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center no-wrap">
            <q-avatar color="brown-6" text-color="white" class="q-mr-md">
              {{ sLocal.local.sigla }}
            </q-avatar>
            <div class="col">
              <div class="text-h5 ellipsis">{{ sLocal.local.estoquelocal }}</div>
              <div class="text-caption text-grey-7">
                {{ formataCodigo(sLocal.local.codestoquelocal) }} · {{ sLocal.local.filial }}
              </div>
            </div>
            <q-badge v-if="sLocal.local.inativo" color="orange-7" class="q-mr-sm">Inativo</q-badge>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="sLocal.editar(sLocal.local)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              :icon="sLocal.local.inativo ? 'play_arrow' : 'pause'"
              @click="sLocal.alternarInativo(sLocal.local)"
            >
              <q-tooltip>{{ sLocal.local.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
          </q-card-section>
          <q-separator />
          <q-list>
            <q-item>
              <q-item-section avatar><q-icon name="print" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Impressora da via de separação</q-item-label>
                <q-item-label>{{
                  sLocal.local.impressora || 'Nenhuma (não imprime sozinho)'
                }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="inventory" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Estoque</q-item-label>
                <q-item-label>
                  {{ sLocal.local.controlaestoque ? 'Controla estoque' : 'Não controla estoque' }}
                  <template v-if="sLocal.local.deposito"> · depósito</template>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <EstoqueLocalDialog />
  </q-page>
</template>
