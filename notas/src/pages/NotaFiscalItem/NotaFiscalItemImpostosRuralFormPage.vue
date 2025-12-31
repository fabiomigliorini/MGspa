<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { useNotaFiscalItemCalculos } from 'src/composables/useNotaFiscalItemCalculos'
import NotaFiscalItemNav from 'src/components/NotaFiscalItem/NotaFiscalItemNav.vue'
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
    await notaFiscalStore.updateItemImpostosRural(codnotafiscalitem.value, editingItem.value)
    $q.notify({
      type: 'positive',
      message: 'Impostos rurais atualizados com sucesso',
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
            Impostos Rural - Item #{{ editingItem?.ordem }} - NFe #{{ nota?.numero }}
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

        <q-banner class="bg-info text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="info" />
          </template>
          Impostos específicos para atividade rural e produtor rural (Mato Grosso).
        </q-banner>

        <!-- Certificação SEFAZ MT -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="verified" size="sm" class="q-mr-xs" />
              CERTIFICAÇÃO
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-toggle
                  v-model="editingItem.certidaosefazmt"
                  label="Possui Certidão SEFAZ/MT"
                  color="primary"
                  :disable="notaBloqueada"
                />
                <div class="text-caption text-grey-7 q-mt-xs">
                  Produtor possui certidão de regularidade da SEFAZ Mato Grosso
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- FETHAB -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="nature" size="sm" class="q-mr-xs" />
              FETHAB (Fundo Estadual de Transporte e Habitação)
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.fethabkg"
                  label="FETHAB por Kg"
                  outlined
                  type="number"
                  step="0.0001"
                  min="0"
                  prefix="R$"
                  hint="Valor do FETHAB por quilograma"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.fethabvalor"
                  label="Valor Total FETHAB"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  hint="Valor total do FETHAB"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- IAGRO -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="eco" size="sm" class="q-mr-xs" />
              IAGRO (Instituto de Defesa Agropecuária)
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.iagrokg"
                  label="IAGRO por Kg"
                  outlined
                  type="number"
                  step="0.0001"
                  min="0"
                  prefix="R$"
                  hint="Valor do IAGRO por quilograma"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.iagrovalor"
                  label="Valor Total IAGRO"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  hint="Valor total do IAGRO"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- FUNRURAL -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="agriculture" size="sm" class="q-mr-xs" />
              FUNRURAL (Fundo de Assistência ao Trabalhador Rural)
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.funruralpercentual"
                  label="Alíquota FUNRURAL"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  hint="Percentual do FUNRURAL"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.funruralvalor"
                  label="Valor FUNRURAL"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  hint="Valor total do FUNRURAL"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- SENAR -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="school" size="sm" class="q-mr-xs" />
              SENAR (Serviço Nacional de Aprendizagem Rural)
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.senarpercentual"
                  label="Alíquota SENAR"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  hint="Percentual do SENAR"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.senarvalor"
                  label="Valor SENAR"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  hint="Valor total do SENAR"
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
