<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  useTributacaoNaturezaOperacaoStore,
  TIPO_PRODUTO_OPTIONS,
} from '../stores/tributacaoNaturezaOperacaoStore'
import api from '../services/api'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const tributacaoStore = useTributacaoNaturezaOperacaoStore()

const loading = ref(false)
const codnaturezaoperacao = computed(() => route.params.codnaturezaoperacao)
const isEditMode = computed(() => !!route.params.codtributacaonaturezaoperacao)
const tributacao = computed(() => tributacaoStore.currentTributacao)

// Options para selects
const tributacaoOptions = ref([])
const estadoOptions = ref([])
const cfopOptions = ref([])

const form = ref({
  codtributacao: null,
  codcfop: null,
  codestado: null,
  codtipoproduto: null,
  ncm: null,
  bit: false,
  icmscst: null,
  icmsbase: null,
  icmspercentual: null,
  icmslpbase: null,
  icmslppercentual: null,
  csosn: null,
  ipicst: null,
  piscst: null,
  pispercentual: null,
  cofinscst: null,
  cofinspercentual: null,
  csllpercentual: null,
  irpjpercentual: null,
  observacoesnf: null,
})

const formatValidationErrors = (error) => {
  if (error.response?.data?.errors) {
    const errors = error.response.data.errors
    const messages = []
    for (const fieldErrors of Object.values(errors)) {
      messages.push(...fieldErrors)
    }
    return messages.join('\n')
  }
  return error.response?.data?.message || error.message
}

const loadOptions = async () => {
  try {
    const [tributacoesRes, estadosRes, cfopsRes] = await Promise.all([
      api.get('/v1/select/tributo'),
      api.get('/v1/select/estado'),
      api.get('/v1/cfop', { params: { per_page: 1000 } }),
    ])
    tributacaoOptions.value = tributacoesRes.data?.data || []
    estadoOptions.value = estadosRes.data?.data || []
    cfopOptions.value = (cfopsRes.data?.data || []).map((c) => ({
      ...c,
      label: `${c.cfop} - ${c.descricao}`,
    }))
  } catch (error) {
    console.error('Erro ao carregar opções:', error)
  }
}

const loadFormData = async () => {
  if (isEditMode.value) {
    loading.value = true
    try {
      tributacaoStore.setCodNaturezaOperacao(parseInt(codnaturezaoperacao.value))
      await tributacaoStore.fetchTributacao(route.params.codtributacaonaturezaoperacao)
      if (tributacao.value) {
        form.value = {
          codtributacao: tributacao.value.codtributacao,
          codcfop: tributacao.value.codcfop,
          codestado: tributacao.value.codestado,
          codtipoproduto: tributacao.value.codtipoproduto,
          ncm: tributacao.value.ncm,
          bit: tributacao.value.bit || false,
          icmscst: tributacao.value.icmscst,
          icmsbase: tributacao.value.icmsbase,
          icmspercentual: tributacao.value.icmspercentual,
          icmslpbase: tributacao.value.icmslpbase,
          icmslppercentual: tributacao.value.icmslppercentual,
          csosn: tributacao.value.csosn,
          ipicst: tributacao.value.ipicst,
          piscst: tributacao.value.piscst,
          pispercentual: tributacao.value.pispercentual,
          cofinscst: tributacao.value.cofinscst,
          cofinspercentual: tributacao.value.cofinspercentual,
          csllpercentual: tributacao.value.csllpercentual,
          irpjpercentual: tributacao.value.irpjpercentual,
          observacoesnf: tributacao.value.observacoesnf,
        }
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar Tributação',
        caption: formatValidationErrors(error),
        multiLine: true,
      })
      handleCancel()
    } finally {
      loading.value = false
    }
  } else {
    tributacaoStore.setCodNaturezaOperacao(parseInt(codnaturezaoperacao.value))
  }
}

