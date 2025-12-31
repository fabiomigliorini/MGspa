<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
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
const isEditMode = computed(() => !!codnotafiscalitem.value && codnotafiscalitem.value !== 'novo')
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

    if (isEditMode.value) {
      // Verifica se já está editando este item
      if (!editingItem.value || editingItem.value.codnotafiscalprodutobarra !== parseInt(codnotafiscalitem.value)) {
        // Inicia a edição do item (cria cópia no store)
        notaFiscalStore.startEditingItem(codnotafiscalitem.value)
      }
    } else {
      // Modo criação - inicializa um item vazio
      notaFiscalStore.editingItem = {
        codprodutobarra: null,
        ordem: null,
        quantidade: 1,
        valorunitario: 0,
        valortotal: 0,
        valortotalfinal: 0,
        valordesconto: 0,
        valorfrete: 0,
        valorseguro: 0,
        valoroutras: 0,
        codcfop: null,
        descricaoalternativa: '',
        pedido: '',
        pedidoitem: '',
        observacoes: '',
        devolucaopercentual: 0,
        codnegocioprodutobarra: null,
      }
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
    if (isEditMode.value) {
      await notaFiscalStore.updateItemDetalhes(codnotafiscalitem.value, editingItem.value)
      $q.notify({
        type: 'positive',
        message: 'Item atualizado com sucesso',
      })
      // Limpa o item em edição após salvar
      notaFiscalStore.clearEditingItem()
    } else {
      const newItem = await notaFiscalStore.createItem(codnotafiscal.value, editingItem.value)
      $q.notify({
        type: 'positive',
        message: 'Item criado com sucesso',
      })
      // Limpa o item em edição
      notaFiscalStore.clearEditingItem()
      // Redireciona para edição do item recém-criado
      router.replace({
        name: 'nota-fiscal-item-detalhes',
        params: {
          codnotafiscal: codnotafiscal.value,
          codnotafiscalitem: newItem.codnotafiscalprodutobarra,
        },
      })
    }
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

// Limpa o item em edição ao sair da página (se o usuário navegar para outra página)
onBeforeUnmount(() => {
  // Não limpa se estiver navegando entre as abas de edição do mesmo item
  // (será limpo apenas no cancelar ou salvar)
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
            {{ isEditMode ? 'Editar Item' : 'Novo Item' }} - NFe #{{ nota?.numero }}
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
        <NotaFiscalItemNav
          v-if="isEditMode"
          :codnotafiscal="codnotafiscal"
          :codnotafiscalitem="codnotafiscalitem"
        />

        <!-- Produto e Quantidades -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="inventory_2" size="sm" class="q-mr-xs" />
              PRODUTO
            </div>

            <div class="row q-col-gutter-md">
              <!-- Produto -->
              <div class="col-12">
                <q-input
                  v-model.number="editingItem.codprodutobarra"
                  label="Código Produto *"
                  outlined
                  type="number"
                  hint="Código do produto (codprodutobarra)"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Ordem -->
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="editingItem.ordem"
                  label="Ordem"
                  outlined
                  type="number"
                  min="0"
                  hint="Ordem de exibição"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Quantidade -->
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="editingItem.quantidade"
                  label="Quantidade *"
                  outlined
                  type="number"
                  step="0.0001"
                  min="0.0001"
                  :rules="[
                    (val) => val !== null && val !== undefined || 'Campo obrigatório',
                    (val) => val > 0 || 'Deve ser maior que zero',
                  ]"
                  lazy-rules
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor Unitário -->
              <div class="col-12 col-sm-3">
                <q-input
                  v-model.number="editingItem.valorunitario"
                  label="Valor Unitário *"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :rules="[
                    (val) => val !== null && val !== undefined || 'Campo obrigatório',
                    (val) => val >= 0 || 'Deve ser maior ou igual a zero',
                  ]"
                  lazy-rules
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor Total (calculado) -->
              <div class="col-12 col-sm-3">
                <q-input
                  :model-value="valorTotal.toFixed(2)"
                  label="Valor Total"
                  outlined
                  readonly
                  prefix="R$"
                  input-class="text-right"
                  hint="Calculado automaticamente"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Valores Adicionais -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="payments" size="sm" class="q-mr-xs" />
              VALORES ADICIONAIS
            </div>

            <div class="row q-col-gutter-md">
              <!-- Desconto -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input
                  v-model.number="editingItem.valordesconto"
                  label="Desconto"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Frete -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input
                  v-model.number="editingItem.valorfrete"
                  label="Frete"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Seguro -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input
                  v-model.number="editingItem.valorseguro"
                  label="Seguro"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Outras Despesas -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input
                  v-model.number="editingItem.valoroutras"
                  label="Outras Despesas"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Valor Total Final (calculado) -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input
                  :model-value="valorTotalFinal.toFixed(2)"
                  label="Valor Total Final"
                  outlined
                  readonly
                  prefix="R$"
                  input-class="text-right text-weight-bold"
                  hint="Total - Desconto + Frete + Seguro + Outras"
                  bg-color="blue-grey-1"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Informações Fiscais -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="receipt_long" size="sm" class="q-mr-xs" />
              INFORMAÇÕES FISCAIS
            </div>

            <div class="row q-col-gutter-md">
              <!-- CFOP -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.codcfop"
                  label="Código CFOP *"
                  outlined
                  type="number"
                  hint="Código do CFOP (codcfop)"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Devolução Percentual -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editingItem.devolucaopercentual"
                  label="% Devolução"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  suffix="%"
                  hint="Percentual de devolução (se aplicável)"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Descrição Alternativa -->
              <div class="col-12">
                <q-input
                  v-model="editingItem.descricaoalternativa"
                  label="Descrição Alternativa"
                  outlined
                  maxlength="120"
                  hint="Descrição customizada para a NFe (máx. 120 caracteres)"
                  :disable="notaBloqueada"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Pedido e Observações -->
        <q-card v-if="editingItem" flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="notes" size="sm" class="q-mr-xs" />
              INFORMAÇÕES COMPLEMENTARES
            </div>

            <div class="row q-col-gutter-md">
              <!-- Pedido -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="editingItem.pedido"
                  label="Número do Pedido"
                  outlined
                  maxlength="15"
                  hint="Referência ao pedido de venda/compra"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Item do Pedido -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="editingItem.pedidoitem"
                  label="Item do Pedido"
                  outlined
                  maxlength="6"
                  hint="Número do item no pedido"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Observações -->
              <div class="col-12">
                <q-input
                  v-model="editingItem.observacoes"
                  label="Observações"
                  outlined
                  type="textarea"
                  rows="3"
                  maxlength="1500"
                  counter
                  hint="Informações adicionais sobre o item (máx. 1500 caracteres)"
                  :disable="notaBloqueada"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
</template>
