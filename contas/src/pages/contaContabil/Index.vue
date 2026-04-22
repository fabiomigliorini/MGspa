<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useContaContabilStore } from 'src/stores/contaContabilStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useContaContabilStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref({ codcontacontabil: null, contacontabil: '', numero: '' })

const columns = [
  {
    name: 'codcontacontabil',
    label: '#',
    field: 'codcontacontabil',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'contacontabil', label: 'Descrição', field: 'contacontabil', align: 'left' },
  { name: 'numero', label: 'Número', field: 'numero', align: 'left' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const abrirNovo = () => {
  isNovo.value = true
  model.value = { codcontacontabil: null, contacontabil: '', numero: '' }
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codcontacontabil: row.codcontacontabil,
    contacontabil: row.contacontabil,
    numero: row.numero,
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/conta-contabil', {
      contacontabil: model.value.contacontabil,
      numero: model.value.numero,
    })
    store.upsertLocal(data.data)
    notifySuccess('Conta contábil criada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar conta contábil')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/conta-contabil/${model.value.codcontacontabil}`, {
      contacontabil: model.value.contacontabil,
      numero: model.value.numero,
    })
    store.upsertLocal(data.data)
    notifySuccess('Conta contábil atualizada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar conta contábil')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/conta-contabil/${row.codcontacontabil}/inativo`)
      : await api.post(`v1/conta-contabil/${row.codcontacontabil}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Conta contábil inativada' : 'Conta contábil reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a conta contábil "${row.contacontabil}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/conta-contabil/${row.codcontacontabil}`)
      store.removeLocal(row.codcontacontabil)
      notifySuccess('Conta contábil excluída')
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
      <div class="q-pa-md" style="margin: auto; max-width: 850px">
        <q-table
          :rows="store.items"
          :columns="columns"
          row-key="codcontacontabil"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhuma conta contábil encontrada"
        >
          <template #body-cell-codcontacontabil="props">
            <q-td :props="props" class="text-grey-7">
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-contacontabil="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              style="max-width: 20vw; white-space: normal; word-break: break-word"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-inativo="props">
            <q-td :props="props">
              <q-badge v-if="props.row.inativo" color="orange-7">Inativa</q-badge>
              <q-badge v-else color="green-6">Ativa</q-badge>
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
        <q-tooltip anchor="center left" self="center right">Nova Conta Contábil</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA CONTA CONTÁBIL' : 'EDITAR CONTA CONTÁBIL' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.contacontabil"
                  outlined
                  label="Descrição"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input v-model="model.numero" outlined label="Número" maxlength="15" />
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
