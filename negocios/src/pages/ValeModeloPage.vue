<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuasar } from 'quasar'
import { valeModeloStore } from 'stores/valeModelo'
import { formataReal } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const $q = useQuasar()
const sVale = valeModeloStore()
const { modelos, carregando } = storeToRefs(sVale)

const colunas = [
  { name: 'favorecido', label: 'Favorecido', field: 'favorecido', align: 'left' },
  { name: 'modelo', label: 'Descrição', field: 'modelo', align: 'left' },
  { name: 'itens', label: 'Itens', field: (r) => r.itens.length, align: 'right' },
  { name: 'valorprodutos', label: 'Valor', field: 'valorprodutos', align: 'right' },
  { name: 'inativo', label: 'Situação', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const confirmarExclusao = (modelo) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o modelo "${modelo.modelo}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(() => sVale.excluir(modelo))
}

const carregarMais = async (indice, done) => {
  const temMais = await sVale.carregarMais()
  done(!temMais)
}

onMounted(() => sVale.carregar(1))
</script>

<template>
  <q-page class="bg-grey-2">
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <MgEmptyState v-if="!carregando && !modelos.length" icon="card_giftcard">
          Nenhum modelo de vale com esse filtro.
        </MgEmptyState>

        <q-table
          v-else
          :rows="modelos"
          :columns="colunas"
          row-key="codvalemodelo"
          flat
          bordered
          :loading="carregando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
        >
          <template #body-cell-favorecido="props">
            <q-td :props="props">
              <span v-if="props.value">{{ props.value }}</span>
              <span v-else class="text-grey-6">Ao portador</span>
            </q-td>
          </template>

          <template #body-cell-modelo="props">
            <q-td :props="props">
              <router-link
                :to="`/vale-modelo/${props.row.codvalemodelo}`"
                class="text-weight-medium text-primary"
                style="text-decoration: none"
              >
                {{ props.value }}
              </router-link>
            </q-td>
          </template>

          <template #body-cell-valorprodutos="props">
            <q-td :props="props">{{ formataReal(props.value) }}</q-td>
          </template>

          <template #body-cell-inativo="props">
            <q-td :props="props">
              <q-badge v-if="props.row.inativo" color="orange-7">Inativo</q-badge>
              <q-badge v-else color="green-6">Ativo</q-badge>
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <MgInfoCriacao :registro="props.row" />
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                :to="`/vale-modelo/${props.row.codvalemodelo}`"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                :icon="props.row.inativo ? 'play_arrow' : 'pause'"
                @click="sVale.alternarInativo(props.row)"
              >
                <q-tooltip>{{ props.row.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="confirmarExclusao(props.row)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/vale-modelo/novo">
        <q-tooltip anchor="center left" self="center right">Novo Modelo</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
