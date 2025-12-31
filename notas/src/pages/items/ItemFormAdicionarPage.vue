<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'
import { formatCurrency } from 'src/utils/formatters'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Refs
const codigoBarrasInput = ref(null)

// State
const loading = ref(false)
const loadingProduto = ref(false)
const produto = ref(null)

const form = ref({
  codigobarra: '',
  quantidade: 1
})

// Methods
const buscarProduto = async () => {
  if (!form.value.codigobarra) {
    $q.notify({
      type: 'warning',
      message: 'Informe o código de barras'
    })
    return
  }

  loadingProduto.value = true
  try {
    // TODO: Implementar busca de produto via API
    // const response = await api.get(`/produto-barra/buscar/${form.value.codigobarra}`)
    // produto.value = response.data

    // Mock para desenvolvimento
    await new Promise(resolve => setTimeout(resolve, 500))
    produto.value = {
      codprodutobarra: 1,
      descricao: 'Produto Exemplo',
      estoque: 100,
      unidade: 'UN',
      valor: 10.50
    }

    // Foca no campo quantidade
    await nextTick()
    document.querySelector('input[type="number"]')?.focus()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Produto não encontrado',
      caption: error.response?.data?.message || error.message
    })
    produto.value = null
  } finally {
    loadingProduto.value = false
  }
}

const handleCodigoBarrasEnter = () => {
  if (form.value.codigobarra) {
    buscarProduto()
  }
}

const handleSubmit = async () => {
  if (!produto.value) {
    $q.notify({
      type: 'warning',
      message: 'Busque o produto antes de adicionar'
    })
    return
  }

  loading.value = true
  try {
    const itemData = {
      codprodutobarra: produto.value.codprodutobarra,
      quantidade: form.value.quantidade,
      valorunitario: produto.value.valor
    }

    await notaFiscalStore.createItem(route.params.codnotafiscal, itemData)

    $q.notify({
      type: 'positive',
      message: 'Item adicionado com sucesso',
      caption: 'Os tributos foram calculados automaticamente'
    })

    // Volta para a visualização da nota
    router.push({
      name: 'nota-fiscal-view',
      params: { codnotafiscal: route.params.codnotafiscal },
      hash: '#itens'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao adicionar item',
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
  // Foca no campo de código de barras
  nextTick(() => {
    codigoBarrasInput.value?.$el.querySelector('input')?.focus()
  })
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <div class="col">
          <div class="text-h5">Adicionar Item</div>
          <div class="text-caption text-grey-7">
            Informe o código de barras e a quantidade
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
            icon="add"
            label="Adicionar"
            type="submit"
            :loading="loading"
          />
        </div>
      </div>

      <!-- Formulário -->
      <q-card>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- Código de Barras -->
            <div class="col-12">
              <q-input
                ref="codigoBarrasInput"
                v-model="form.codigobarra"
                label="Código de Barras / EAN *"
                outlined
                autofocus
                :rules="[val => !!val || 'Campo obrigatório']"
                @keyup.enter="handleCodigoBarrasEnter"
              >
                <template v-slot:prepend>
                  <q-icon name="qr_code_scanner" />
                </template>
                <template v-slot:append>
                  <q-btn
                    flat
                    round
                    dense
                    icon="search"
                    @click="buscarProduto"
                    :loading="loadingProduto"
                  />
                </template>
              </q-input>

              <!-- Info do Produto -->
              <div v-if="produto" class="q-mt-sm q-pa-sm bg-blue-1 rounded-borders">
                <div class="text-body2 text-weight-medium">{{ produto.descricao }}</div>
                <div class="text-caption text-grey-7">
                  Estoque: {{ produto.estoque || 0 }} {{ produto.unidade || 'UN' }} |
                  Valor: R$ {{ formatCurrency(produto.valor) }}
                </div>
              </div>
            </div>

            <!-- Quantidade -->
            <div class="col-12 col-sm-6">
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
                :suffix="produto?.unidade || 'UN'"
              >
                <template v-slot:prepend>
                  <q-icon name="numbers" />
                </template>
              </q-input>
            </div>

            <!-- Valor Unitário (informativo) -->
            <div class="col-12 col-sm-6" v-if="produto">
              <q-input
                :model-value="formatCurrency(produto.valor)"
                label="Valor Unitário (Informativo)"
                outlined
                readonly
                prefix="R$"
              />
            </div>

            <!-- Total (informativo) -->
            <div class="col-12" v-if="produto && form.quantidade">
              <q-separator class="q-mb-md" />
              <div class="row items-center">
                <div class="col">
                  <div class="text-caption text-grey-7">Valor Total (Estimado)</div>
                  <div class="text-h6 text-primary">
                    R$ {{ formatCurrency(produto.valor * form.quantidade) }}
                  </div>
                  <div class="text-caption text-grey-8 q-mt-xs">
                    <q-icon name="info" size="xs" class="q-mr-xs" />
                    Os tributos serão calculados automaticamente pelo sistema
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Dica -->
      <q-banner class="bg-blue-1 q-mt-md">
        <template v-slot:avatar>
          <q-icon name="tips_and_updates" color="primary" />
        </template>
        <div class="text-body2">
          <strong>Dica:</strong> Após adicionar o item, você poderá editar os detalhes e tributos se necessário.
        </div>
      </q-banner>
    </q-form>
  </q-page>
</template>
