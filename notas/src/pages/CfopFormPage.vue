<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useCfopStore } from '../stores/cfopStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const cfopStore = useCfopStore()

const loading = ref(false)
const isEditMode = computed(() => !!route.params.codcfop)
const cfop = computed(() => cfopStore.currentCfop)

const form = ref({
  codcfop: '',
  cfop: '',
})

const formatValidationErrors = (error) => {
  if (error.response?.data?.errors) {
    const errors = error.response.data.errors
    const messages = []
    for (const fieldErrors of Object.values(errors)) {
      messages.push(...fieldErrors)
    }
    return messages.join('\n')
  }
  return error.response?.data?.message || error.message
}

const loadFormData = async () => {
  if (isEditMode.value) {
    loading.value = true
    try {
      await cfopStore.fetchCfop(route.params.codcfop)
      if (cfop.value) {
        form.value = {
          cfop: cfop.value.descricao || '',
        }
      }
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar CFOP',
        caption: errorMessage,
        multiLine: true,
        timeout: 5000,
      })
      router.push({ name: 'cfop' })
    } finally {
      loading.value = false
    }
    return
  }
  form.value = {
    codcfop: '',
    cfop: '',
  }
}

const handleSubmit = async () => {
  $q.dialog({
    title: 'Confirmação',
    message: 'Tem certeza que deseja salvar?',
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Salvar',
      color: 'primary',
    },
    persistent: true,
  }).onOk(async () => {
    loading.value = true
    try {
      if (isEditMode.value) {
        await cfopStore.updateCfop(route.params.codcfop, form.value)
        $q.notify({
          type: 'positive',
          message: 'CFOP atualizado com sucesso',
        })
      } else {
        await cfopStore.createCfop(form.value)
        $q.notify({
          type: 'positive',
          message: 'CFOP criado com sucesso',
        })
      }
      router.push({ name: 'cfop' })
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} CFOP`,
        caption: errorMessage,
        multiLine: true,
        timeout: 5000,
      })
    } finally {
      loading.value = false
    }
  })
}

const handleCancel = () => {
  router.push({ name: 'cfop' })
}

onMounted(() => {
  loadFormData()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 700px; margin: 0 auto">
      <q-form @submit.prevent="handleSubmit">
        <!-- Header -->
        <div class="row items-center q-mb-md">
        <q-btn flat dense round icon="arrow_back" @click="handleCancel" :disable="loading" />
        <div class="text-h5 q-ml-sm">
          <template v-if="isEditMode">Alterar CFOP {{ route.params.codcfop }}</template>
          <template v-else>Novo CFOP</template>
        </div>
      </div>

      <!-- Codigo CFOP (apenas para criacao) -->
      <q-card v-if="!isEditMode" class="q-mb-md">
        <div class="text-subtitle1 text-white bg-primary q-pa-sm">Código CFOP</div>
        <q-card-section>
          <q-input
            v-model="form.codcfop"
            outlined
            autofocus
            mask="####"
            placeholder="Ex: 5102"
            :disable="loading"
            :rules="[
              (val) => !!val || 'Código CFOP é obrigatório',
              (val) => /^\d{4}$/.test(val) || 'Código CFOP deve ter 4 dígitos',
              (val) => (parseInt(val) >= 1000 && parseInt(val) <= 9999) || 'CFOP deve estar entre 1000 e 9999',
            ]"
          />
        </q-card-section>
      </q-card>

      <!-- Descricao -->
      <q-card class="q-mb-md">
        <div class="text-subtitle1 text-white bg-primary q-pa-sm">Descrição</div>
        <q-card-section>
          <q-input
            v-model="form.cfop"
            type="textarea"
            rows="6"
            outlined
            :autofocus="isEditMode"
            maxlength="500"
            placeholder="Ex: Venda de Mercadoria Adquirida ou Recebida de Terceiros"
            :disable="loading"
            :rules="[(val) => !!val || 'Descrição é obrigatória']"
          />
        </q-card-section>
      </q-card>

      <!-- Auditoria -->
      <div v-if="isEditMode && cfop" class="text-caption text-grey q-mt-md">
        <span v-if="cfop.usuarioAlteracao">
          Alterado em {{ new Date(cfop.alteracao).toLocaleString('pt-BR') }} por
          {{ cfop.usuarioAlteracao.usuario }}
        </span>
      </div>

      <!-- FAB Salvar -->
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn
          fab
          color="primary"
          icon="save"
          type="submit"
          :loading="loading"
        >
          <q-tooltip>Salvar</q-tooltip>
        </q-btn>
      </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
