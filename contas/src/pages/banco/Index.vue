<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useBancoStore } from 'src/stores/bancoStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useBancoStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref({ codbanco: null, banco: '', sigla: '', numerobanco: null })

const columns = [
  {
    name: 'codbanco',
    label: '#',
    field: 'codbanco',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'banco', label: 'Banco', field: 'banco', align: 'left' },
  { name: 'sigla', label: 'Sigla', field: 'sigla', align: 'left' },
  { name: 'numerobanco', label: 'Nº Banco', field: 'numerobanco', align: 'right' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const abrirNovo = () => {
  isNovo.value = true
  model.value = { codbanco: null, banco: '', sigla: '', numerobanco: null }
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codbanco: row.codbanco,
    banco: row.banco,
    sigla: row.sigla,
    numerobanco: row.numerobanco,
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/banco', {
      banco: model.value.banco,
      sigla: model.value.sigla,
      numerobanco: model.value.numerobanco,
    })
    store.upsertLocal(data.data)
    notifySuccess('Banco criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar banco')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/banco/${model.value.codbanco}`, {
      banco: model.value.banco,
      sigla: model.value.sigla,
      numerobanco: model.value.numerobanco,
    })
    store.upsertLocal(data.data)
    notifySuccess('Banco atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar banco')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/banco/${row.codbanco}/inativo`)
      : await api.post(`v1/banco/${row.codbanco}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Banco inativado' : 'Banco reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o banco "${row.banco}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/banco/${row.codbanco}`)
      store.removeLocal(row.codbanco)
      notifySuccess('Banco excluído')
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  })
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
      <div class="q-pa-md">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="codbanco"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum banco encontrado"
        >
          <template #body-cell-codbanco="props">
            <q-td :props="props" class="text-grey-7">
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-banco="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-inativo="props">
            <q-td :props="props">
              <q-badge v-if="props.row.inativo" color="orange-7">Inativo</q-badge>
              <q-badge v-else color="green-6">Ativo</q-badge>
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <q-btn flat dense round color="primary" icon="edit" @click="abrirEditar(props.row)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                round
                :color="props.row.inativo ? 'green-6' : 'orange-7'"
                :icon="props.row.inativo ? 'toggle_off' : 'toggle_on'"
                @click="toggleInativo(props.row)"
              >
                <q-tooltip>{{ props.row.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn flat dense round color="negative" icon="delete" @click="excluir(props.row)">
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
      <q-btn fab icon="add" color="primary" @click="abrirNovo">
        <q-tooltip anchor="center left" self="center right">Novo Banco</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO BANCO' : 'EDITAR BANCO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.banco"
                  outlined
                  label="Banco"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-4">
                <q-input v-model="model.sigla" outlined label="Sigla" maxlength="3" />
              </div>
              <div class="col-8">
                <q-input
                  v-model.number="model.numerobanco"
                  outlined
                  type="number"
                  label="Nº Banco"
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
