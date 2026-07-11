<script setup>
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useChequeRepasseStore } from 'src/stores/chequeRepasseStore'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataData, formataCodigo } from '@components/formatters'

const $q = useQuasar()
const store = useChequeRepasseStore()

const columns = [
  { name: 'codchequerepasse', label: '#', field: 'codchequerepasse', align: 'left' },
  { name: 'data', label: 'Data', field: 'data', align: 'center' },
  { name: 'portador', label: 'Portador', field: 'portador', align: 'left' },
  { name: 'cheques', label: 'Cheques', field: 'cheque_repasse_cheque_s_count', align: 'right' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const toggleInativo = (row) => {
  if (row.inativo) {
    reativar(row)
    return
  }
  $q.dialog({
    title: 'Inativar repasse',
    message:
      'Inativar este repasse devolve os cheques ainda "Repassados" para "À Repassar". Confirma?',
    ok: { label: 'Confirmar', color: 'primary', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(() => inativar(row))
}

const inativar = async (row) => {
  try {
    const { data } = await api.post(`v1/cheque-repasse/${row.codchequerepasse}/inativo`)
    store.upsertLocal(data)
    notifySuccess('Repasse inativado')
  } catch (e) {
    notifyError(e, 'Erro ao inativar repasse')
  }
}

const reativar = async (row) => {
  try {
    const { data } = await api.delete(`v1/cheque-repasse/${row.codchequerepasse}/inativo`)
    store.upsertLocal(data)
    notifySuccess('Repasse reativado')
  } catch (e) {
    notifyError(e, 'Erro ao reativar repasse')
  }
}

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => store.fetchItems(true))
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="margin: auto; max-width: 1086px">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="codchequerepasse"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum repasse encontrado"
        >
          <template #body-cell-codchequerepasse="props">
            <q-td :props="props" class="text-grey-7">
              <router-link
                :to="{
                  name: 'cheque-repasse-detalhe',
                  params: { codchequerepasse: props.row.codchequerepasse },
                }"
                class="text-primary"
              >
                {{ formataCodigo(props.row.codchequerepasse) }}
              </router-link>
            </q-td>
          </template>

          <template #body-cell-data="props">
            <q-td :props="props" class="text-weight-medium">
              {{ formataData(props.row.data) }}
            </q-td>
          </template>

          <template #body-cell-portador="props">
            <q-td :props="props">{{ props.row.portador?.portador }}</q-td>
          </template>

          <template #body-cell-inativo="props">
            <q-td :props="props">
              <q-badge v-if="props.row.inativo" color="orange-7">Inativo</q-badge>
              <q-badge v-else color="green-6">Ativo</q-badge>
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="visibility"
                :to="{
                  name: 'cheque-repasse-detalhe',
                  params: { codchequerepasse: props.row.codchequerepasse },
                }"
              >
                <q-tooltip>Ver</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                :icon="props.row.inativo ? 'play_arrow' : 'pause'"
                @click="toggleInativo(props.row)"
              >
                <q-tooltip>{{ props.row.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
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
      <q-btn fab icon="add" color="primary" :to="{ name: 'cheque-repasse-novo' }">
        <q-tooltip anchor="center left" self="center right">Novo Repasse</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
