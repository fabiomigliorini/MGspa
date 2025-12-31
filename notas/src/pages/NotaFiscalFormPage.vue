<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import SelectNaturezaOperacao from '../components/selects/SelectNaturezaOperacao.vue'
import SelectFilial from '../components/selects/SelectFilial.vue'
import SelectEstoqueLocal from '../components/selects/SelectEstoqueLocal.vue'
import SelectPessoa from '../components/selects/SelectPessoa.vue'
import SelectEstado from '../components/selects/SelectEstado.vue'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Funções de conversão de data/hora
// Converte ISO datetime (YYYY-MM-DDTHH:MM:SS) para DD/MM/YYYY HH:mm:ss
const isoToFormDateTime = (isoDateTime) => {
  if (!isoDateTime) return null
  const dateObj = new Date(isoDateTime)
  const day = String(dateObj.getDate()).padStart(2, '0')
  const month = String(dateObj.getMonth() + 1).padStart(2, '0')
  const year = dateObj.getFullYear()
  const hours = String(dateObj.getHours()).padStart(2, '0')
  const minutes = String(dateObj.getMinutes()).padStart(2, '0')
  const seconds = String(dateObj.getSeconds()).padStart(2, '0')
  return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`
}

// Converte DD/MM/YYYY HH:mm:ss para ISO datetime (YYYY-MM-DDTHH:MM:SS)
const formDateTimeToIso = (formDateTime) => {
  if (!formDateTime || formDateTime.length !== 19) return null
  const [datePart, timePart] = formDateTime.split(' ')
  const [day, month, year] = datePart.split('/')
  return `${year}-${month}-${day}T${timePart}`
}

// State
const loading = ref(false)
const isEditMode = computed(() => !!route.params.codnotafiscal)
const nota = computed(() => notaFiscalStore.currentNota)
const notaBloqueada = computed(() => {
  if (!isEditMode.value || !nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

const form = ref({
  codfilial: null,
  codestoquelocal: null,
  codpessoa: null,
  codnaturezaoperacao: null,
  codoperacao: null,
  modelo: '55',
  serie: '1',
  numero: null,
  emissao: isoToFormDateTime(new Date().toISOString()),
  saida: isoToFormDateTime(new Date().toISOString()),
  cpf: null,
  valordesconto: 0,
  valorfrete: 0,
  valorseguro: 0,
  valoroutras: 0,
  frete: 9,
  codpessoatransportador: null,
  placa: null,
  codestadoplaca: null,
  volumes: null,
  volumesespecie: null,
  volumesmarca: null,
  volumesnumero: null,
  pesobruto: null,
  pesoliquido: null,
  observacoes: null,
  emitida: false,
})

// Options (serão carregadas via API)
const operacoesOptions = ref([])

const modeloOptions = [
  { label: 'NF-e (55)', value: '55' },
  { label: 'NFC-e (65)', value: '65' },
]

const freteOptions = [
  { label: '0 - Por conta do emitente', value: 0 },
  { label: '1 - Por conta do destinatário', value: 1 },
  { label: '2 - Por conta de terceiros', value: 2 },
  { label: '3 - Transporte próprio por conta do remetente', value: 3 },
  { label: '4 - Transporte próprio por conta do destinatário', value: 4 },
  { label: '9 - Sem frete', value: 9 },
]

// Methods
const loadFormData = async () => {
  if (isEditMode.value) {
    loading.value = true
    try {
      // A verificação de cache é feita dentro do fetchNota (usa cache se o codnotafiscal for o mesmo)
      await notaFiscalStore.fetchNota(route.params.codnotafiscal)

      if (nota.value) {
        form.value = {
          codfilial: nota.value.codfilial,
          codestoquelocal: nota.value.codestoquelocal,
          codpessoa: nota.value.codpessoa,
          codnaturezaoperacao: nota.value.codnaturezaoperacao,
          codoperacao: nota.value.codoperacao,
          modelo: nota.value.modelo,
          serie: nota.value.serie,
          numero: nota.value.numero,
          emissao: isoToFormDateTime(nota.value.emissao),
          saida: isoToFormDateTime(nota.value.saida),
          cpf: nota.value.cpf,
          valordesconto: nota.value.valordesconto || 0,
          valorfrete: nota.value.valorfrete || 0,
          valorseguro: nota.value.valorseguro || 0,
          valoroutras: nota.value.valoroutras || 0,
          frete: nota.value.frete ?? 9,
          codpessoatransportador: nota.value.codpessoatransportador,
          placa: nota.value.placa,
          codestadoplaca: nota.value.codestadoplaca,
          volumes: nota.value.volumes,
          volumesespecie: nota.value.volumesespecie,
          volumesmarca: nota.value.volumesmarca,
          volumesnumero: nota.value.volumesnumero,
          pesobruto: nota.value.pesobruto,
          pesoliquido: nota.value.pesoliquido,
          observacoes: nota.value.observacoes,
          emitida: nota.value.emitida,
        }
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar nota fiscal',
        caption: error.response?.data?.message || error.message,
      })
      router.push({ name: 'notas' })
    } finally {
      loading.value = false
    }
  }
}

const loadOptions = async () => {
  // TODO: Implementar carregamento das opções via API
  // Por enquanto, deixar vazio para não bloquear o desenvolvimento
  operacoesOptions.value = [
    { label: 'Entrada', value: 1 },
    { label: 'Saída', value: 2 },
  ]
}

const handleSubmit = async () => {
  loading.value = true
  try {
    // Prepara os dados convertendo as datas/horas para formato ISO
    const dataToSend = {
      ...form.value,
      emissao: formDateTimeToIso(form.value.emissao),
      saida: formDateTimeToIso(form.value.saida),
    }

    if (isEditMode.value) {
      await notaFiscalStore.updateNota(route.params.codnotafiscal, dataToSend)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal atualizada com sucesso',
      })
    } else {
      const created = await notaFiscalStore.createNota(dataToSend)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal criada com sucesso',
      })
      router.push({ name: 'nota-fiscal-view', params: { codnotafiscal: created.codnotafiscal } })
      return
    }

    router.push({ name: 'nota-fiscal-view', params: { codnotafiscal: route.params.codnotafiscal } })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} nota fiscal`,
      caption: error.response?.data?.message || error.message,
    })
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  if (isEditMode.value) {
    router.push({ name: 'nota-fiscal-view', params: { codnotafiscal: route.params.codnotafiscal } })
  } else {
    router.push({ name: 'notas' })
  }
}

