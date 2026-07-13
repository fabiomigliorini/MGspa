<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useUnidadeReferenciaStore } from 'src/stores/unidadeReferenciaStore'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataMesAno } from '@components/formatters'

const store = useUnidadeReferenciaStore()

const enteLabel = { FEDERAL: 'Federal', ESTADUAL: 'Estadual', MUNICIPAL: 'Municipal' }
const enteOptions = [
  { label: 'Federal', value: 'FEDERAL' },
  { label: 'Estadual', value: 'ESTADUAL' },
  { label: 'Municipal', value: 'MUNICIPAL' },
]

// ---------- Nova unidade (criar) ----------
// Editar/inativar/excluir ficam no detalhe (a lista só navega).
const dialog = ref(false)
const saving = ref(false)
const model = ref({ codigo: '', descricao: '', ente: 'ESTADUAL' })

const abrirNovo = () => {
  model.value = { codigo: '', descricao: '', ente: 'ESTADUAL' }
  dialog.value = true
}
const criar = async () => {
  saving.value = true
  try {
    const { data } = await api.post('v1/unidade-referencia', {
      codigo: model.value.codigo,
      descricao: model.value.descricao,
      ente: model.value.ente,
    })
    store.upsertLocal(data.data)
    notifySuccess('Unidade criada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar unidade')
  } finally {
    saving.value = false
  }
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
        <q-list v-if="store.items.length > 0" bordered separator class="bg-white rounded-borders">
          <q-item
            v-for="u in store.items"
            :key="u.codunidadereferencia"
            clickable
            :to="{
              name: 'unidade-referencia-detalhe',
              params: { codunidadereferencia: u.codunidadereferencia },
            }"
            :class="{ 'bg-grey-2': u.inativo }"
          >
            <q-item-section avatar>
              <q-avatar color="blue-1" text-color="blue-8" icon="straighten" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">
                {{ u.codigo }}
                <q-badge v-if="u.inativo" color="orange-7" class="q-ml-xs">Inativo</q-badge>
              </q-item-label>
              <q-item-label caption>{{ u.descricao }}</q-item-label>
            </q-item-section>
            <q-item-section side class="gt-xs">
              <q-item-label class="text-caption text-grey-7">
                {{ enteLabel[u.ente] || u.ente }}
              </q-item-label>
            </q-item-section>
            <q-item-section side class="text-right" style="min-width: 110px">
              <template v-if="u.valores && u.valores.length">
                <q-item-label class="text-weight-bold text-blue-9">
                  {{ formataNumero(u.valores[0].valor, 2) }}
                </q-item-label>
                <q-item-label caption>{{ formataMesAno(u.valores[0].competencia) }}</q-item-label>
              </template>
              <q-item-label v-else class="text-grey-5">—</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey-6 q-pa-xl">
          Nenhuma unidade de referência cadastrada.
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
        <q-tooltip anchor="center left" self="center right">Nova Unidade</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Dialog nova unidade -->
    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 440px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">NOVA UNIDADE</q-card-section>
        <q-form @submit.prevent="criar">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-5">
                <q-input
                  v-model="model.codigo"
                  outlined
                  label="Código"
                  maxlength="10"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                  @update:model-value="(v) => (model.codigo = (v || '').toUpperCase())"
                />
              </div>
              <div class="col-7">
                <q-select
                  v-model="model.ente"
                  :options="enteOptions"
                  emit-value
                  map-options
                  outlined
                  label="Ente"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.descricao"
                  outlined
                  label="Descrição"
                  maxlength="100"
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
