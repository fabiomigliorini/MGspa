<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import DetalhesTab from 'src/components/NotaFiscalItemTabs/DetalhesTab.vue'
import ImpostosTab from 'src/components/NotaFiscalItemTabs/ImpostosTab.vue'
import ImpostosRuralTab from 'src/components/NotaFiscalItemTabs/ImpostosRuralTab.vue'
import ImpostosReformaTab from 'src/components/NotaFiscalItemTabs/ImpostosReformaTab.vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { useNotaFiscalItemCalculos } from 'src/composables/useNotaFiscalItemCalculos'
import { storeToRefs } from 'pinia'

const route = useRoute()

const codnotafiscal = computed(() => route.params.codnotafiscal)
const codnotafiscalitem = computed(() => route.params.codnotafiscalitem)

const tab = ref('reforma')

const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = ref(false)
const nota = computed(() => notaFiscalStore.currentNota)
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Usa o editingItem do store diretamente (ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)

// Composable de cálculos usa o editingItem do store e passa o store para recalcular tributos
const calculos = useNotaFiscalItemCalculos(editingItem, notaFiscalStore)

// Computed
const valorTotal = computed(() => {
  return editingItem.value?.valortotal || 0
})

const valorTotalFinal = computed(() => {
  return editingItem.value?.valortotalfinal || 0
})

// Methods
const loadFormData = async () => {
  try {
    loading.value = true

    // Carrega a nota fiscal se ainda não foi carregada
    if (!nota.value || nota.value.codnotafiscal !== parseInt(codnotafiscal.value)) {
      await notaFiscalStore.fetchNota(codnotafiscal.value)
    }

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
    await notaFiscalStore.updateItem(codnotafiscal.value, codnotafiscalitem.value, editingItem.value)
    $q.notify({
      type: 'positive',
      message: 'Item atualizado com sucesso',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await loadFormData()
  // Inicializa os watchers de cálculo após carregar os dados
  calculos.setupWatchers()
  calculos.inicializaValorTotalFinal()
})

// Limpa o item em edição ao sair da página (se o usuário navegar para outra página)
onBeforeUnmount(() => {
  // Não limpa se estiver navegando entre as abas de edição do mesmo item
  // (será limpo apenas no cancelar ou salvar)
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit" v-if="editingItem">
      <div style="max-width: 700px; margin: 0 auto">

        <!-- Header -->
        <div class="row items-center q-mb-md">
          <div class="text-h5">
            <q-btn flat dense round icon="arrow_back" :to="'/notas/' + codnotafiscal" class="q-mr-sm" size="0.8em"
              :disable="loading" />
            <!-- Produto -->
            <div class="col-12">
              <q-avatar>
                <q-img :src="editingItem.produtoBarra.imagem" />
              </q-avatar>
              {{ editingItem.produtoBarra.descricao }}
            </div>
          </div>
          <q-space />
          <q-btn flat dense color="grey-7" icon="close" :to="'/notas/' + codnotafiscal" :disable="loading"
            class="q-mr-sm">
            <q-tooltip>Cancelar</q-tooltip>
          </q-btn>
          <q-btn unelevated color="primary" icon="save" label="Salvar" type="submit" :loading="loading"
            :disable="notaBloqueada" />
        </div>

        <!-- Produto e Quantidades -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="inventory_2" size="sm" class="q-mr-xs" />
              PRODUTO
            </div>

            <div class="row q-col-gutter-md">

              <!-- Quantidade -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.quantidade" label="Quantidade *" outlined type="number"
                  step="0.0001" min="0.0001" :rules="[
                    (val) => val !== null && val !== undefined || 'Campo obrigatório',
                    (val) => val > 0 || 'Deve ser maior que zero',
                  ]" lazy-rules :disable="notaBloqueada" input-class="text-right" autofocus hint="" />
              </div>

              <!-- Valor Unitário -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.valorunitario" label="Valor Unitário *" outlined type="number"
                  step="0.000001" min="0" prefix="R$" :rules="[
                    (val) => val !== null && val !== undefined || 'Campo obrigatório',
                    (val) => val >= 0 || 'Deve ser maior ou igual a zero',
                  ]" lazy-rules :disable="notaBloqueada" input-class="text-right" hint="" />
              </div>

              <!-- Valor Total (calculado) -->
              <div class="col-6 col-sm-3">
                <q-input :model-value="valorTotal.toFixed(2)" label="Total Produto" outlined readonly prefix="R$"
                  input-class="text-right" hint="" />
              </div>

              <!-- Desconto -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.valordesconto" label="Desconto" outlined type="number" step="0.01"
                  min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" hint="" />
              </div>

              <!-- Frete -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.valorfrete" label="Frete" outlined type="number" step="0.01"
                  min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" hint="" />
              </div>

              <!-- Seguro -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.valorseguro" label="Seguro" outlined type="number" step="0.01"
                  min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" hint="" />
              </div>

              <!-- Outras Despesas -->
              <div class="col-6 col-sm-3">
                <q-input v-model.number="editingItem.valoroutras" label="Outras Despesas" outlined type="number"
                  step="0.01" min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" hint="" />
              </div>

              <!-- Valor Total Final (calculado) -->
              <div class="col-6 col-sm-3">
                <q-input :model-value="valorTotalFinal.toFixed(2)" label="Valor Total Final" outlined readonly
                  prefix="R$" input-class="text-right text-weight-bold" hint="" bg-color="blue-grey-1" />
              </div>
            </div>

          </q-card-section>
        </q-card>


        <q-banner v-if="notaBloqueada && nota" class="bg-warning text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="lock" />
          </template>
          Esta nota está {{ nota.status }} e não pode ser editada.
        </q-banner>

        <q-card flat bordered>
          <q-tabs v-model="tab" align="left" class="text-grey-7 bg-grey-2" active-bg-color="primary"
            active-color="white" indicator-color="transparent" inline-label no-caps>
            <q-tab name="detalhes" label="Detalhes" icon="shopping_cart" />
            <q-tab name="impostos" label="Impostos" icon="calculate" />
            <q-tab name="rural" label="Impostos Rural" icon="agriculture" />
            <q-tab name="reforma" label="Reforma Tributária" icon="gavel" />
          </q-tabs>

          <q-card-section class="q-pa-none">
            <q-tab-panels v-model="tab" animated>
              <q-tab-panel name="detalhes">
                <DetalhesTab />
              </q-tab-panel>
              <q-tab-panel name="impostos">
                <ImpostosTab />
              </q-tab-panel>
              <q-tab-panel name="rural">
                <ImpostosRuralTab />
              </q-tab-panel>
              <q-tab-panel name="reforma">
                <ImpostosReformaTab />
              </q-tab-panel>
            </q-tab-panels>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
  <div class="q-my-lg">&nbsp;</div>
</template>
