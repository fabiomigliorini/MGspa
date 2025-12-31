<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { useNotaFiscalItemCalculos } from 'src/composables/useNotaFiscalItemCalculos'
import NotaFiscalItemNav from 'src/components/NotaFiscalItem/NotaFiscalItemNav.vue'
import { ICMS_CST_OPTIONS, CSOSN_OPTIONS, IPI_CST_OPTIONS, PIS_CST_OPTIONS, COFINS_CST_OPTIONS } from 'src/constants/notaFiscal'
import { storeToRefs } from 'pinia'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = ref(false)
const codnotafiscal = computed(() => route.params.codnotafiscal)
const codnotafiscalitem = computed(() => route.params.codnotafiscalitem)
const nota = computed(() => notaFiscalStore.currentNota)
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Usa o editingItem do store diretamente (ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)

// Composable de cálculos usa o editingItem do store e passa o store para recalcular tributos
const calculos = useNotaFiscalItemCalculos(editingItem, notaFiscalStore)

// Methods
const loadFormData = async () => {
  try {
    loading.value = true

    // Carrega a nota fiscal se ainda não foi carregada
    if (!nota.value || nota.value.codnotafiscal !== parseInt(codnotafiscal.value)) {
      await notaFiscalStore.fetchNota(codnotafiscal.value)
    }

    // Verifica se já está editando este item
    if (!editingItem.value || editingItem.value.codnotafiscalprodutobarra !== parseInt(codnotafiscalitem.value)) {
      // Inicia a edição do item (cria cópia no store)
      notaFiscalStore.startEditingItem(codnotafiscalitem.value)
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar dados: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await notaFiscalStore.updateItemImpostos(codnotafiscalitem.value, editingItem.value)
    $q.notify({
      type: 'positive',
      message: 'Impostos atualizados com sucesso',
    })
    // Limpa o item em edição após salvar
    notaFiscalStore.clearEditingItem()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  // Limpa o item em edição ao cancelar
  notaFiscalStore.clearEditingItem()
  router.push({
    name: 'nota-fiscal-view',
    params: { codnotafiscal: codnotafiscal.value },
  })
}

// Lifecycle
onMounted(async () => {
  await loadFormData()
  // Inicializa os watchers de cálculo após carregar os dados
  calculos.setupWatchers()
  calculos.inicializaValorTotalFinal()
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <div style="max-width: 900px; margin: 0 auto">
        <!-- Header -->
        <div class="row items-center q-mb-md">
          <div class="text-h5">
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              @click="handleCancel"
              class="q-mr-sm"
              size="0.8em"
              :disable="loading"
            />
            Impostos - Item #{{ editingItem?.ordem }} - NFe #{{ nota?.numero }}
          </div>
          <q-space />
          <q-btn flat dense color="grey-7" icon="close" @click="handleCancel" :disable="loading" class="q-mr-sm">
            <q-tooltip>Cancelar</q-tooltip>
          </q-btn>
          <q-btn
            unelevated
            color="primary"
            icon="save"
            label="Salvar"
            type="submit"
            :loading="loading"
            :disable="notaBloqueada"
          />
        </div>

        <q-banner v-if="notaBloqueada && nota" class="bg-warning text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="lock" />
          </template>
          Esta nota está {{ nota.status }} e não pode ser editada.
        </q-banner>

        <!-- Navegação -->
        <NotaFiscalItemNav :codnotafiscal="codnotafiscal" :codnotafiscalitem="codnotafiscalitem" />

        <!-- ICMS -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="account_balance" size="sm" class="q-mr-xs" />
              ICMS
            </div>

            <div class="row q-col-gutter-md">
              <!-- CST / CSOSN -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="editingItem.icmscst"
                  :options="ICMS_CST_OPTIONS"
                  label="ICMS CST"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="notaBloqueada"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="editingItem.csosn"
                  :options="CSOSN_OPTIONS"
                  label="CSOSN (Simples Nacional)"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Base de Cálculo -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsbasepercentual"
                  label="% Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsbase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Alíquota -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmspercentual"
                  label="Alíquota ICMS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor ICMS -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsvalor"
                  label="Valor ICMS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- ICMS ST -->
              <div class="col-12">
                <q-separator class="q-my-md" />
                <div class="text-subtitle2 text-weight-medium q-mb-md">ICMS ST (Substituição Tributária)</div>
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsstbase"
                  label="Base de Cálculo ST"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsstpercentual"
                  label="Alíquota ST"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.icmsstvalor"
                  label="Valor ICMS ST"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- IPI -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="inventory" size="sm" class="q-mr-xs" />
              IPI
            </div>

            <div class="row q-col-gutter-md">
              <!-- CST -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="editingItem.ipicst"
                  :options="IPI_CST_OPTIONS"
                  label="IPI CST"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Base de Cálculo -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.ipibase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Alíquota -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.ipipercentual"
                  label="Alíquota IPI"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor IPI -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.ipivalor"
                  label="Valor IPI"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor Devolução -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.ipidevolucaovalor"
                  label="Valor Devolução IPI"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  hint="Valor de IPI devolvido"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- PIS -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="money" size="sm" class="q-mr-xs" />
              PIS
            </div>

            <div class="row q-col-gutter-md">
              <!-- CST -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="editingItem.piscst"
                  :options="PIS_CST_OPTIONS"
                  label="PIS CST"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Base -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.pisbase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Alíquota -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.pispercentual"
                  label="Alíquota PIS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.pisvalor"
                  label="Valor PIS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- COFINS -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="attach_money" size="sm" class="q-mr-xs" />
              COFINS
            </div>

            <div class="row q-col-gutter-md">
              <!-- CST -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="editingItem.cofinscst"
                  :options="COFINS_CST_OPTIONS"
                  label="COFINS CST"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Base -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.cofinsbase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Alíquota -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.cofinspercentual"
                  label="Alíquota COFINS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.cofinsvalor"
                  label="Valor COFINS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- CSLL e IRPJ -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="gavel" size="sm" class="q-mr-xs" />
              CSLL E IRPJ
            </div>

            <div class="row q-col-gutter-md">
              <!-- CSLL -->
              <div class="col-12">
                <div class="text-subtitle2 text-weight-medium q-mb-md">CSLL</div>
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.csllbase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.csllpercentual"
                  label="Alíquota CSLL"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.csllvalor"
                  label="Valor CSLL"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- IRPJ -->
              <div class="col-12">
                <q-separator class="q-my-md" />
                <div class="text-subtitle2 text-weight-medium q-mb-md">IRPJ</div>
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.irpjbase"
                  label="Base de Cálculo"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.irpjpercentual"
                  label="Alíquota IRPJ"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="editingItem.irpjvalor"
                  label="Valor IRPJ"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
</template>
