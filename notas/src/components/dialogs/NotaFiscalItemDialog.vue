<script setup>
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { Notify } from 'quasar'
import { useSelectProdutoBarraStore } from 'src/stores/selects/produtoBarra'
import { formatCodProduto, formatDecimal } from 'src/utils/formatters'

const store = useSelectProdutoBarraStore()
const emit = defineEmits(['update:modelValue', 'save'])

// const busca = ref(null)
const selectedProd = ref(null)
const scrollAreaRef = ref(null)
const loading = ref(false)

defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
})

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  emit('save', {
    produto: selectedProd.value,
  })
}

// Watch dialog close to reset form
watch(
  () => store.busca,
  () => {
    loading.value = true
    pesquisar()
  }
)

// pesquisa no backend
const pesquisar = useDebounceFn(async () => {
  try {
    await store.search()
    if (store.produtos.length > 0) {
      selectedProd.value = store.produtos[0]
    } else {
      selectedProd.value = null
    }
  } catch (error) {
    console.log(error)
    Notify.create({
      type: 'negative',
      message: 'Erro ao aplicar filtros',
      caption: error.message,
    })
  } finally {
    loading.value = false
  }
}, 800) // 800ms de debounce

// marca produto selecionado
const selectProd = (prod) => {
  selectedProd.value = prod
}

// scrola ate o produto selecionado
const scrollToSelected = (index) => {
  if (!scrollAreaRef.value) return

  // Aguarda o próximo tick para garantir que o DOM foi atualizado
  setTimeout(() => {
    const scrollArea = scrollAreaRef.value
    const listItems = scrollArea.$el.querySelectorAll('.q-item')
    const selectedItem = listItems[index]

    if (selectedItem) {
      selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  }, 50)
}

// navegacao pelo teclado (up, down, home, end)
const handleKeyNavigation = (event) => {
  if (!store.produtos || store.produtos.length === 0) return

  const currentIndex = selectedProd.value
    ? store.produtos.findIndex((p) => p === selectedProd.value)
    : -1

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    const nextIndex = currentIndex < store.produtos.length - 1 ? currentIndex + 1 : 0
    selectedProd.value = store.produtos[nextIndex]
    scrollToSelected(nextIndex)
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    const prevIndex = currentIndex > 0 ? currentIndex - 1 : store.produtos.length - 1
    selectedProd.value = store.produtos[prevIndex]
    scrollToSelected(prevIndex)
  } else if (event.key === 'Home') {
    event.preventDefault()
    selectedProd.value = store.produtos[0]
    scrollToSelected(0)
  } else if (event.key === 'End') {
    event.preventDefault()
    const lastIndex = store.produtos.length - 1
    selectedProd.value = store.produtos[lastIndex]
    scrollToSelected(lastIndex)
  }
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    @keydown="handleKeyNavigation"
  >
    <q-card class="dialog-card">
      <q-form @submit.prevent="handleSave">
        <!-- CABECALHO -->
        <q-card-section class="bg-primary text-white">
          <div class="text-h6 ellipsis">Adicionar item na Nota Fiscal</div>
        </q-card-section>

        <!-- campo de busca -->
        <q-card-section class="q-pt-md q-pb-md">
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="store.busca"
                label="busca produto"
                outlined
                unmasked-value
                hint="Busca por: barras, preço, código ou descrição"
                autofocus
              />
            </div>
          </div>
        </q-card-section>

        <!-- Itens -->
        <q-card-section style="height: 55vh">
          <!-- Carregando -->
          <template v-if="loading">
            <div class="flex justify-center items-center text-grey text-h6">
              <q-spinner color="grey" size="50px" class="q-mr-md" />
              Carregando...
            </div>
          </template>

          <!-- ainda nao digitou na busca -->
          <template v-else-if="store.busca == null || store.busca?.length <= 2">
            <div>
              <q-icon name="search"></q-icon>
              Digite algo para pesquisar!
            </div>
          </template>

          <!-- nao achou nada -->
          <template v-else-if="store.produtos.length == 0">
            <div class="full-height flex justify-center items-center text-grey text-h6">
              <q-icon name="error" size="50px" class="q-mr-md"></q-icon>
              Produto não encontrado!
            </div>
          </template>

          <!-- produtos -->
          <template v-else>
            <q-scroll-area ref="scrollAreaRef" class="full-height">
              <!-- se muitos produtos -->
              <template v-if="store.produtos.length >= 100">
                <q-banner class="bg-warning text-grey-8 rounded-borders q-mb-sm">
                  <template v-slot:avatar>
                    <q-icon name="warning" />
                  </template>
                  A pesquisa retornou mais de 100 Itens. Não é possível mostrar todos. Refine sua
                  pesquisa!
                </q-banner>
              </template>

              <!-- listagem -->
              <q-list separator>
                <template v-for="prod in store.produtos" :key="prod.codprodutoabarra">
                  <q-item
                    clickable
                    v-ripple
                    :active="prod == selectedProd"
                    active-class="bg-blue-2"
                    @click="selectProd(prod)"
                  >
                    <q-item-section avatar>
                      <q-img
                        :src="prod.imagem"
                        width="120px"
                        height="80px"
                        class="rounded-borders"
                        v-if="prod.imagem"
                        style="max-width: 10vw"
                      />
                      <q-img
                        src="/produtoSemImagem.png"
                        width="120px"
                        height="80px"
                        class="rounded-borders"
                        v-else
                        style="max-width: 10vw"
                      />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-weight-bold text-body1 text-grey-7">
                        {{ prod.descricao }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ formatCodProduto(prod.codproduto) }} | Barras {{ prod.barras }} |
                        {{ prod.referencia }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-item-label class="text-weight-bold text-body1">
                        {{ formatDecimal(prod.preco, 2) }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ prod.sigla }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-list>
            </q-scroll-area>
          </template>
        </q-card-section>

        <!-- BOTOES -->
        <q-card-actions align="right" class="q-pa-md">
          <q-space />
          <q-btn flat label="Cancelar" @click="close" />
          <q-btn
            unelevated
            label="Adicionar"
            color="primary"
            icon="save"
            type="submit"
            :loading="loading"
            :disable="!selectedProd"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.dialog-card {
  width: 600px;
  max-width: 95vw;
}
</style>
