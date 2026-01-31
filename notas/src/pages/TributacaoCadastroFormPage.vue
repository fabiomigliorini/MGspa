<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useTributacaoCadastroStore } from '../stores/tributacaoCadastroStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const tributacaoStore = useTributacaoCadastroStore()

const loading = ref(false)
const isEditMode = computed(() => !!route.params.codtributacao)
const tributacao = computed(() => tributacaoStore.currentTributacao)

const form = ref({
  tributacao: '',
  aliquotaicmsecf: null,
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
      await tributacaoStore.fetchTributacao(route.params.codtributacao)
      if (tributacao.value) {
        form.value = {
          tributacao: tributacao.value.tributacao || '',
          aliquotaicmsecf: tributacao.value.aliquotaicmsecf,
        }
      }
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar Tributacao',
        caption: errorMessage,
        multiLine: true,
        timeout: 5000,
      })
      router.push({ name: 'tributacao-cadastro' })
    } finally {
      loading.value = false
    }
    return
  }
  form.value = {
    tributacao: '',
    aliquotaicmsecf: null,
  }
}

const handleSubmit = async () => {
  $q.dialog({
    title: 'Confirmacao',
    message: 'Tem certeza que deseja salvar?',
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Salvar',
      color: 'primary',
    },
  }).onOk(async () => {
    loading.value = true
    try {
      if (isEditMode.value) {
        await tributacaoStore.updateTributacao(route.params.codtributacao, form.value)
        $q.notify({
          type: 'positive',
          message: 'Tributacao atualizada com sucesso',
        })
      } else {
        await tributacaoStore.createTributacao(form.value)
        $q.notify({
          type: 'positive',
          message: 'Tributacao criada com sucesso',
        })
      }
      router.push({ name: 'tributacao-cadastro' })
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} Tributacao`,
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
  router.push({ name: 'tributacao-cadastro' })
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
            <template v-if="isEditMode">
              Alterar Tributacao #{{ route.params.codtributacao }}
            </template>
            <template v-else>Nova Tributacao</template>
          </div>
        </div>

        <!-- Nome da Tributacao -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">Tributacao</div>
          <q-card-section>
            <q-input
              v-model="form.tributacao"
              outlined
              autofocus
              maxlength="100"
              counter
              placeholder="Ex: Tributado"
              :disable="loading"
              :rules="[
                (val) => !!val || 'Tributacao e obrigatoria',
                (val) => val.length <= 100 || 'Maximo 100 caracteres',
              ]"
            />
          </q-card-section>
        </q-card>

        <!-- Aliquota ICMS ECF -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">Aliquota ICMS ECF (%)</div>
          <q-card-section>
            <q-input
              v-model="form.aliquotaicmsecf"
              outlined
              mask="##.##"
              fill-mask="0"
              reverse-fill-mask
              placeholder="00.00"
              suffix="%"
              inputmode="numeric"
              :disable="loading"
              :rules="[
                (val) =>
                  val === null ||
                  val === '' ||
                  (parseFloat(val) >= 0 && parseFloat(val) <= 100) ||
                  'Aliquota deve estar entre 0 e 100',
              ]"
            />
          </q-card-section>
        </q-card>

        <!-- Auditoria -->
        <div v-if="isEditMode && tributacao" class="text-caption text-grey q-mt-md">
          <span v-if="tributacao.usuarioAlteracao">
            Alterado em {{ new Date(tributacao.alteracao).toLocaleString('pt-BR') }} por
            {{ tributacao.usuarioAlteracao.usuario }}
          </span>
        </div>

        <!-- FAB Salvar -->
        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab color="primary" icon="save" type="submit" :loading="loading">
            <q-tooltip>Salvar</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-form>
    </div>
  </q-page>
</template>
