<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from 'stores/notaFiscalStore'
import { formatCurrency, formatDecimal } from 'src/utils/formatters'
import SelectPessoa from 'src/components/selects/SelectPessoa.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const store = useNotaFiscalStore()

const codnotafiscal = computed(() => parseInt(route.params.codnotafiscal))
const loading = ref(false)
const submitting = ref(false)
const codpessoa = ref(null)

// Estado local para controlar quantidades de devolução
const quantidades = ref({})

// Nota atual
const nota = computed(() => store.currentNota)

// Verifica se a nota original é para consumidor genérico (codpessoa=1)
const isConsumidorGenerico = computed(() => nota.value?.codpessoa === 1)

// Itens disponíveis para devolução
const itens = computed(() => store.itens || [])

// Verifica se há pelo menos um item selecionado para devolução
const hasItemSelecionado = computed(() => {
  return Object.values(quantidades.value).some((qtd) => qtd > 0)
})

// Valor total da devolução
const valorTotalDevolucao = computed(() => {
  return itens.value.reduce((total, item) => {
    const qtdDevolucao = quantidades.value[item.codnotafiscalprodutobarra] || 0
    if (qtdDevolucao > 0) {
      const valorUnitario = parseFloat(item.valorunitario) || 0
      return total + valorUnitario * qtdDevolucao
    }
    return total
  }, 0)
})

// Carrega a nota fiscal
onMounted(async () => {
  loading.value = true
  try {
    await store.fetchNota(codnotafiscal.value)
    // Inicializa quantidades com 0
    itens.value.forEach((item) => {
      quantidades.value[item.codnotafiscalprodutobarra] = 0
    })
    // Se não for consumidor genérico, usa a pessoa da nota original
    if (!isConsumidorGenerico.value && nota.value?.codpessoa) {
      codpessoa.value = nota.value.codpessoa
    }
  } catch (error) {
    console.log(error)
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar nota fiscal',
    })
    router.push({ name: 'notas' })
  } finally {
    loading.value = false
  }
})

// Marca todos os itens com quantidade máxima
const marcarTodos = () => {
  itens.value.forEach((item) => {
    quantidades.value[item.codnotafiscalprodutobarra] = parseFloat(item.quantidade) || 0
  })
}

// Desmarca todos os itens
const marcarNenhum = () => {
  itens.value.forEach((item) => {
    quantidades.value[item.codnotafiscalprodutobarra] = 0
  })
}

// Imagem do item
const getImagem = (item) => {
  if (item.produtoBarra?.imagem) {
    return item.produtoBarra.imagem
  }
  return '/produtoSemImagem.png'
}

// Confirmação antes de submeter
const confirmarDevolucao = () => {
  if (isConsumidorGenerico.value && !codpessoa.value) {
    $q.notify({
      type: 'warning',
      message: 'Informe o cadastro da pessoa para fazer uma devolução!',
    })
    return
  }

  if (!hasItemSelecionado.value) {
    $q.notify({
      type: 'warning',
      message: 'Selecione ao menos um item para devolução',
    })
    return
  }

  $q.dialog({
    title: 'Confirmar Devolução',
    message: `Deseja criar uma nota fiscal de devolução no valor de ${formatCurrency(valorTotalDevolucao.value)}?`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    submitDevolucao()
  })
}

