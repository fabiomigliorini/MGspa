<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useTipoTituloStore } from 'src/stores/tipoTituloStore'
import { notifySuccess, notifyError } from 'src/utils/notify'
import SelectTipoMovimentoTitulo from 'src/components/select/SelectTipoMovimentoTitulo.vue'

const $q = useQuasar()
const store = useTipoTituloStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)

const emptyModel = () => ({
  codtipotitulo: null,
  tipotitulo: '',
  observacoes: '',
  codtipomovimentotitulo: null,
  pagar: false,
  receber: false,
  debito: false,
  credito: false,
})

const model = ref(emptyModel())

const columns = [
  {
    name: 'codtipotitulo',
    label: '#',
    field: 'codtipotitulo',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'tipotitulo', label: 'Descrição', field: 'tipotitulo', align: 'left' },
  { name: 'direcao', label: 'Direção', field: 'direcao', align: 'center' },
  { name: 'natureza', label: 'Natureza', field: 'natureza', align: 'center' },
  {
    name: 'tipomovimentotitulo',
    label: 'Tipo Movimento',
    field: 'tipomovimentotitulo',
    align: 'left',
  },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
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
    codtipotitulo: row.codtipotitulo,
    tipotitulo: row.tipotitulo,
    observacoes: row.observacoes || '',
    codtipomovimentotitulo: row.codtipomovimentotitulo,
    pagar: !!row.pagar,
    receber: !!row.receber,
    debito: !!row.debito,
    credito: !!row.credito,
  }
  dialog.value = true
}

const payload = () => ({
  tipotitulo: model.value.tipotitulo,
  observacoes: model.value.observacoes,
  codtipomovimentotitulo: model.value.codtipomovimentotitulo,
  pagar: model.value.pagar,
  receber: model.value.receber,
  debito: model.value.debito,
  credito: model.value.credito,
})

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/tipo-titulo', payload())
    store.upsertLocal(data.data)
    notifySuccess('Tipo de título criado')
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
    const { data } = await api.put(`v1/tipo-titulo/${model.value.codtipotitulo}`, payload())
    store.upsertLocal(data.data)
    notifySuccess('Tipo de título atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/tipo-titulo/${row.codtipotitulo}/inativo`)
      : await api.post(`v1/tipo-titulo/${row.codtipotitulo}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Inativado' : 'Reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir "${row.tipotitulo}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/tipo-titulo/${row.codtipotitulo}`)
      store.removeLocal(row.codtipotitulo)
      notifySuccess('Tipo de título excluído')
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
          row-key="codtipotitulo"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum tipo de título encontrado"
        >
          <template #body-cell-codtipotitulo="props">
            <q-td :props="props" class="text-grey-7">{{ props.value }}</q-td>
          </template>

          <template #body-cell-tipotitulo="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-direcao="props">
            <q-td :props="props">
              <q-badge v-if="props.row.receber" color="green-6">Receber</q-badge>
              <q-badge v-if="props.row.pagar" color="orange-7">Pagar</q-badge>
            </q-td>
          </template>

          <template #body-cell-natureza="props">
            <q-td :props="props">
              <q-badge v-if="props.row.credito" color="teal-6">Crédito</q-badge>
              <q-badge v-if="props.row.debito" color="deep-orange-6">Débito</q-badge>
            </q-td>
          </template>

          <template #body-cell-tipomovimentotitulo="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.tipomovimentotitulo || '—' }}
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
              <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="abrirEditar(props.row)">
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
              <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluir(props.row)">
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
        <q-tooltip anchor="center left" self="center right">Novo Tipo de Título</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO TIPO DE TÍTULO' : 'EDITAR TIPO DE TÍTULO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.tipotitulo"
                  outlined
                  label="Descrição"
                  maxlength="20"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <div class="col-12">
                <SelectTipoMovimentoTitulo
                  v-model="model.codtipomovimentotitulo"
                  outlined
                  clearable
                  label="Tipo de Movimento"
                />
              </div>

              <div class="col-6">
                <q-checkbox v-model="model.receber" label="Receber" />
              </div>
              <div class="col-6">
                <q-checkbox v-model="model.pagar" label="Pagar" />
              </div>
              <div class="col-6">
                <q-checkbox v-model="model.credito" label="Crédito" />
              </div>
              <div class="col-6">
                <q-checkbox v-model="model.debito" label="Débito" />
              </div>

              <div class="col-12">
                <q-input
                  v-model="model.observacoes"
                  outlined
                  label="Observações"
                  maxlength="255"
                  type="textarea"
                  autogrow
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
