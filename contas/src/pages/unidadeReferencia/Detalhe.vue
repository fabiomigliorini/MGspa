<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataMesAno } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const cod = Number(route.params.codunidadereferencia)

const enteLabel = { FEDERAL: 'Federal', ESTADUAL: 'Estadual', MUNICIPAL: 'Municipal' }
const enteOptions = [
  { label: 'Federal', value: 'FEDERAL' },
  { label: 'Estadual', value: 'ESTADUAL' },
  { label: 'Municipal', value: 'MUNICIPAL' },
]

const unidade = ref(null)
const loading = ref(false)
const importando = ref(false)

const valores = computed(() => unidade.value?.valores || [])
const valorAtual = computed(() => valores.value[0] || null)
const ehUpf = computed(() => unidade.value?.codigo === 'UPF')

async function carregar() {
  loading.value = true
  try {
    const { data } = await api.get(`v1/unidade-referencia/${cod}`)
    unidade.value = data.data
  } catch (e) {
    notifyError(e, 'Erro ao carregar unidade')
  } finally {
    loading.value = false
  }
}

// ===================== Unidade (editar / inativar / excluir) =====================
const dialogUnidade = ref(false)
const savingUnidade = ref(false)
const modelUnidade = ref({ codigo: '', descricao: '', ente: 'ESTADUAL' })

function editarUnidade() {
  modelUnidade.value = {
    codigo: unidade.value.codigo,
    descricao: unidade.value.descricao,
    ente: unidade.value.ente,
  }
  dialogUnidade.value = true
}
async function salvarUnidade() {
  savingUnidade.value = true
  try {
    const { data } = await api.put(`v1/unidade-referencia/${cod}`, modelUnidade.value)
    unidade.value = data.data
    notifySuccess('Unidade atualizada')
    dialogUnidade.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar unidade')
  } finally {
    savingUnidade.value = false
  }
}
async function toggleInativo() {
  try {
    const { data } = unidade.value.inativo
      ? await api.delete(`v1/unidade-referencia/${cod}/inativo`)
      : await api.post(`v1/unidade-referencia/${cod}/inativo`)
    unidade.value = data.data
    notifySuccess(unidade.value.inativo ? 'Unidade inativada' : 'Unidade reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar status')
  }
}
function excluirUnidade() {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a unidade "${unidade.value.codigo}"? Os valores lançados também serão removidos.`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/unidade-referencia/${cod}`)
      notifySuccess('Unidade excluída')
      router.push({ name: 'unidade-referencia' })
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  })
}

// ===================== Valores (novo / editar / excluir) =====================
const dialog = ref(false)
const isNovo = ref(true)
const saving = ref(false)
const formRef = ref(null)
const model = ref({ codunidadereferenciavalor: null, competencia: null, valor: null })