// Submete a devolução
const submitDevolucao = async () => {
  // Monta array de itens para enviar
  const itensParaEnviar = itens.value
    .filter((item) => quantidades.value[item.codnotafiscalprodutobarra] > 0)
    .map((item) => ({
      codnotafiscalprodutobarra: item.codnotafiscalprodutobarra,
      quantidade: quantidades.value[item.codnotafiscalprodutobarra],
    }))

  submitting.value = true
  try {
    const novaNota = await store.criarDevolucao(codnotafiscal.value, {
      codpessoa: codpessoa.value,
      itens: itensParaEnviar,
    })

    $q.notify({
      type: 'positive',
      message: 'Devolução criada com sucesso!',
    })

    // Redireciona para a nova nota criada
    if (novaNota?.codnotafiscal) {
      router.push({
        name: 'nota-fiscal-view',
        params: { codnotafiscal: novaNota.codnotafiscal },
      })
    } else {
      router.push({ name: 'notas' })
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Erro ao criar devolução'
    $q.notify({
      type: 'negative',
      message,
    })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <q-btn
        flat
        dense
        round
        icon="arrow_back"
        :to="{ name: 'nota-fiscal-view', params: { codnotafiscal } }"
        class="q-mr-sm"
      />
      <div class="text-h5">Devolução</div>
    </div>
    <div style="max-width: 700px; margin: 0 auto">
      <q-form @submit.prevent="confirmarDevolucao">
        <q-card flat bordered class="full-height">
          <q-card-section class="bg-primary text-white q-mb-md">
            <div class="row items-center justify-between">
              <div class="text-h6">
                <q-icon name="list" size="1.5em" class="q-mr-sm" />
                Selecione os produtos para Devolução!
              </div>
            </div>
          </q-card-section>

          <!-- Alerta de pessoa obrigatória (apenas para consumidor genérico) -->
          <div>
            <q-banner
              v-if="isConsumidorGenerico"
              class="bg-red text-white q-mx-md q-mb-md rounded-borders"
            >
              Informe o cadastro da pessoa para fazer uma devolução!
            </q-banner>
          </div>

          <!-- Seleção de pessoa (apenas para consumidor genérico) -->
          <q-card-section v-if="isConsumidorGenerico" class="q-pt-none">
            <SelectPessoa v-model="codpessoa" label="Pessoa" dense />
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="row justify-end q-gutter-md q-mb-md">
              <q-btn flat color="primary" icon="done_all" @click="marcarTodos">
                <q-tooltip>Marcar Todos</q-tooltip>
              </q-btn>
              <q-btn flat color="negative" icon="remove_done" @click="marcarNenhum">
                <q-tooltip>Marcar Nenhum</q-tooltip>
              </q-btn>
            </div>

            <q-inner-loading :showing="loading" />

            <!-- Lista de itens -->
            <q-list separator>
              <q-item v-for="item in itens" :key="item.codnotafiscalprodutobarra" class="q-py-md">
                <q-item-section avatar>
                  <q-avatar square size="80px">
                    <q-img :src="getImagem(item)" :ratio="1" />
                  </q-avatar>
                  <div v-if="!item.produtoBarra?.imagem" class="text-caption text-grey text-center">
                    Imagem indisponível
                  </div>
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-medium">
                    {{ item.descricaoalternativa || item.produtoBarra?.descricao }}
                  </q-item-label>
                  <q-item-label caption>{{ item.produtoBarra?.barras || '-' }}</q-item-label>
                  <q-item-label caption class="text-grey-7">
                    {{ formatDecimal(item.quantidade, 3) }} de
                    {{ formatCurrency(item.valorunitario) }} =
                    {{ formatCurrency(item.valortotal) }}
                  </q-item-label>
                </q-item-section>

                <q-item-section class="col-3">
                  <q-input
                    v-model.number="quantidades[item.codnotafiscalprodutobarra]"
                    type="number"
                    label="Quantidade"
                    outlined
                    dense
                    :min="0"
                    step="0.001"
                    :max="parseFloat(item.quantidade)"
                    :rules="[
                      (val) => val >= 0 || 'Mínimo 0',
                      (val) => val <= parseFloat(item.quantidade) || `Máximo ${item.quantidade}`,
                    ]"
                    input-class="text-right"
                  ></q-input>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle1 text-right">
              Valor total da devolução:
              <span class="text-weight-bold text-primary">
                {{ formatCurrency(valorTotalDevolucao) }}
              </span>
            </div>
          </q-card-section>
        </q-card>

        <!-- FAB para Confirmar Devolução -->
        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn
            fab
            color="primary"
            icon="check"
            type="submit"
            :loading="submitting"
            :disable="!hasItemSelecionado || (isConsumidorGenerico && !codpessoa)"
          >
            <q-tooltip>Confirmar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
