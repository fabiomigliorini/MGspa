<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useTipoProdutoStore } from 'src/stores/tipoProdutoStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useTipoProdutoStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref(novoModelo())

function novoModelo() {
  return {
    codtipoproduto: null,
    tipoproduto: '',
    estoque: true,
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
    codtipoproduto: row.codtipoproduto,
    tipoproduto: row.tipoproduto,
    estoque: !!row.estoque,
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const payload = () => ({
  tipoproduto: model.value.tipoproduto,
  estoque: model.value.estoque,
})

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/tipo-produto', payload())
    store.upsertLocal(data)
    notifySuccess('Tipo de produto criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar tipo de produto')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/tipo-produto/${model.value.codtipoproduto}`, payload())
    store.upsertLocal(data)
    notifySuccess('Tipo de produto atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar tipo de produto')
  } finally {
    saving.value = false
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o tipo "${row.tipoproduto}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/tipo-produto/${row.codtipoproduto}`)
      store.removeLocal(row.codtipoproduto)
      notifySuccess('Tipo de produto excluído')
    } catch (e) {
      notifyError(e, 'Erro ao excluir tipo de produto')
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
          TIPOS DE PRODUTO
          <q-space />
          <span class="text-caption text-grey-6">{{ store.total }} registros</span>
        </q-card-section>

        <q-infinite-scroll @load="carregarMais" :offset="250">
          <q-list>
            <template v-for="item in store.items" :key="item.codtipoproduto">
              <q-separator inset />
              <q-item>
                <q-item-section avatar>
                  <q-avatar
                    rounded
                    size="42px"
                    color="grey-3"
                    text-color="grey-7"
                    icon="category"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium">
                    {{ item.tipoproduto }}
                    <q-badge
                      :color="item.estoque ? 'green-6' : 'grey-5'"
                      class="q-ml-sm"
                      :label="item.estoque ? 'Controla estoque' : 'Sem estoque'"
                    />
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">
                    {{ codFormatado(item.codtipoproduto) }}
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
            Nenhum tipo de produto encontrado
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
        <q-tooltip anchor="center left" self="center right">Novo Tipo</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO TIPO DE PRODUTO' : 'EDITAR TIPO DE PRODUTO' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.tipoproduto"
                  outlined
                  label="Tipo de Produto"
                  maxlength="50"
                  autofocus
                  :rules="[(v) => (!!v && v.length >= 2) || 'Mínimo 2 caracteres']"
                />
              </div>
              <div class="col-12">
                <q-toggle v-model="model.estoque" label="Controla estoque" />
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
