<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useGrupoClienteStore } from 'src/stores/grupoClienteStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useGrupoClienteStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)

const emptyModel = () => ({
  codgrupocliente: null,
  grupocliente: '',
})

const model = ref(emptyModel())

const columns = [
  {
    name: 'codgrupocliente',
    label: '#',
    field: 'codgrupocliente',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'grupocliente', label: 'Grupo', field: 'grupocliente', align: 'left' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const abrirNovo = () => {
  isNovo.value = true
  model.value = emptyModel()
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codgrupocliente: row.codgrupocliente,
    grupocliente: row.grupocliente,
  }
  dialog.value = true
}

const payload = () => ({
  grupocliente: model.value.grupocliente,
})

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/grupo-cliente', payload())
    store.upsertLocal(data.data)
    notifySuccess('Grupo de cliente criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/grupo-cliente/${model.value.codgrupocliente}`, payload())
    store.upsertLocal(data.data)
    notifySuccess('Grupo de cliente atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar')
  } finally {
    saving.value = false
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir "${row.grupocliente}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/grupo-cliente/${row.codgrupocliente}`)
      store.removeLocal(row.codgrupocliente)
      notifySuccess('Grupo de cliente excluído')
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  })
}

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => {
  store.fetchItems(true)
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="codgrupocliente"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum grupo de cliente encontrado"
        >
          <template #body-cell-codgrupocliente="props">
            <q-td :props="props" class="text-grey-7">{{ props.value }}</q-td>
          </template>

          <template #body-cell-grupocliente="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <q-btn flat dense round color="primary" icon="edit" @click="abrirEditar(props.row)">
                <q-tooltip>Editar</q-tooltip>
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
        <q-tooltip anchor="center left" self="center right">Novo Grupo de Cliente</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO GRUPO DE CLIENTE' : 'EDITAR GRUPO DE CLIENTE' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.grupocliente"
                  outlined
                  label="Grupo"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
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
