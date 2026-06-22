<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useMoedaStore } from 'src/stores/moedaStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useMoedaStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref({ moeda: '', descricao: '', simbolo: '' })

const columns = [
  { name: 'moeda', label: 'Código', field: 'moeda', align: 'left' },
  { name: 'descricao', label: 'Descrição', field: 'descricao', align: 'left' },
  { name: 'simbolo', label: 'Símbolo', field: 'simbolo', align: 'left' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const abrirNovo = () => {
  isNovo.value = true
  model.value = { moeda: '', descricao: '', simbolo: '' }
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = { moeda: row.moeda, descricao: row.descricao, simbolo: row.simbolo }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/moeda', {
      moeda: model.value.moeda,
      descricao: model.value.descricao,
      simbolo: model.value.simbolo,
    })
    store.upsertLocal(data.data)
    notifySuccess('Moeda criada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar moeda')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    // O código (PK) não muda na edição — só descrição e símbolo.
    const { data } = await api.put(`v1/moeda/${model.value.moeda}`, {
      descricao: model.value.descricao,
      simbolo: model.value.simbolo,
    })
    store.upsertLocal(data.data)
    notifySuccess('Moeda atualizada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar moeda')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/moeda/${row.moeda}/inativo`)
      : await api.post(`v1/moeda/${row.moeda}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Moeda inativada' : 'Moeda reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a moeda "${row.descricao}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/moeda/${row.moeda}`)
      store.removeLocal(row.moeda)
      notifySuccess('Moeda excluída')
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
      <div class="q-pa-md" style="margin: auto; max-width: 1086px">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="moeda"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhuma moeda encontrada"
        >
          <template #body-cell-moeda="props">
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
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="abrirEditar(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
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
              <q-btn
                flat
                dense
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="excluir(props.row)"
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
      <q-btn fab icon="add" color="primary" @click="abrirNovo">
        <q-tooltip anchor="center left" self="center right">Nova Moeda</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA MOEDA' : 'EDITAR MOEDA' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-4">
                <q-input
                  v-model="model.moeda"
                  outlined
                  label="Código"
                  maxlength="3"
                  :disable="!isNovo"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório', (v) => (v && v.length === 3) || '3 letras']"
                  @update:model-value="(v) => (model.moeda = (v || '').toUpperCase())"
                />
              </div>
              <div class="col-8">
                <q-input
                  v-model="model.descricao"
                  outlined
                  label="Descrição"
                  maxlength="60"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.simbolo"
                  outlined
                  label="Símbolo"
                  maxlength="5"
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
