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

const form = ref({
  cbsbase: 0,
  cbsaliquota: 0,
  cbsvalor: 0,
  ibsbase: 0,
  ibsaliquota: 0,
  ibsvalor: 0,
  isbase: 0,
  isaliquota: 0,
  isvalor: 0
})

const loadItem = async () => {
  loadingItem.value = true
  try {
    const itemData = await notaFiscalStore.fetchItem(
      route.params.codnotafiscal,
      route.params.codnotafiscalprodutobarra
    )
    item.value = itemData

    // TODO: Carregar dados de tributos da reforma da tabela tblnotafiscalitemtributo
    form.value = {
      cbsbase: itemData.tributos?.cbsbase || 0,
      cbsaliquota: itemData.tributos?.cbsaliquota || 0,
      cbsvalor: itemData.tributos?.cbsvalor || 0,
      ibsbase: itemData.tributos?.ibsbase || 0,
      ibsaliquota: itemData.tributos?.ibsaliquota || 0,
      ibsvalor: itemData.tributos?.ibsvalor || 0,
      isbase: itemData.tributos?.isbase || 0,
      isaliquota: itemData.tributos?.isaliquota || 0,
      isvalor: itemData.tributos?.isvalor || 0
    }
  } catch {
    $q.notify({ type: 'negative', message: 'Erro ao carregar item' })
  } finally {
    loadingItem.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    // TODO: Implementar atualização dos tributos da reforma
    $q.notify({ type: 'positive', message: 'Tributos da reforma atualizados com sucesso' })
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
          <div class="text-h5">Editar Tributos da Reforma (CBS/IBS/IS)</div>
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
        <q-banner class="bg-blue-1 q-mb-md">
          <template v-slot:avatar>
            <q-icon name="info" color="primary" />
          </template>
          <div class="text-body2">
            Tributos da Reforma Tributária armazenados na tabela <strong>tblnotafiscalitemtributo</strong>
          </div>
        </q-banner>

        <!-- CBS - Contribuição sobre Bens e Serviços -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">CBS - Contribuição sobre Bens e Serviços</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.cbsbase"
                  label="Base de Cálculo CBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.cbsaliquota"
                  label="Alíquota CBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  suffix="%"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.cbsvalor"
                  label="Valor CBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- IBS - Imposto sobre Bens e Serviços -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">IBS - Imposto sobre Bens e Serviços</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.ibsbase"
                  label="Base de Cálculo IBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.ibsaliquota"
                  label="Alíquota IBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  suffix="%"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.ibsvalor"
                  label="Valor IBS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- IS - Imposto Seletivo -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">IS - Imposto Seletivo</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.isbase"
                  label="Base de Cálculo IS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.isaliquota"
                  label="Alíquota IS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  suffix="%"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.isvalor"
                  label="Valor IS"
                  outlined
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="R$"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
</template>
