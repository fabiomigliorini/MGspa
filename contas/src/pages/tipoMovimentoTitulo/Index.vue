<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useTipoMovimentoTituloStore } from 'src/stores/tipoMovimentoTituloStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useTipoMovimentoTituloStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)

const FLAGS = [
  { key: 'implantacao', label: 'Implantação' },
  { key: 'ajuste', label: 'Ajuste' },
  { key: 'armotizacao', label: 'Amortização' },
  { key: 'juros', label: 'Juros' },
  { key: 'desconto', label: 'Desconto' },
  { key: 'pagamento', label: 'Pagamento' },
  { key: 'estorno', label: 'Estorno' },
]

const emptyModel = () => ({
  codtipomovimentotitulo: null,
  tipomovimentotitulo: '',
  observacao: '',
  implantacao: false,
  ajuste: false,
  armotizacao: false,
  juros: false,
  desconto: false,
  pagamento: false,
  estorno: false,
})

const model = ref(emptyModel())

const columns = [
  {
    name: 'codtipomovimentotitulo',
    label: '#',
    field: 'codtipomovimentotitulo',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'tipomovimentotitulo', label: 'Descrição', field: 'tipomovimentotitulo', align: 'left' },
  { name: 'flags', label: 'Flags', field: 'flags', align: 'left' },
  { name: 'observacao', label: 'Observação', field: 'observacao', align: 'left' },
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
    codtipomovimentotitulo: row.codtipomovimentotitulo,
    tipomovimentotitulo: row.tipomovimentotitulo,
    observacao: row.observacao || '',
    implantacao: !!row.implantacao,
    ajuste: !!row.ajuste,
    armotizacao: !!row.armotizacao,
    juros: !!row.juros,
    desconto: !!row.desconto,
    pagamento: !!row.pagamento,
    estorno: !!row.estorno,
  }
  dialog.value = true
}

const payload = () => ({
  tipomovimentotitulo: model.value.tipomovimentotitulo,
  observacao: model.value.observacao,
  implantacao: model.value.implantacao,
  ajuste: model.value.ajuste,
  armotizacao: model.value.armotizacao,
  juros: model.value.juros,
  desconto: model.value.desconto,
  pagamento: model.value.pagamento,
  estorno: model.value.estorno,
})

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/tipo-movimento-titulo', payload())
    store.upsertLocal(data.data)
    notifySuccess('Tipo de movimento criado')
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
      `v1/tipo-movimento-titulo/${model.value.codtipomovimentotitulo}`,
      payload(),
    )
    store.upsertLocal(data.data)
    notifySuccess('Tipo de movimento atualizado')
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
      ? await api.delete(`v1/tipo-movimento-titulo/${row.codtipomovimentotitulo}/inativo`)
      : await api.post(`v1/tipo-movimento-titulo/${row.codtipomovimentotitulo}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Inativado' : 'Reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir "${row.tipomovimentotitulo}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/tipo-movimento-titulo/${row.codtipomovimentotitulo}`)
      store.removeLocal(row.codtipomovimentotitulo)
      notifySuccess('Tipo de movimento excluído')
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
          row-key="codtipomovimentotitulo"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum tipo de movimento encontrado"
        >
          <template #body-cell-codtipomovimentotitulo="props">
            <q-td :props="props" class="text-grey-7">{{ props.value }}</q-td>
          </template>

          <template #body-cell-tipomovimentotitulo="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
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
        <q-tooltip anchor="center left" self="center right">Novo Tipo de Movimento</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO TIPO DE MOVIMENTO' : 'EDITAR TIPO DE MOVIMENTO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.tipomovimentotitulo"
                  outlined
                  label="Descrição"
                  maxlength="20"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.observacao"
                  outlined
                  label="Observação"
                  maxlength="255"
                  type="textarea"
                  autogrow
                />
              </div>
              <div
                v-for="f in FLAGS"
                :key="f.key"
                class="col-6"
              >
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
