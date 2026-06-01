<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useMarcaStore } from 'src/stores/marcaStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const store = useMarcaStore()

const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const model = ref(novoModelo())

function novoModelo() {
  return {
    codmarca: null,
    marca: '',
    site: false,
    abcignorar: false,
    controlada: false,
    descricaosite: '',
  }
}

const codFormatado = (v) => '#' + String(v).padStart(6, '0')

const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

const formataData = (v) => (v ? new Date(v).toLocaleDateString('pt-BR') : '—')

const abrirNovo = () => {
  isNovo.value = true
  model.value = novoModelo()
  dialog.value = true
}

const abrirEditar = (row) => {
  isNovo.value = false
  model.value = {
    codmarca: row.codmarca,
    marca: row.marca,
    site: !!row.site,
    abcignorar: !!row.abcignorar,
    controlada: !!row.controlada,
    descricaosite: row.descricaosite || '',
  }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const payload = () => ({
  marca: model.value.marca,
  site: model.value.site,
  abcignorar: model.value.abcignorar,
  controlada: model.value.controlada,
  descricaosite: model.value.descricaosite,
})

const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/marca', payload())
    store.upsertLocal(data)
    notifySuccess('Marca criada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar marca')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/marca/${model.value.codmarca}`, payload())
    store.upsertLocal(data)
    notifySuccess('Marca atualizada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar marca')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (row) => {
  try {
    const { data } = row.inativo
      ? await api.delete(`v1/marca/${row.codmarca}/inativo`)
      : await api.post(`v1/marca/${row.codmarca}/inativo`)
    store.upsertLocal(data)
    notifySuccess(data.inativo ? 'Marca inativada' : 'Marca reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a marca "${row.marca}"?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/marca/${row.codmarca}`)
      store.removeLocal(row.codmarca)
      notifySuccess('Marca excluída')
    } catch (e) {
      notifyError(e, 'Erro ao excluir marca')
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
      <div class="q-pa-md" style="margin: auto; max-width: 1280px">
        <div
          v-if="store.items.length === 0 && !store.loading"
          class="text-center text-grey-6 q-pa-xl"
        >
          Nenhuma marca encontrada
        </div>

        <div class="row q-col-gutter-md">
          <div
            v-for="item in store.items"
            :key="item.codmarca"
            class="col-xs-12 col-sm-6 col-md-4 col-lg-3"
          >
            <q-card bordered flat class="full-height column">
              <q-item
                clickable
                :to="{ name: 'marca-detalhe', params: { id: item.codmarca } }"
                class="q-pt-md"
              >
                <q-item-section avatar>
                  <q-avatar rounded size="56px" color="grey-3" text-color="grey-7">
                    <img v-if="item.imagem && item.imagem.url" :src="item.imagem.url" />
                    <q-icon v-else name="sell" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium ellipsis">
                    {{ item.marca }}
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">
                    {{ codFormatado(item.codmarca) }}
                  </q-item-label>
                  <q-rating
                    :model-value="item.abccategoria || 0"
                    max="3"
                    size="16px"
                    color="amber-7"
                    icon="star_border"
                    icon-selected="star"
                    readonly
                    class="q-mt-xs"
                  />
                </q-item-section>
                <q-item-section side top>
                  <q-badge v-if="item.inativo" color="orange-7">Inativo</q-badge>
                </q-item-section>
              </q-item>

              <q-separator inset />

              <q-card-section class="q-py-sm col">
                <div class="row items-center text-caption text-grey-7 q-gutter-x-md">
                  <div v-if="item.faltando || item.itensabaixominimo" class="text-red-6">
                    <q-icon name="trending_down" /> {{ item.itensabaixominimo || 0 }} faltando
                  </div>
                  <div v-if="item.sobrando || item.itensacimamaximo" class="text-orange-8">
                    <q-icon name="trending_up" /> {{ item.itensacimamaximo || 0 }} sobrando
                  </div>
                </div>
                <div class="text-caption text-grey-6 q-mt-xs">
                  Venda/ano: {{ formataMoeda(item.vendaanovalor) }}
                  <span v-if="item.vendaanopercentual">
                    ({{ Number(item.vendaanopercentual).toFixed(1) }}%)
                  </span>
                </div>
                <div class="text-caption text-grey-6">
                  Última compra: {{ formataData(item.dataultimacompra) }}
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions align="right">
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
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="abrirNovo">
        <q-tooltip anchor="center left" self="center right">Nova Marca</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA MARCA' : 'EDITAR MARCA' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.marca"
                  outlined
                  label="Marca"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => (!!v && v.length >= 2) || 'Mínimo 2 caracteres']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.descricaosite"
                  outlined
                  type="textarea"
                  autogrow
                  label="Descrição para o site"
                />
              </div>
              <div class="col-12">
                <q-toggle v-model="model.site" label="Disponível no site" />
              </div>
              <div class="col-12">
                <q-toggle v-model="model.controlada" label="Marca controlada" />
              </div>
              <div class="col-12">
                <q-toggle v-model="model.abcignorar" label="Ignorar na curva ABC" />
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
