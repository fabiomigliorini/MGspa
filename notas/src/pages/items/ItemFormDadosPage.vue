<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = ref(false)
const loadingItem = ref(false)
const item = ref(null)
const valorTotalOriginal = ref(0)

const form = ref({
  quantidade: 1,
  valorunitario: 0,
  valortotal: 0,
  valordesconto: 0,
  valorfrete: 0,
  valorseguro: 0,
  valoroutras: 0,
  informacoesadicionais: null
})

// Computed
const valorTotalAlterado = computed(() => {
  return form.value.valortotal !== valorTotalOriginal.value
})

// Methods
const loadItem = async () => {
  loadingItem.value = true
  try {
    const itemData = await notaFiscalStore.fetchItem(
      route.params.codnotafiscal,
      route.params.codnotafiscalprodutobarra
    )

    item.value = itemData

    // Preenche o formulário
    form.value = {
      quantidade: itemData.quantidade || 1,
      valorunitario: parseFloat(itemData.valorunitario) || 0,
      valortotal: parseFloat(itemData.valortotal) || 0,
      valordesconto: parseFloat(itemData.valordesconto) || 0,
      valorfrete: parseFloat(itemData.valorfrete) || 0,
      valorseguro: parseFloat(itemData.valorseguro) || 0,
      valoroutras: parseFloat(itemData.valoroutras) || 0,
      informacoesadicionais: itemData.informacoesadicionais
    }

    valorTotalOriginal.value = form.value.valortotal
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar item',
      caption: error.response?.data?.message || error.message
    })
  } finally {
    loadingItem.value = false
  }
}

const calcularTotal = () => {
  const subtotal = (form.value.quantidade || 0) * (form.value.valorunitario || 0)
  const desconto = form.value.valordesconto || 0
  const frete = form.value.valorfrete || 0
  const seguro = form.value.valorseguro || 0
  const outras = form.value.valoroutras || 0

  form.value.valortotal = subtotal - desconto + frete + seguro + outras
  valorTotalOriginal.value = form.value.valortotal
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await notaFiscalStore.updateItem(
      route.params.codnotafiscal,
      route.params.codnotafiscalprodutobarra,
      form.value
    )

    $q.notify({
      type: 'positive',
      message: 'Item atualizado com sucesso',
      caption: 'Os tributos foram recalculados automaticamente'
    })

    router.push({
      name: 'nota-fiscal-view',
      params: { codnotafiscal: route.params.codnotafiscal },
      hash: '#itens'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao atualizar item',
      caption: error.response?.data?.message || error.message
    })
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  router.push({
    name: 'nota-fiscal-view',
    params: { codnotafiscal: route.params.codnotafiscal }
  })
}

// Lifecycle
onMounted(() => {
  loadItem()
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <div class="col">
          <div class="text-h5">Editar Dados do Item</div>
          <div v-if="item" class="text-caption text-grey-7">
            {{ item.produtoBarra?.descricao }}
          </div>
        </div>
        <div class="col-auto">
          <q-btn
            flat
            icon="close"
            label="Cancelar"
            @click="handleCancel"
            :disable="loading"
          />
          <q-btn
            color="primary"
            icon="save"
            label="Salvar"
            type="submit"
            :loading="loading"
          />
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loadingItem" class="row justify-center q-py-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <!-- Formulário -->
      <div v-else-if="item">
        <!-- Dados do Produto -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Produto</div>

            <div class="row q-col-gutter-md">
              <!-- Código de Barras -->
              <div class="col-12">
                <q-input
                  :model-value="item.produtoBarra?.codigobarra"
                  label="Código de Barras"
                  outlined
                  readonly
                >
                  <template v-slot:prepend>
                    <q-icon name="qr_code_scanner" />
                  </template>
                </q-input>
              </div>

              <!-- Descrição -->
              <div class="col-12">
                <q-input
                  :model-value="item.produtoBarra?.descricao"
                  label="Descrição do Produto"
                  outlined
                  readonly
                />
              </div>

              <!-- NCM -->
              <div class="col-12 col-sm-6">
                <q-input
                  :model-value="item.produtoBarra?.ncm"
                  label="NCM"
                  outlined
                  readonly
                />
              </div>

              <!-- CEST -->
              <div class="col-12 col-sm-6">
                <q-input
                  :model-value="item.produtoBarra?.cest"
                  label="CEST"
                  outlined
                  readonly
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Quantidade e Valores -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quantidade e Valores</div>

            <div class="row q-col-gutter-md">
              <!-- Quantidade -->
              <div class="col-12 col-sm-6 col-md-4">
                <q-input
                  v-model.number="form.quantidade"
                  label="Quantidade *"
                  outlined
                  type="number"
                  step="0.001"
                  min="0.001"
                  :rules="[
                    val => !!val || 'Campo obrigatório',
                    val => val > 0 || 'Quantidade deve ser maior que zero'
                  ]"
                  :suffix="item.produtoBarra?.unidade || 'UN'"
                  @update:model-value="calcularTotal"
                />
              </div>

              <!-- Valor Unitário -->
              <div class="col-12 col-sm-6 col-md-4">
                <q-input
                  v-model.number="form.valorunitario"
                  label="Valor Unitário *"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[
                    val => val !== null && val !== undefined || 'Campo obrigatório',
                    val => val >= 0 || 'Valor deve ser maior ou igual a zero'
                  ]"
                  prefix="R$"
                  @update:model-value="calcularTotal"
                />
              </div>

              <!-- Valor Total -->
              <div class="col-12 col-sm-12 col-md-4">
                <q-input
                  v-model.number="form.valortotal"
                  label="Valor Total *"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[
                    val => val !== null && val !== undefined || 'Campo obrigatório',
                    val => val >= 0 || 'Valor deve ser maior ou igual a zero'
                  ]"
                  prefix="R$"
                  hint="Calculado automaticamente"
                  readonly
                  :bg-color="valorTotalAlterado ? 'orange-1' : 'grey-2'"
                />
              </div>

              <!-- Desconto -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.valordesconto"
                  label="Desconto"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  @update:model-value="calcularTotal"
                />
              </div>

              <!-- Frete -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.valorfrete"
                  label="Frete"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  @update:model-value="calcularTotal"
                />
              </div>

              <!-- Seguro -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.valorseguro"
                  label="Seguro"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  @update:model-value="calcularTotal"
                />
              </div>

              <!-- Outras Despesas -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.valoroutras"
                  label="Outras Despesas"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                  @update:model-value="calcularTotal"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Informações Adicionais -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Informações Adicionais</div>

            <div class="row q-col-gutter-md">
              <!-- Informações Adicionais -->
              <div class="col-12">
                <q-input
                  v-model="form.informacoesadicionais"
                  label="Informações Adicionais do Item"
                  outlined
                  type="textarea"
                  rows="3"
                  hint="Informações que aparecerão no DANFE"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Alerta sobre recálculo de tributos -->
        <q-banner class="bg-orange-1 q-mb-md">
          <template v-slot:avatar>
            <q-icon name="warning" color="orange" />
          </template>
          <div class="text-body2">
            <strong>Atenção:</strong> Ao salvar, os tributos serão recalculados automaticamente com base nos novos valores.
          </div>
        </q-banner>
      </div>

      <!-- Erro -->
      <q-card v-else flat bordered class="q-pa-xl text-center">
        <q-icon name="error" size="4em" color="negative" />
        <div class="text-h6 text-grey-7 q-mt-md">Item não encontrado</div>
      </q-card>
    </q-form>
  </q-page>
</template>
