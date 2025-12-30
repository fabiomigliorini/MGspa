<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

const loading = ref(false)
const loadingItem = ref(false)
const item = ref(null)
const cfopOptions = ref([])

const form = ref({
  codcfop: null
  // TODO: Adicionar campos de tributos
})

const loadItem = async () => {
  loadingItem.value = true
  try {
    const itemData = await notaFiscalStore.fetchItem(
      route.params.codnotafiscal,
      route.params.codnotafiscalprodutobarra
    )
    item.value = itemData
    form.value.codcfop = itemData.codcfop
  } catch {
    $q.notify({ type: 'negative', message: 'Erro ao carregar item' })
  } finally {
    loadingItem.value = false
  }
}

const filterCfop = (val, update) => {
  update(() => { cfopOptions.value = [] })
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await notaFiscalStore.updateItem(
      route.params.codnotafiscal,
      route.params.codnotafiscalprodutobarra,
      form.value
    )
    $q.notify({ type: 'positive', message: 'Tributos atualizados com sucesso' })
    router.push({ name: 'nota-fiscal-view', params: { codnotafiscal: route.params.codnotafiscal } })
  } catch {
    $q.notify({ type: 'negative', message: 'Erro ao atualizar tributos' })
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  router.push({ name: 'nota-fiscal-view', params: { codnotafiscal: route.params.codnotafiscal } })
}

onMounted(() => { loadItem() })
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <div class="col">
          <div class="text-h5">Editar Tributos Legados</div>
          <div v-if="item" class="text-caption text-grey-7">
            {{ item.produtoBarra?.descricao }}
          </div>
        </div>
        <div class="col-auto">
          <q-btn flat icon="close" label="Cancelar" @click="handleCancel" :disable="loading" />
          <q-btn color="primary" icon="save" label="Salvar" type="submit" :loading="loading" />
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loadingItem" class="row justify-center q-py-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <!-- Formulário -->
      <div v-else-if="item">
        <!-- CFOP -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">CFOP - Código Fiscal de Operação</div>
            <q-select
              v-model="form.codcfop"
              :options="cfopOptions"
              label="CFOP *"
              outlined
              use-input
              input-debounce="300"
              emit-value
              map-options
              @filter="filterCfop"
              :rules="[val => !!val || 'Campo obrigatório']"
            />
          </q-card-section>
        </q-card>

        <!-- ICMS -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">ICMS</div>
            <!-- TODO: Implementar campos ICMS -->
            <div class="text-caption text-grey-7">Campos de ICMS (CST, BC, Alíquota, Valor, etc)</div>
          </q-card-section>
        </q-card>

        <!-- IPI -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">IPI</div>
            <!-- TODO: Implementar campos IPI -->
            <div class="text-caption text-grey-7">Campos de IPI (CST, BC, Alíquota, Valor, etc)</div>
          </q-card-section>
        </q-card>

        <!-- PIS -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">PIS</div>
            <!-- TODO: Implementar campos PIS -->
            <div class="text-caption text-grey-7">Campos de PIS (CST, BC, Alíquota, Valor, etc)</div>
          </q-card-section>
        </q-card>

        <!-- COFINS -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">COFINS</div>
            <!-- TODO: Implementar campos COFINS -->
            <div class="text-caption text-grey-7">Campos de COFINS (CST, BC, Alíquota, Valor, etc)</div>
          </q-card-section>
        </q-card>

        <!-- Outros Tributos (IRPJ, CSLL, FUNRURAL, etc) -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Outros Tributos Federais</div>
            <!-- TODO: Implementar campos outros tributos -->
            <div class="text-caption text-grey-7">IRPJ, CSLL, FUNRURAL, etc</div>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
</template>
