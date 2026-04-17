<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useFormaPagamentoStore } from 'src/stores/formaPagamentoStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useFormaPagamentoStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)

const FLAGS = [
  { key: 'avista', label: 'À Vista' },
  { key: 'boleto', label: 'Boleto' },
  { key: 'fechamento', label: 'Fechamento' },
  { key: 'notafiscal', label: 'Nota Fiscal' },
  { key: 'entrega', label: 'Entrega' },
  { key: 'valecompra', label: 'Vale Compra' },
  { key: 'pix', label: 'Pix' },
  { key: 'lio', label: 'Lio' },
  { key: 'stone', label: 'Stone' },
  { key: 'safrapay', label: 'SafraPay' },
  { key: 'integracao', label: 'Integração' },
]

const emptyModel = () => ({
  codformapagamento: null,
  formapagamento: '',
  formapagamentoecf: '',
  parcelas: null,
  diasentreparcelas: null,
  avista: false,
  boleto: false,
  fechamento: false,
  notafiscal: false,
  entrega: false,
  valecompra: false,
  lio: false,
  pix: false,
  stone: false,
  integracao: false,
  safrapay: false,
})

const model = ref(emptyModel())

const columns = [
  {
    name: 'codformapagamento',
    label: '#',
    field: 'codformapagamento',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'formapagamento', label: 'Descrição', field: 'formapagamento', align: 'left' },
  { name: 'parcelas', label: 'Parcelas', field: 'parcelas', align: 'right' },
  { name: 'flags', label: 'Flags', field: 'flags', align: 'left' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const flagsAtivos = (row) => FLAGS.filter((f) => row[f.key])

const abrirNovo = () => {
  isNovo.value = true
  model.value = emptyModel()
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codformapagamento: row.codformapagamento,
    formapagamento: row.formapagamento,
    formapagamentoecf: row.formapagamentoecf || '',
    parcelas: row.parcelas,
    diasentreparcelas: row.diasentreparcelas,
    avista: !!row.avista,
    boleto: !!row.boleto,
    fechamento: !!row.fechamento,
    notafiscal: !!row.notafiscal,
    entrega: !!row.entrega,
    valecompra: !!row.valecompra,
    lio: !!row.lio,
    pix: !!row.pix,
    stone: !!row.stone,
    integracao: !!row.integracao,
    safrapay: !!row.safrapay,
  }
  dialog.value = true
}

const payload = () => {
  const p = {
    formapagamento: model.value.formapagamento,
    formapagamentoecf: model.value.formapagamentoecf || null,
    parcelas: model.value.parcelas,
    diasentreparcelas: model.value.diasentreparcelas,
  }
  for (const f of FLAGS) p[f.key] = model.value[f.key]
  return p
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/forma-pagamento', payload())
    store.upsertLocal(data.data)
    notifySuccess('Forma de pagamento criada')
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
    const { data } = await api.put(
      `v1/forma-pagamento/${model.value.codformapagamento}`,
      payload(),
    )
    store.upsertLocal(data.data)
    notifySuccess('Forma de pagamento atualizada')
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
      ? await api.delete(`v1/forma-pagamento/${row.codformapagamento}/inativo`)
      : await api.post(`v1/forma-pagamento/${row.codformapagamento}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Inativada' : 'Reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir "${row.formapagamento}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/forma-pagamento/${row.codformapagamento}`)
      store.removeLocal(row.codformapagamento)
      notifySuccess('Forma de pagamento excluída')
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
          row-key="codformapagamento"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhuma forma de pagamento encontrada"
        >
          <template #body-cell-codformapagamento="props">
            <q-td :props="props" class="text-grey-7">{{ props.value }}</q-td>
          </template>

          <template #body-cell-formapagamento="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-parcelas="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.parcelas || '—' }}
            </q-td>
          </template>

          <template #body-cell-flags="props">
            <q-td :props="props">
              <q-badge
                v-for="f in flagsAtivos(props.row)"
                :key="f.key"
                color="blue-grey-6"
                class="q-mr-xs"
              >
                {{ f.label }}
              </q-badge>
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
        <q-tooltip anchor="center left" self="center right">Nova Forma de Pagamento</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 560px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA FORMA DE PAGAMENTO' : 'EDITAR FORMA DE PAGAMENTO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.formapagamento"
                  outlined
                  label="Descrição"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <div class="col-4">
                <q-input
                  v-model="model.formapagamentoecf"
                  outlined
                  label="Cód. ECF"
                  maxlength="5"
                />
              </div>
              <div class="col-4">
                <q-input v-model.number="model.parcelas" outlined type="number" label="Parcelas" />
              </div>
              <div class="col-4">
                <q-input
                  v-model.number="model.diasentreparcelas"
                  outlined
                  type="number"
                  label="Dias entre"
                />
              </div>

              <div v-for="f in FLAGS" :key="f.key" class="col-6 col-sm-4">
                <q-checkbox v-model="model[f.key]" :label="f.label" />
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