const handleClearPessoa = () => {
  form.value.codpessoa = 1 // Define como Consumidor ao invés de null
}

// Lifecycle
onMounted(() => {
  loadFormData()
  loadOptions()
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <div style="max-width: 600px; margin: 0 auto;">
        <!-- Header -->
        <div class="row items-center q-mb-md">
          <div class="text-h5">
            <q-btn flat dense round icon="arrow_back" @click="handleCancel" class="q-mr-sm" size="0.8em"
              :disable="loading" />
            {{ isEditMode ? 'Editar Nota Fiscal' : 'Nova Nota Fiscal' }}
          </div>
          <q-space />
          <q-btn flat dense color="grey-7" icon="close" @click="handleCancel" :disable="loading" class="q-mr-sm">
            <q-tooltip>Cancelar</q-tooltip>
          </q-btn>
          <q-btn unelevated color="primary" icon="save" label="Salvar" type="submit" :loading="loading"
            :disable="notaBloqueada" />
        </div>

        <q-banner v-if="notaBloqueada && nota" class="bg-warning text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="lock" />
          </template>
          Esta nota está {{ nota.status }} e não pode ser editada.
        </q-banner>

        <!-- Dados Principais -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="description" size="sm" class="q-mr-xs" />
              DADOS PRINCIPAIS
            </div>

            <div class="row q-col-gutter-md">
              <!-- Filial -->
              <div class="col-12 col-sm-6">
                <SelectFilial v-model="form.codfilial" label="Filial *" :disable="notaBloqueada" />
              </div>

              <!-- Local de Estoque -->
              <div class="col-12 col-sm-6">
                <SelectEstoqueLocal v-model="form.codestoquelocal" :codfilial="form.codfilial" label="Local de Estoque"
                  :disable="notaBloqueada" />
              </div>

              <!-- Modelo -->
              <div class="col-12 col-sm-4">
                <q-select v-model="form.modelo" :options="modeloOptions" label="Modelo *" outlined emit-value
                  map-options :rules="[(val) => !!val || 'Campo obrigatório']"
                  :disable="notaBloqueada || (nota && nota.numero != 0)" />
              </div>

              <!-- Série -->
              <div class="col-12 col-sm-4">
                <q-input v-model="form.serie" label="Série *" outlined :rules="[(val) => !!val || 'Campo obrigatório']"
                  :disable="notaBloqueada || (nota && nota.numero != 0)" />
              </div>

              <!-- Número -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.numero" label="Número" outlined type="number"
                  hint="Deixe em branco para gerar automaticamente" :disable="notaBloqueada || isEditMode"
                  input-class="text-right" />
              </div>

              <!-- Data Emissão -->
              <div class="col-12 col-sm-6">
                <q-input v-model="form.emissao" label="Data/Hora Emissão *" outlined placeholder="DD/MM/YYYY HH:mm:ss"
                  mask="##/##/#### ##:##:##" :rules="[(val) => !!val || 'Campo obrigatório']" :disable="notaBloqueada"
                  input-class="text-center">
                  <template v-slot:append>
                    <q-icon name="event" class="cursor-pointer">
                      <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                        <q-date v-model="form.emissao" mask="DD/MM/YYYY HH:mm:ss" default-view="Calendar">
                          <div class="row items-center justify-end">
                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                          </div>
                        </q-date>
                      </q-popup-proxy>
                    </q-icon>
                    <q-icon name="access_time" class="cursor-pointer q-ml-xs">
                      <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                        <q-time v-model="form.emissao" mask="DD/MM/YYYY HH:mm:ss" with-seconds>
                          <div class="row items-center justify-end">
                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                          </div>
                        </q-time>
                      </q-popup-proxy>
                    </q-icon>
                  </template>
                </q-input>
              </div>

              <!-- Data Saída -->
              <div class="col-12 col-sm-6">
                <q-input v-model="form.saida" label="Data/Hora Saída *" outlined placeholder="DD/MM/YYYY HH:mm:ss"
                  mask="##/##/#### ##:##:##" :rules="[(val) => !!val || 'Campo obrigatório']" :disable="notaBloqueada"
                  input-class="text-center">
                  <template v-slot:append>
                    <q-icon name="event" class="cursor-pointer">
                      <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                        <q-date v-model="form.saida" mask="DD/MM/YYYY HH:mm:ss" default-view="Calendar">
                          <div class="row items-center justify-end">
                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                          </div>
                        </q-date>
                      </q-popup-proxy>
                    </q-icon>
                    <q-icon name="access_time" class="cursor-pointer q-ml-xs">
                      <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                        <q-time v-model="form.saida" mask="DD/MM/YYYY HH:mm:ss" with-seconds>
                          <div class="row items-center justify-end">
                            <q-btn v-close-popup label="Fechar" color="primary" flat />
                          </div>
                        </q-time>
                      </q-popup-proxy>
                    </q-icon>
                  </template>
                </q-input>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Destinatário -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="person" size="sm" class="q-mr-xs" />
              DESTINATÁRIO / REMETENTE
            </div>

            <div class="row q-col-gutter-md">
              <!-- Cliente/Fornecedor -->
              <div class="col-12">
                <SelectPessoa
                  v-model="form.codpessoa"
                  label="Cliente/Fornecedor *"
                  :disable="notaBloqueada"
                  @clear="handleClearPessoa"
                />
              </div>

              <!-- CPF na Nota (opcional) - Apenas para Consumidor -->
              <div v-if="form.codpessoa === 1" class="col-12 col-sm-6">
                <q-input v-model="form.cpf" label="CPF na Nota (Consumidor)" outlined mask="###.###.###-##"
                  hint="Usado para Nota Paulista, etc" :disable="notaBloqueada" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Operação Fiscal -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="compare_arrows" size="sm" class="q-mr-xs" />
              OPERAÇÃO FISCAL
            </div>

            <div class="row q-col-gutter-md">
              <!-- Natureza de Operação -->
              <div class="col-12 col-sm-6">
                <SelectNaturezaOperacao v-model="form.codnaturezaoperacao" label="Natureza de Operação *"
                  :disable="notaBloqueada" />
              </div>

              <!-- Operação -->
              <div class="col-12 col-sm-6">
                <q-select v-model="form.codoperacao" :options="operacoesOptions" label="Operação *" outlined emit-value
                  map-options :rules="[(val) => !!val || 'Campo obrigatório']" :disable="notaBloqueada" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Valores -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="payments" size="sm" class="q-mr-xs" />
              VALORES ADICIONAIS
            </div>

            <div class="row q-col-gutter-md">
              <!-- Desconto -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model.number="form.valordesconto" label="Desconto" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Frete -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model.number="form.valorfrete" label="Frete" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Seguro -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model.number="form.valorseguro" label="Seguro" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Outras Despesas -->
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model.number="form.valoroutras" label="Outras Despesas" outlined type="number" step="0.01"
                  min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Transporte -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="local_shipping" size="sm" class="q-mr-xs" />
              TRANSPORTE
            </div>

            <div class="row q-col-gutter-md">
              <!-- Modalidade Frete -->
              <div class="col-12 col-sm-6">
                <q-select v-model="form.frete" :options="freteOptions" label="Modalidade do Frete" outlined emit-value
                  map-options :disable="notaBloqueada" />
              </div>

              <!-- Transportador -->
              <div class="col-12 col-sm-6">
                <SelectPessoa v-model="form.codpessoatransportador" label="Transportador" :disable="notaBloqueada" />
              </div>

              <!-- Placa -->
              <div class="col-12 col-sm-8">
                <q-input
                  v-model="form.placa"
                  label="Placa do Veículo"
                  outlined
                  maxlength="7"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- UF Placa -->
              <div class="col-12 col-sm-4">
                <SelectEstado
                  v-model="form.codestadoplaca"
                  label="UF Placa"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Volumes -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.volumes"
                  label="Volumes"
                  outlined
                  type="number"
                  min="0"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Espécie dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="form.volumesespecie"
                  label="Espécie"
                  outlined
                  maxlength="60"
                  hint="Ex: Caixa, Pacote, Fardo"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Marca dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="form.volumesmarca"
                  label="Marca"
                  outlined
                  maxlength="60"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Numeração dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="form.volumesnumero"
                  label="Numeração"
                  outlined
                  maxlength="60"
                  :disable="notaBloqueada"
                />
              </div>

              <!-- Peso Bruto -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.pesobruto"
                  label="Peso Bruto (Kg)"
                  outlined
                  type="number"
                  step="0.001"
                  min="0"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>

              <!-- Peso Líquido -->
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="form.pesoliquido"
                  label="Peso Líquido (Kg)"
                  outlined
                  type="number"
                  step="0.001"
                  min="0"
                  :disable="notaBloqueada"
                  input-class="text-right"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Observações -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="notes" size="sm" class="q-mr-xs" />
              OBSERVAÇÕES
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input v-model="form.observacoes" label="Observações / Informações Complementares" outlined
                  type="textarea" rows="4" :disable="notaBloqueada" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </q-form>
  </q-page>
</template>