function novo() {
  isNovo.value = true
  model.value = { codunidadereferenciavalor: null, competencia: null, valor: null }
  dialog.value = true
}
function editar(v) {
  isNovo.value = false
  model.value = {
    codunidadereferenciavalor: v.codunidadereferenciavalor,
    competencia: v.competencia,
    valor: v.valor,
  }
  dialog.value = true
}
// Limpa o estado de validação ao abrir — evita erro fantasma quando o form
// reabre depois de um salvamento anterior.
function onDialogShow() {
  formRef.value?.resetValidation()
}
async function salvar() {
  saving.value = true
  try {
    const payload = { competencia: model.value.competencia, valor: model.value.valor }
    if (isNovo.value) {
      await api.post(`v1/unidade-referencia/${cod}/valor`, payload)
      notifySuccess('Valor lançado')
    } else {
      await api.put(
        `v1/unidade-referencia/${cod}/valor/${model.value.codunidadereferenciavalor}`,
        payload,
      )
      notifySuccess('Valor atualizado')
    }
    dialog.value = false
    await carregar()
  } catch (e) {
    // 409 = competência já existe (o backend não sobrescreve no manual)
    notifyError(e, 'Erro ao salvar valor')
  } finally {
    saving.value = false
  }
}
function excluir(v) {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o valor de ${formataMesAno(v.competencia)}?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/unidade-referencia/${cod}/valor/${v.codunidadereferenciavalor}`)
      notifySuccess('Valor excluído')
      await carregar()
    } catch (e) {
      notifyError(e, 'Erro ao excluir valor')
    }
  })
}

async function importarUpf() {
  importando.value = true
  try {
    const { data } = await api.post('v1/unidade-referencia/importar-upf-mt')
    await carregar()
    if (data.total > 0) notifySuccess(`${data.total} competência(s) importada(s)`)
    else notifyError('Nada importado — verifique a SEFAZ ou lance manualmente.')
  } catch (e) {
    notifyError(e, 'Erro ao importar da SEFAZ-MT')
  } finally {
    importando.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 900px; margin: auto">
      <template v-if="unidade">
        <!-- Cabeçalho: voltar + identificação + ações da unidade -->
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'unidade-referencia' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h5 text-grey-9">
              {{ unidade.codigo }}
              <q-badge v-if="unidade.inativo" color="orange-7" class="q-ml-sm">Inativo</q-badge>
            </div>
            <div class="text-subtitle2 text-grey-7">{{ unidade.descricao }}</div>
            <div class="text-caption text-grey-6">
              {{ enteLabel[unidade.ente] || unidade.ente }}
            </div>
          </q-item-section>
          <q-item-section side>
            <div class="row items-center no-wrap">
              <q-btn
                v-if="ehUpf"
                flat
                round
                color="primary"
                icon="cloud_download"
                :loading="importando"
                @click="importarUpf"
              >
                <q-tooltip>Importar da SEFAZ-MT</q-tooltip>
              </q-btn>
              <q-btn flat round color="grey-7" icon="edit" @click="editarUnidade">
                <q-tooltip>Editar unidade</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="grey-7"
                :icon="unidade.inativo ? 'play_arrow' : 'pause'"
                @click="toggleInativo"
              >
                <q-tooltip>{{ unidade.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn flat round color="grey-7" icon="delete" @click="excluirUnidade">
                <q-tooltip>Excluir unidade</q-tooltip>
              </q-btn>
            </div>
          </q-item-section>
        </q-item>

        <!-- Valor atual (destaque) -->
        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center">
            <div>
              <div class="text-caption text-grey-7">Valor atual</div>
              <div class="text-h4 text-blue-9 text-weight-bold">
                {{ valorAtual ? formataNumero(valorAtual.valor, 2) : '—' }}
              </div>
            </div>
            <q-space />
            <div v-if="valorAtual" class="text-right text-grey-7">
              <div class="text-caption">Competência</div>
              <div class="text-subtitle1">{{ formataMesAno(valorAtual.competencia) }}</div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Histórico + CRUD de valores -->
        <q-card bordered flat>
          <q-item>
            <q-item-section>
              <q-item-label class="text-subtitle1">Valores por competência</q-item-label>
              <q-item-label caption>Histórico mensal (mais recente primeiro)</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn flat round color="primary" icon="add" @click="novo">
                <q-tooltip>Novo valor</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
          <q-separator />
          <q-list separator>
            <q-item v-for="v in valores" :key="v.codunidadereferenciavalor">
              <q-item-section avatar>
                <q-avatar color="blue-1" text-color="blue-8" icon="event" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">
                  {{ formataMesAno(v.competencia) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side class="text-right">
                <q-item-label class="text-weight-bold text-blue-9">
                  {{ formataNumero(v.valor, 2) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row items-center no-wrap">
                  <MgInfoCriacao :registro="v" />
                  <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editar(v)" />
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    icon="delete"
                    @click="excluir(v)"
                  />
                </div>
              </q-item-section>
            </q-item>
            <q-item v-if="!valores.length">
              <q-item-section class="text-grey-6 text-center q-py-lg">
                Nenhum valor lançado ainda.
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>

      <div v-else-if="loading" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>
    </div>

    <!-- Dialog editar unidade -->
    <q-dialog v-model="dialogUnidade">
      <q-card bordered flat style="width: 440px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">EDITAR UNIDADE</q-card-section>
        <q-form @submit.prevent="salvarUnidade">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-5">
                <q-input
                  v-model="modelUnidade.codigo"
                  outlined
                  label="Código"
                  maxlength="10"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                  @update:model-value="(v) => (modelUnidade.codigo = (v || '').toUpperCase())"
                />
              </div>
              <div class="col-7">
                <q-select
                  v-model="modelUnidade.ente"
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
                  v-model="modelUnidade.descricao"
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
            <q-btn flat label="Salvar" type="submit" :loading="savingUnidade" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Dialog novo/editar valor -->
    <q-dialog v-model="dialog" @show="onDialogShow">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO VALOR' : 'EDITAR VALOR' }}
        </q-card-section>
        <q-form ref="formRef" @submit.prevent="salvar">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <MgInputData
                  v-model="model.competencia"
                  label="Competência"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-6">
                <MgInputValor
                  v-model="model.valor"
                  :decimals="2"
                  label="Valor"
                  stack-label
                  lazy-rules
                  :rules="[(v) => (v != null && v > 0) || 'Obrigatório']"
                />
              </div>
            </div>
            <div class="text-caption text-grey-6 q-mt-sm">
              A competência usa sempre o 1º dia do mês (mês/ano).
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
