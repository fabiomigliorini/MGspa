<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useChequeMotivoDevolucaoStore } from 'src/stores/chequeMotivoDevolucaoStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useChequeMotivoDevolucaoStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref({ codchequemotivodevolucao: null, numero: null, chequemotivodevolucao: '' })

const columns = [
  {
    name: 'codchequemotivodevolucao',
    label: '#',
    field: 'codchequemotivodevolucao',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'numero', label: 'Número', field: 'numero', align: 'right' },
  {
    name: 'chequemotivodevolucao',
    label: 'Descrição',
    field: 'chequemotivodevolucao',
    align: 'left',
  },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const abrirNovo = () => {
  isNovo.value = true
  model.value = { codchequemotivodevolucao: null, numero: null, chequemotivodevolucao: '' }
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codchequemotivodevolucao: row.codchequemotivodevolucao,
    numero: row.numero,
    chequemotivodevolucao: row.chequemotivodevolucao,
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/cheque-motivo-devolucao', {
      numero: model.value.numero,
      chequemotivodevolucao: model.value.chequemotivodevolucao,
    })
    store.upsertLocal(data)
    notifySuccess('Motivo de devolução criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar motivo de devolução')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(
      `v1/cheque-motivo-devolucao/${model.value.codchequemotivodevolucao}`,
      {
        numero: model.value.numero,
        chequemotivodevolucao: model.value.chequemotivodevolucao,
      },
    )
    store.upsertLocal(data)
    notifySuccess('Motivo de devolução atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar motivo de devolução')
  } finally {
    saving.value = false
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o motivo "${row.numero} - ${row.chequemotivodevolucao}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/cheque-motivo-devolucao/${row.codchequemotivodevolucao}`)
      store.removeLocal(row.codchequemotivodevolucao)
      notifySuccess('Motivo de devolução excluído')
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
          row-key="codchequemotivodevolucao"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum motivo de devolução encontrado"
        >
          <template #body-cell-codchequemotivodevolucao="props">
            <q-td :props="props" class="text-grey-7">
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-chequemotivodevolucao="props">
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
        <q-tooltip anchor="center left" self="center right">Novo Motivo</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO MOTIVO DE DEVOLUÇÃO' : 'EDITAR MOTIVO DE DEVOLUÇÃO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-4">
                <q-input
                  v-model.number="model.numero"
                  outlined
                  type="number"
                  label="Número"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.chequemotivodevolucao"
                  outlined
                  label="Descrição"
                  maxlength="200"
                  :rules="[
                    (v) => !!v || 'Obrigatório',
                    (v) => (v && v.length >= 5) || 'Mínimo 5 caracteres',
                  ]"
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
