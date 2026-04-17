<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { usePortadorStore } from 'src/stores/portadorStore'
import { notifySuccess, notifyError } from 'src/utils/notify'
import SelectBanco from 'src/components/select/SelectBanco.vue'
import SelectFilial from 'src/components/select/SelectFilial.vue'

const $q = useQuasar()
const store = usePortadorStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)

const emptyModel = () => ({
  codportador: null,
  portador: '',
  codbanco: null,
  codfilial: null,
  agencia: null,
  agenciadigito: null,
  conta: null,
  contadigito: null,
  convenio: null,
  carteira: null,
  carteiravariacao: null,
  pixdict: '',
  emiteboleto: false,
})

const model = ref(emptyModel())

const columns = [
  {
    name: 'codportador',
    label: '#',
    field: 'codportador',
    align: 'left',
    format: (v) => '#' + String(v).padStart(8, '0'),
  },
  { name: 'portador', label: 'Portador', field: 'portador', align: 'left' },
  { name: 'banco', label: 'Banco', field: 'banco', align: 'left' },
  { name: 'filial', label: 'Filial', field: 'filial', align: 'left' },
  { name: 'conta', label: 'Conta', field: 'conta', align: 'left' },
  { name: 'emiteboleto', label: 'Boleto', field: 'emiteboleto', align: 'center' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const formatarConta = (row) => {
  if (!row.conta && !row.agencia) return '—'
  const ag = row.agencia ? `${row.agencia}${row.agenciadigito ? '-' + row.agenciadigito : ''}` : ''
  const ct = row.conta ? `${row.conta}${row.contadigito ? '-' + row.contadigito : ''}` : ''
  return [ag, ct].filter(Boolean).join(' / ')
}

const abrirNovo = () => {
  isNovo.value = true
  model.value = emptyModel()
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codportador: row.codportador,
    portador: row.portador,
    codbanco: row.codbanco,
    codfilial: row.codfilial,
    agencia: row.agencia,
    agenciadigito: row.agenciadigito,
    conta: row.conta,
    contadigito: row.contadigito,
    convenio: row.convenio,
    carteira: row.carteira,
    carteiravariacao: row.carteiravariacao,
    pixdict: row.pixdict || '',
    emiteboleto: !!row.emiteboleto,
  }
  dialog.value = true
}

const payload = () => ({
  portador: model.value.portador,
  codbanco: model.value.codbanco,
  codfilial: model.value.codfilial,
  agencia: model.value.agencia,
  agenciadigito: model.value.agenciadigito,
  conta: model.value.conta,
  contadigito: model.value.contadigito,
  convenio: model.value.convenio,
  carteira: model.value.carteira,
  carteiravariacao: model.value.carteiravariacao,
  pixdict: model.value.pixdict || null,
  emiteboleto: model.value.emiteboleto,
})

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/portador', payload())
    store.upsertLocal(data.data)
    notifySuccess('Portador criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar portador')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/portador/${model.value.codportador}`, payload())
    store.upsertLocal(data.data)
    notifySuccess('Portador atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar portador')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/portador/${row.codportador}/inativo`)
      : await api.post(`v1/portador/${row.codportador}/inativo`)
    store.upsertLocal(data.data)
    notifySuccess(data.data.inativo ? 'Portador inativado' : 'Portador reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o portador "${row.portador}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/portador/${row.codportador}`)
      store.removeLocal(row.codportador)
      notifySuccess('Portador excluído')
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
          row-key="codportador"
          flat
          bordered
          :loading="store.loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhum portador encontrado"
        >
          <template #body-cell-codportador="props">
            <q-td :props="props" class="text-grey-7">{{ props.value }}</q-td>
          </template>

          <template #body-cell-portador="props">
            <q-td
              :props="props"
              class="text-weight-medium text-primary cursor-pointer"
              @click="abrirEditar(props.row)"
            >
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-banco="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.banco || '—' }}
            </q-td>
          </template>

          <template #body-cell-filial="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.filial || '—' }}
            </q-td>
          </template>

          <template #body-cell-conta="props">
            <q-td :props="props" class="text-grey-8">
              {{ formatarConta(props.row) }}
            </q-td>
          </template>

          <template #body-cell-emiteboleto="props">
            <q-td :props="props">
              <q-icon v-if="props.row.emiteboleto" name="check_circle" color="green-6" size="sm" />
              <q-icon v-else name="remove" color="grey-5" size="sm" />
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
        <q-tooltip anchor="center left" self="center right">Novo Portador</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog" persistent>
      <q-card bordered flat style="width: 600px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO PORTADOR' : 'EDITAR PORTADOR' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.portador"
                  outlined
                  label="Portador"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <div class="col-12 col-sm-6">
                <SelectBanco v-model="model.codbanco" outlined clearable label="Banco" />
              </div>

              <div class="col-12 col-sm-6">
                <SelectFilial v-model="model.codfilial" outlined clearable label="Filial" />
              </div>

              <div class="col-4">
                <q-input v-model.number="model.agencia" outlined type="number" label="Agência" />
              </div>
              <div class="col-2">
                <q-input
                  v-model.number="model.agenciadigito"
                  outlined
                  type="number"
                  label="Dígito"
                />
              </div>

              <div class="col-4">
                <q-input v-model.number="model.conta" outlined type="number" label="Conta" />
              </div>
              <div class="col-2">
                <q-input v-model.number="model.contadigito" outlined type="number" label="Dígito" />
              </div>

              <div class="col-12">
                <q-input v-model="model.pixdict" outlined label="Chave Pix" maxlength="77" />
              </div>

              <div class="col-12">
                <q-checkbox v-model="model.emiteboleto" label="Emite Boleto" />
              </div>

              <template v-if="model.emiteboleto">
                <div class="col-6 col-sm-4">
                  <q-input
                    v-model.number="model.convenio"
                    outlined
                    type="number"
                    label="Convênio"
                  />
                </div>
                <div class="col-6 col-sm-4">
                  <q-input
                    v-model.number="model.carteira"
                    outlined
                    type="number"
                    label="Carteira"
                  />
                </div>
                <div class="col-6 col-sm-4">
                  <q-input
                    v-model.number="model.carteiravariacao"
                    outlined
                    type="number"
                    label="Variação"
                  />
                </div>
              </template>
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
