<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import SelectNaturezaOperacao from '../components/selects/SelectNaturezaOperacao.vue'
import SelectFilial from '../components/selects/SelectFilial.vue'
import SelectEstoqueLocal from '../components/selects/SelectEstoqueLocal.vue'

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
  return ['Autorizada', 'Cancelada', 'Inutilizada'].includes(nota.value.status)
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
  pesobruto: null,
  pesoliquido: null,
  observacoes: null,
  emitida: false,
})

// Options (serão carregadas via API)
const pessoasOptions = ref([])
const operacoesOptions = ref([])
const transportadoresOptions = ref([])
const estadosOptions = ref([])

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
      // A verificação de cache é feita dentro do fetchNota
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
  estadosOptions.value = []
}

const filterPessoas = (val, update) => {
  // TODO: Implementar busca de pessoas via API
  update(() => {
    pessoasOptions.value = []
  })
}

const filterTransportadores = (val, update) => {
  // TODO: Implementar busca de transportadores via API
  update(() => {
    transportadoresOptions.value = []
  })
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

// Lifecycle
onMounted(() => {
  loadFormData()
  loadOptions()
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <div class="col">
          <div class="text-h5">
            {{ isEditMode ? 'Editar Nota Fiscal' : 'Nova Nota Fiscal' }}
          </div>
          <div v-if="isEditMode && nota" class="text-caption text-grey-7">
            {{ nota.modelo }} Nº {{ nota.numero }} - {{ nota.status }}
          </div>
        </div>
        <div class="col-auto">
          <q-btn flat icon="close" label="Cancelar" @click="handleCancel" :disable="loading" />
          <q-btn
            color="primary"
            icon="save"
            label="Salvar"
            type="submit"
            :loading="loading"
            :disable="notaBloqueada"
          />
        </div>
      </div>

      <q-banner v-if="notaBloqueada" class="bg-warning text-white q-mb-md">
        <template v-slot:avatar>
          <q-icon name="lock" />
        </template>
        Esta nota está {{ nota.status }} e não pode ser editada.
      </q-banner>

      <!-- Dados Principais -->
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Dados Principais</div>

          <div class="row q-col-gutter-md">
            <!-- Filial -->
            <div class="col-12 col-sm-6">
              <SelectFilial v-model="form.codfilial" label="Filial *" :disable="notaBloqueada" />
            </div>

            <!-- Local de Estoque -->
            <div class="col-12 col-sm-6">
              <SelectEstoqueLocal
                v-model="form.codestoquelocal"
                :codfilial="form.codfilial"
                label="Local de Estoque"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Modelo -->
            <div class="col-12 col-sm-4">
              <q-select
                v-model="form.modelo"
                :options="modeloOptions"
                label="Modelo *"
                outlined
                emit-value
                map-options
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada || isEditMode"
              />
            </div>

            <!-- Série -->
            <div class="col-12 col-sm-4">
              <q-input
                v-model="form.serie"
                label="Série *"
                outlined
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada || isEditMode"
              />
            </div>

            <!-- Número -->
            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="form.numero"
                label="Número"
                outlined
                type="number"
                hint="Deixe em branco para gerar automaticamente"
                :disable="notaBloqueada || isEditMode"
              />
            </div>

            <!-- Data Emissão -->
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.emissao"
                label="Data/Hora Emissão *"
                outlined
                placeholder="DD/MM/YYYY HH:mm:ss"
                mask="##/##/#### ##:##:##"
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date
                        v-model="form.emissao"
                        mask="DD/MM/YYYY HH:mm:ss"
                        default-view="Calendar"
                      >
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
              <q-input
                v-model="form.saida"
                label="Data/Hora Saída *"
                outlined
                placeholder="DD/MM/YYYY HH:mm:ss"
                mask="##/##/#### ##:##:##"
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date
                        v-model="form.saida"
                        mask="DD/MM/YYYY HH:mm:ss"
                        default-view="Calendar"
                      >
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
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Destinatário</div>

          <div class="row q-col-gutter-md">
            <!-- Cliente/Fornecedor -->
            <div class="col-12">
              <q-select
                v-model="form.codpessoa"
                :options="pessoasOptions"
                label="Cliente/Fornecedor *"
                outlined
                use-input
                input-debounce="300"
                emit-value
                map-options
                @filter="filterPessoas"
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada"
              >
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey"> Digite para buscar... </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <!-- CPF na Nota (opcional) -->
            <div class="col-12 col-sm-6">
              <q-input
                v-model="form.cpf"
                label="CPF na Nota (Consumidor)"
                outlined
                mask="###.###.###-##"
                hint="Usado para Nota Paulista, etc"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Operação Fiscal -->
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Operação Fiscal</div>

          <div class="row q-col-gutter-md">
            <!-- Natureza de Operação -->
            <div class="col-12 col-sm-6">
              <SelectNaturezaOperacao
                v-model="form.codnaturezaoperacao"
                label="Natureza de Operação *"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Operação -->
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.codoperacao"
                :options="operacoesOptions"
                label="Operação *"
                outlined
                emit-value
                map-options
                :rules="[(val) => !!val || 'Campo obrigatório']"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Valores -->
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Valores Adicionais</div>

          <div class="row q-col-gutter-md">
            <!-- Desconto -->
            <div class="col-12 col-sm-6 col-md-3">
              <q-input
                v-model.number="form.valordesconto"
                label="Desconto"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Frete -->
            <div class="col-12 col-sm-6 col-md-3">
              <q-input
                v-model.number="form.valorfrete"
                label="Frete"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Seguro -->
            <div class="col-12 col-sm-6 col-md-3">
              <q-input
                v-model.number="form.valorseguro"
                label="Seguro"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Outras Despesas -->
            <div class="col-12 col-sm-6 col-md-3">
              <q-input
                v-model.number="form.valoroutras"
                label="Outras Despesas"
                outlined
                type="number"
                step="0.01"
                min="0"
                prefix="R$"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Transporte -->
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Transporte</div>

          <div class="row q-col-gutter-md">
            <!-- Modalidade Frete -->
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.frete"
                :options="freteOptions"
                label="Modalidade do Frete"
                outlined
                emit-value
                map-options
                :disable="notaBloqueada"
              />
            </div>

            <!-- Transportador -->
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.codpessoatransportador"
                :options="transportadoresOptions"
                label="Transportador"
                outlined
                use-input
                input-debounce="300"
                clearable
                emit-value
                map-options
                @filter="filterTransportadores"
                :disable="notaBloqueada"
              >
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey"> Digite para buscar... </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <!-- Placa -->
            <div class="col-12 col-sm-6 col-md-4">
              <q-input
                v-model="form.placa"
                label="Placa do Veículo"
                outlined
                mask="AAA-AAAA"
                :disable="notaBloqueada"
              />
            </div>

            <!-- UF Placa -->
            <div class="col-12 col-sm-6 col-md-2">
              <q-select
                v-model="form.codestadoplaca"
                :options="estadosOptions"
                label="UF Placa"
                outlined
                clearable
                emit-value
                map-options
                :disable="notaBloqueada"
              />
            </div>

            <!-- Volumes -->
            <div class="col-12 col-sm-6 col-md-2">
              <q-input
                v-model.number="form.volumes"
                label="Volumes"
                outlined
                type="number"
                min="0"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Peso Bruto -->
            <div class="col-12 col-sm-6 col-md-2">
              <q-input
                v-model.number="form.pesobruto"
                label="Peso Bruto (Kg)"
                outlined
                type="number"
                step="0.001"
                min="0"
                :disable="notaBloqueada"
              />
            </div>

            <!-- Peso Líquido -->
            <div class="col-12 col-sm-6 col-md-2">
              <q-input
                v-model.number="form.pesoliquido"
                label="Peso Líquido (Kg)"
                outlined
                type="number"
                step="0.001"
                min="0"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Observações -->
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">Observações</div>

          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="form.observacoes"
                label="Observações / Informações Complementares"
                outlined
                type="textarea"
                rows="4"
                :disable="notaBloqueada"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-form>
  </q-page>
</template>
