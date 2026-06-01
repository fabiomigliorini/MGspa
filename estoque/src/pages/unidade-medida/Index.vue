<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useUnidadeMedidaStore } from 'src/stores/unidadeMedidaStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useUnidadeMedidaStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref(novoModelo())

function novoModelo() {
  return {
    codunidademedida: null,
    unidademedida: '',
    sigla: '',
  }
}

const codFormatado = (v) => '#' + String(v).padStart(6, '0')

const abrirNovo = () => {
  isNovo.value = true
  model.value = novoModelo()
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codunidademedida: row.codunidademedida,
    unidademedida: row.unidademedida,
    sigla: row.sigla,
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const payload = () => ({
  unidademedida: model.value.unidademedida,
  sigla: model.value.sigla,
})

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/unidade-medida', payload())
    store.upsertLocal(data)
    notifySuccess('Unidade de medida criada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar unidade de medida')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/unidade-medida/${model.value.codunidademedida}`, payload())
    store.upsertLocal(data)
    notifySuccess('Unidade de medida atualizada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar unidade de medida')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/unidade-medida/${row.codunidademedida}/inativo`)
      : await api.post(`v1/unidade-medida/${row.codunidademedida}/inativo`)
    store.upsertLocal(data)
    notifySuccess(data.inativo ? 'Unidade inativada' : 'Unidade reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a unidade "${row.unidademedida}"?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/unidade-medida/${row.codunidademedida}`)
      store.removeLocal(row.codunidademedida)
      notifySuccess('Unidade excluída')
    } catch (e) {
      notifyError(e, 'Erro ao excluir unidade')
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
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="text-grey-9 text-overline row items-center">
          UNIDADES DE MEDIDA
          <q-space />
          <span class="text-caption text-grey-6">{{ store.total }} registros</span>
        </q-card-section>

        <q-infinite-scroll @load="carregarMais" :offset="250">
          <q-list>
            <template v-for="item in store.items" :key="item.codunidademedida">
              <q-separator inset />
              <q-item>
                <q-item-section avatar>
                  <q-avatar rounded size="42px" color="grey-3" text-color="grey-7">
                    {{ item.sigla }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium">
                    {{ item.unidademedida }}
                    <q-badge v-if="item.inativo" color="orange-7" class="q-ml-sm">Inativo</q-badge>
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">
                    {{ codFormatado(item.codunidademedida) }} · Sigla: {{ item.sigla }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn
                      flat
                      dense
                      round
                      size="sm"
                      color="grey-7"
                      icon="edit"
                      @click="abrirEditar(item)"
                    >
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      size="sm"
                      color="grey-7"
                      :icon="item.inativo ? 'play_arrow' : 'pause'"
                      @click="toggleInativo(item)"
                    >
                      <q-tooltip>{{ item.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      size="sm"
                      color="grey-7"
                      icon="delete"
                      @click="excluir(item)"
                    >
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </template>
          </q-list>

          <div
            v-if="store.items.length === 0 && !store.loading"
            class="text-center text-grey-6 q-pa-xl"
          >
            Nenhuma unidade de medida encontrada
          </div>

          <template #loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="32px" />
            </div>
          </template>
        </q-infinite-scroll>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="abrirNovo">
        <q-tooltip anchor="center left" self="center right">Nova Unidade</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA UNIDADE DE MEDIDA' : 'EDITAR UNIDADE DE MEDIDA' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-8">
                <q-input
                  v-model="model.unidademedida"
                  outlined
                  label="Descrição"
                  maxlength="15"
                  autofocus
                  :rules="[(v) => (!!v && v.length >= 2) || 'Mínimo 2 caracteres']"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="model.sigla"
                  outlined
                  label="Sigla"
                  maxlength="3"
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
