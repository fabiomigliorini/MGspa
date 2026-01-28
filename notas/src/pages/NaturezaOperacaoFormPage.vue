<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  useNaturezaOperacaoStore,
  FINNFE_OPTIONS,
  OPERACAO_OPTIONS,
} from '../stores/naturezaOperacaoStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const naturezaOperacaoStore = useNaturezaOperacaoStore()

const loading = ref(false)
const isEditMode = computed(() => !!route.params.codnaturezaoperacao)
const naturezaOperacao = computed(() => naturezaOperacaoStore.currentNaturezaOperacao)

const form = ref({
  naturezaoperacao: '',
  codoperacao: null,
  emitida: false,
  finnfe: null,
  codnaturezaoperacaodevolucao: null,
  codtipotitulo: null,
  codcontacontabil: null,
  codestoquemovimentotipo: null,
  ibpt: false,
  estoque: false,
  financeiro: false,
  compra: false,
  venda: false,
  vendadevolucao: false,
  transferencia: false,
  preco: false,
  observacoesnf: '',
  mensagemprocom: '',
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
      await naturezaOperacaoStore.fetchNaturezaOperacao(route.params.codnaturezaoperacao)
      if (naturezaOperacao.value) {
        form.value = {
          naturezaoperacao: naturezaOperacao.value.naturezaoperacao || '',
          codoperacao: naturezaOperacao.value.codoperacao,
          emitida: naturezaOperacao.value.emitida || false,
          finnfe: naturezaOperacao.value.finnfe,
          codnaturezaoperacaodevolucao: naturezaOperacao.value.codnaturezaoperacaodevolucao,
          codtipotitulo: naturezaOperacao.value.codtipotitulo,
          codcontacontabil: naturezaOperacao.value.codcontacontabil,
          codestoquemovimentotipo: naturezaOperacao.value.codestoquemovimentotipo,
          ibpt: naturezaOperacao.value.ibpt || false,
          estoque: naturezaOperacao.value.estoque || false,
          financeiro: naturezaOperacao.value.financeiro || false,
          compra: naturezaOperacao.value.compra || false,
          venda: naturezaOperacao.value.venda || false,
          vendadevolucao: naturezaOperacao.value.vendadevolucao || false,
          transferencia: naturezaOperacao.value.transferencia || false,
          preco: naturezaOperacao.value.preco || false,
          observacoesnf: naturezaOperacao.value.observacoesnf || '',
          mensagemprocom: naturezaOperacao.value.mensagemprocom || '',
        }
      }
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar Natureza de Operação',
        caption: errorMessage,
        multiLine: true,
        timeout: 5000,
      })
      router.push({ name: 'natureza-operacao' })
    } finally {
      loading.value = false
    }
    return
  }
  // Reset form para criação
  form.value = {
    naturezaoperacao: '',
    codoperacao: null,
    emitida: false,
    finnfe: null,
    codnaturezaoperacaodevolucao: null,
    codtipotitulo: null,
    codcontacontabil: null,
    codestoquemovimentotipo: null,
    ibpt: false,
    estoque: false,
    financeiro: false,
    compra: false,
    venda: false,
    vendadevolucao: false,
    transferencia: false,
    preco: false,
    observacoesnf: '',
    mensagemprocom: '',
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
        await naturezaOperacaoStore.updateNaturezaOperacao(
          route.params.codnaturezaoperacao,
          form.value,
        )
        $q.notify({
          type: 'positive',
          message: 'Natureza de Operação atualizada com sucesso',
        })
      } else {
        await naturezaOperacaoStore.createNaturezaOperacao(form.value)
        $q.notify({
          type: 'positive',
          message: 'Natureza de Operação criada com sucesso',
        })
      }
      router.push({ name: 'natureza-operacao' })
    } catch (error) {
      const errorMessage = formatValidationErrors(error)
      $q.notify({
        type: 'negative',
        message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} Natureza de Operação`,
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
  router.push({ name: 'natureza-operacao' })
}

onMounted(() => {
  loadFormData()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 800px; margin: 0 auto">
      <q-form @submit.prevent="handleSubmit">
        <!-- Header -->
        <div class="row items-center q-mb-md">
          <q-btn flat dense round icon="arrow_back" @click="handleCancel" :disable="loading" />
          <div class="text-h5 q-ml-sm">
            <template v-if="isEditMode"
              >Alterar Natureza de Operação #{{ route.params.codnaturezaoperacao }}</template
            >
            <template v-else>Nova Natureza de Operação</template>
          </div>
        </div>

        <!-- Card 1 - Identificação -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">
            <q-icon name="badge" class="q-mr-sm" />
            Identificação
          </div>
          <q-card-section>
            <!-- Natureza de Operação -->
            <q-input
              v-model="form.naturezaoperacao"
              outlined
              autofocus
              label="Natureza de Operação *"
              maxlength="50"
              counter
              placeholder="Ex: Venda"
              :disable="loading"
              :rules="[
                (val) => !!val || 'Natureza de Operação é obrigatória',
                (val) => val.length <= 50 || 'Máximo 50 caracteres',
              ]"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="swap_horiz" />
              </template>
            </q-input>

            <div class="row q-col-gutter-md">
              <!-- Operação -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.codoperacao"
                  :options="OPERACAO_OPTIONS"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                  outlined
                  label="Operação *"
                  :disable="loading"
                  :rules="[(val) => val !== null || 'Operação é obrigatória']"
                >
                  <template v-slot:prepend>
                    <q-icon name="compare_arrows" />
                  </template>
                </q-select>
              </div>

              <!-- Finalidade NFe -->
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.finnfe"
                  :options="FINNFE_OPTIONS"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Finalidade NFe"
                  :disable="loading"
                >
                  <template v-slot:prepend>
                    <q-icon name="description" />
                  </template>
                </q-select>
              </div>
            </div>

            <!-- Toggle Emitida -->
            <div class="q-mt-md">
              <q-toggle v-model="form.emitida" label="Nossa Emissão" :disable="loading" />
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 2 - Relacionamentos -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">
            <q-icon name="link" class="q-mr-sm" />
            Relacionamentos
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <!-- Natureza de Devolução -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.codnaturezaoperacaodevolucao"
                  outlined
                  type="number"
                  label="Cód. Natureza Devolução"
                  :disable="loading"
                  hint="Código da natureza de operação para devolução"
                >
                  <template v-slot:prepend>
                    <q-icon name="undo" />
                  </template>
                </q-input>
              </div>

              <!-- Tipo Título -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.codtipotitulo"
                  outlined
                  type="number"
                  label="Cód. Tipo Título"
                  :disable="loading"
                  hint="Código do tipo de título financeiro"
                >
                  <template v-slot:prepend>
                    <q-icon name="receipt" />
                  </template>
                </q-input>
              </div>

              <!-- Conta Contábil -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.codcontacontabil"
                  outlined
                  type="number"
                  label="Cód. Conta Contábil"
                  :disable="loading"
                  hint="Código da conta contábil"
                >
                  <template v-slot:prepend>
                    <q-icon name="account_balance" />
                  </template>
                </q-input>
              </div>

              <!-- Tipo Movimento Estoque -->
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.codestoquemovimentotipo"
                  outlined
                  type="number"
                  label="Cód. Tipo Mov. Estoque"
                  :disable="loading"
                  hint="Código do tipo de movimento de estoque"
                >
                  <template v-slot:prepend>
                    <q-icon name="inventory" />
                  </template>
                </q-input>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 3 - Configurações -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">
            <q-icon name="settings" class="q-mr-sm" />
            Configurações
          </div>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.ibpt" label="Usa IBPT" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.estoque" label="Mov. Estoque" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.financeiro" label="Mov. Financeiro" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.compra" label="É Compra" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.venda" label="É Venda" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.vendadevolucao" label="Dev. Venda" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.transferencia" label="Transferência" :disable="loading" />
              </div>
              <div class="col-6 col-sm-4 col-md-3">
                <q-toggle v-model="form.preco" label="Atualiza Preço" :disable="loading" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Card 4 - Observações -->
        <q-card class="q-mb-md">
          <div class="text-subtitle1 text-white bg-primary q-pa-sm">
            <q-icon name="notes" class="q-mr-sm" />
            Observações
          </div>
          <q-card-section>
            <!-- Observações NF -->
            <q-input
              v-model="form.observacoesnf"
              outlined
              type="textarea"
              label="Observações para Nota Fiscal"
              :disable="loading"
              rows="3"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="note" />
              </template>
            </q-input>

            <!-- Mensagem PROCOM -->
            <q-input
              v-model="form.mensagemprocom"
              outlined
              type="textarea"
              label="Mensagem PROCOM"
              :disable="loading"
              rows="3"
            >
              <template v-slot:prepend>
                <q-icon name="message" />
              </template>
            </q-input>
          </q-card-section>
        </q-card>

        <!-- Auditoria -->
        <div v-if="isEditMode && naturezaOperacao" class="text-caption text-grey q-mt-md">
          <span v-if="naturezaOperacao.usuarioAlteracao">
            Alterado em {{ new Date(naturezaOperacao.alteracao).toLocaleString('pt-BR') }} por
            {{ naturezaOperacao.usuarioAlteracao.usuario }}
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