const handleSubmit = async () => {
  $q.dialog({
    title: 'Confirmação',
    message: 'Tem certeza que deseja salvar?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Salvar', color: 'primary' },
    persistent: true,
  }).onOk(async () => {
    loading.value = true
    try {
      if (isEditMode.value) {
        await tributacaoStore.updateTributacao(
          route.params.codtributacaonaturezaoperacao,
          form.value,
        )
        $q.notify({ type: 'positive', message: 'Tributação atualizada com sucesso' })
      } else {
        await tributacaoStore.createTributacao(form.value)
        $q.notify({ type: 'positive', message: 'Tributação criada com sucesso' })
      }
      handleCancel()
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} Tributação`,
        caption: formatValidationErrors(error),
        multiLine: true,
        timeout: 5000,
      })
    } finally {
      loading.value = false
    }
  })
}

const handleCancel = () => {
  router.push({
    name: 'natureza-operacao-view',
    params: { codnaturezaoperacao: codnaturezaoperacao.value },
  })
}

onMounted(async () => {
  await loadOptions()
  await loadFormData()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 900px; margin: 0 auto">
      <q-form @submit.prevent="handleSubmit">
        <!-- Header -->
        <div class="row items-center q-mb-md">
          <q-btn flat dense round icon="arrow_back" @click="handleCancel" :disable="loading" />
          <div class="text-h5 q-ml-sm">
            <template v-if="isEditMode">
              Alterar Tributação #{{ route.params.codtributacaonaturezaoperacao }}
            </template>
            <template v-else>Nova Tributação</template>
          </div>
        </div>

        <!-- Card 1 - Identificação -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">
            <q-icon name="badge" class="q-mr-sm" />
            Identificação
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <!-- Tributação -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.codtributacao"
                  :options="tributacaoOptions"
                  option-value="codtributacao"
                  option-label="tributacao"
                  emit-value
                  map-options
                  outlined
                  label="Tributação *"
                  :disable="loading"
                  :rules="[(val) => val !== null || 'Tributação é obrigatória']"
                >
                  <template v-slot:prepend>
                    <q-icon name="receipt" />
                  </template>
                </q-select>
              </div>

              <!-- CFOP -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.codcfop"
                  :options="cfopOptions"
                  option-value="codcfop"
                  option-label="label"
                  emit-value
                  map-options
                  outlined
                  label="CFOP *"
                  :disable="loading"
                  use-input
                  input-debounce="300"
                  :rules="[(val) => val !== null || 'CFOP é obrigatório']"
                  @filter="(val, update) => update(() => {})"
                >
                  <template v-slot:prepend>
                    <q-icon name="numbers" />
                  </template>
                </q-select>
              </div>

              <!-- Tipo de Produto -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.codtipoproduto"
                  :options="TIPO_PRODUTO_OPTIONS"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Tipo de Produto"
                  :disable="loading"
                >
                  <template v-slot:prepend>
                    <q-icon name="category" />
                  </template>
                </q-select>
              </div>

              <!-- Estado -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.codestado"
                  :options="estadoOptions"
                  option-value="codcidade"
                  option-label="sigla"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Estado"
                  :disable="loading"
                >
                  <template v-slot:prepend>
                    <q-icon name="map" />
                  </template>
                </q-select>
              </div>

              <!-- NCM -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.ncm"
                  outlined
                  label="NCM"
                  maxlength="10"
                  :disable="loading"
                >
                  <template v-slot:prepend>
                    <q-icon name="tag" />
                  </template>
                </q-input>
              </div>

              <!-- BIT -->
              <div class="col-12 col-sm-6">
                <q-toggle v-model="form.bit" label="BIT" :disable="loading" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 2 - Simples Nacional -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-teal q-pa-sm">
            <q-icon name="store" class="q-mr-sm" />
            Simples Nacional
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.csosn"
                  outlined
                  label="CSOSN"
                  type="number"
                  :disable="loading"
                >
                  <template v-slot:prepend>
                    <q-icon name="tag" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.icmsbase"
                  outlined
                  label="Base ICMS (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.icmspercentual"
                  outlined
                  label="Alíquota (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 3 - ICMS -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-blue q-pa-sm">
            <q-icon name="account_balance" class="q-mr-sm" />
            ICMS
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.icmscst"
                  outlined
                  label="CST"
                  type="number"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.icmslpbase"
                  outlined
                  label="Base (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.icmslppercentual"
                  outlined
                  label="Alíquota (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 4 - PIS/COFINS -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-purple q-pa-sm">
            <q-icon name="paid" class="q-mr-sm" />
            PIS / COFINS
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.piscst"
                  outlined
                  label="PIS CST"
                  type="number"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.pispercentual"
                  outlined
                  label="PIS (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.cofinscst"
                  outlined
                  label="COFINS CST"
                  type="number"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="form.cofinspercentual"
                  outlined
                  label="COFINS (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 5 - IPI/CSLL/IRPJ -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-orange q-pa-sm">
            <q-icon name="receipt_long" class="q-mr-sm" />
            IPI / CSLL / IRPJ
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.ipicst"
                  outlined
                  label="IPI CST"
                  type="number"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.csllpercentual"
                  outlined
                  label="CSLL (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.irpjpercentual"
                  outlined
                  label="IRPJ (%)"
                  type="number"
                  step="0.01"
                  :disable="loading"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 6 - Observações -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-grey-7 q-pa-sm">
            <q-icon name="notes" class="q-mr-sm" />
            Observações
          </div>
          <q-card-section>
            <q-input
              v-model="form.observacoesnf"
              outlined
              type="textarea"
              label="Observações para Nota Fiscal"
              :disable="loading"
              rows="3"
            />
          </q-card-section>
        </q-card>

        <!-- Auditoria -->
        <div v-if="isEditMode && tributacao" class="text-caption text-grey q-mt-md">
          <span v-if="tributacao.usuarioAlteracao">
            Alterado em {{ new Date(tributacao.alteracao).toLocaleString('pt-BR') }} por
            {{ tributacao.usuarioAlteracao.usuario }}
          </span>
        </div>

        <!-- FAB Salvar -->
        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab color="primary" icon="save" type="submit" :loading="loading">
            <q-tooltip>Salvar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
