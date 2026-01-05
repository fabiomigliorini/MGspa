<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import SelectNaturezaOperacao from '../components/selects/SelectNaturezaOperacao.vue'
import SelectFilial from '../components/selects/SelectFilial.vue'
import SelectEstoqueLocal from '../components/selects/SelectEstoqueLocal.vue'
import SelectPessoa from '../components/selects/SelectPessoa.vue'
import SelectEstado from '../components/selects/SelectEstado.vue'
import { getModeloLabel } from 'src/constants/notaFiscal'
import { formatNumero } from 'src/utils/formatters'
import { validarChaveNFe } from 'src/utils/validators'

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

const form = ref(null)

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

// Formata erros de validação do Laravel para exibição
const formatValidationErrors = (error) => {
  if (error.response?.data?.errors) {
    const errors = error.response.data.errors
    const messages = []

    for (const fieldErrors of Object.values(errors)) {
      // fieldErrors é um array de mensagens de erro para este campo
      messages.push(...fieldErrors)
    }

    return messages.join('\n')
  }

  return error.response?.data?.message || error.message
}

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
          emitida: nota.value.emitida,
          modelo: String(nota.value.modelo),
          serie: nota.value.serie,
          numero: nota.value.numero,
          nfechave: nota.value.nfechave,
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
        }
      }
    } catch (error) {
      const errorMessage = formatValidationErrors(error)

      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar nota fiscal',
        caption: errorMessage,
        multiLine: true,
        timeout: 5000,
      })
      router.push({ name: 'notas' })
    } finally {
      loading.value = false
    }
    return true
  }

  form.value = {
    codfilial: null,
    codestoquelocal: null,
    codpessoa: 1,
    codnaturezaoperacao: 1,
    codoperacao: null,
    emitida: true,
    modelo: '55',
    serie: 1,
    numero: 0,
    nfechave: null,
    emissao: isoToFormDateTime(new Date),
    saida: isoToFormDateTime(new Date),
    cpf: null,
    valordesconto: null,
    valorfrete: null,
    valorseguro: null,
    valoroutras: null,
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
    const errorMessage = formatValidationErrors(error)

    $q.notify({
      type: 'negative',
      message: `Erro ao ${isEditMode.value ? 'atualizar' : 'criar'} nota fiscal`,
      caption: errorMessage,
      multiLine: true,
      timeout: 5000,
    })
  } finally {
    loading.value = false
  }
}

const handleClearPessoa = () => {
  form.value.codpessoa = 1 // Define como Consumidor ao invés de null
}

// Limpa caracteres não numéricos ao colar
const handlePaste = (evt) => {
  const pastedText = evt.clipboardData?.getData('text')
  if (pastedText) {
    evt.preventDefault()
    const cleanedText = pastedText.replace(/\D/g, '').substring(0, 44)
    form.value.nfechave = cleanedText
  }
}

const resetNumero = () => {
  if (isEditMode.value) {
    form.value.nfechave = nota.value.nfechave;
    form.value.numero = nota.value.numero;
    form.value.serie = nota.value.serie;
    return
  }
  form.value.nfechave = null;
  form.value.numero = 0;
  form.value.serie = 1;
}

// Extrai dados da chave NFe (44 dígitos)
const extrairDadosChaveNFe = (chave) => {
  if (!chave || chave.length !== 44) return null

  return {
    cnpj: chave.substring(6, 20), // Posições 7-20: CNPJ do emitente
    serie: parseInt(chave.substring(22, 25)), // Posições 23-25: Série
    numero: parseInt(chave.substring(25, 34)), // Posições 26-34: Número
    modelo: chave.substring(20, 22), // Posições 21-22: Modelo
  }
}

// Ref para controlar se deve preencher automaticamente (evitar loops)
const cnpjPessoaRef = ref(null)

// Watcher para preencher série e número automaticamente quando a chave for informada
watch(() => form.value?.nfechave, (novaChave) => {
  if (!novaChave || novaChave.length !== 44 || form.value.emitida) return

  const dados = extrairDadosChaveNFe(novaChave)
  if (dados) {
    // Preenche série e número
    form.value.serie = dados.serie
    form.value.numero = dados.numero
    form.value.modelo = dados.modelo

    // Armazena o CNPJ para usar na pesquisa de pessoa
    cnpjPessoaRef.value = dados.cnpj
  }
})

// Computed: Valor Total calculado da Nota Fiscal
const notaValorTotal = computed(() => {
  const valorprodutos = nota.value?.valorprodutos || 0
  const desconto = form.value?.valordesconto || 0
  const frete = form.value?.valorfrete || 0
  const seguro = form.value?.valorseguro || 0
  const outras = form.value?.valoroutras || 0
  return valorprodutos - desconto + frete + seguro + outras
})

// Lifecycle
onMounted(() => {
  console.log(route.params)
  console.log(route.params.codnotafiscal)
  console.log(['iseditmode', isEditMode.value])
  loadFormData()
  loadOptions()
})
</script>

<template>
  <q-page padding>
    <q-form @submit.prevent="handleSubmit" v-if="form">
      <div style="max-width: 700px; margin: 0 auto;">

        <!-- Header -->
        <div class="row items-center q-mb-md" style="flex-wrap: nowrap;">
          <template v-if="isEditMode">
            <q-btn flat dense round icon="arrow_back" :to="'/nota/' + route.params.codnotafiscal" class="q-mr-sm"
              :disable="loading" style="flex-shrink: 0;" />
          </template>
          <template v-else>
            <q-btn flat dense round icon="arrow_back" to="/nota" class="q-mr-sm" size="1.5em" :disable="loading"
              style="flex-shrink: 0;" />
          </template>
          <div class="text-h5 ellipsis" style="flex: 1; min-width: 0;">
            <template v-if="isEditMode">
              {{ getModeloLabel(form.modelo) }}
              {{ formatNumero(form.numero) }}
              - Série
              {{ form.serie }}
            </template>
            <template v-else>
              Nova Nota Fiscal
            </template>
          </div>
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
                <SelectFilial v-model="form.codfilial" label="Filial *" :disable="notaBloqueada" autofocus />
              </div>

              <!-- Local de Estoque -->
              <div class="col-12 col-sm-6">
                <SelectEstoqueLocal v-model="form.codestoquelocal" :codfilial="form.codfilial" label="Local de Estoque"
                  :disable="notaBloqueada" />
              </div>

              <!-- EMITIDA -->
              <div class="col-12 col-sm-3">
                <q-select v-model="form.emitida"
                  :options="[{ label: 'Nossa', value: true }, { label: 'Terceiro', value: false }]" label="Emissão *"
                  outlined emit-value map-options :rules="[(val) => val !== null || 'Campo obrigatório']"
                  :disable="notaBloqueada || (nota && nota.numero != 0)" @update:model-value="resetNumero()" />
              </div>

              <!-- Modelo -->
              <div class="col-12 col-sm-3">
                <q-select v-model="form.modelo" :options="modeloOptions" label="Modelo *" outlined emit-value
                  map-options :rules="[(val) => !!val || 'Campo obrigatório']"
                  :disable="notaBloqueada || (nota && nota.numero != 0)" />
              </div>

              <!-- Série -->
              <div class="col-12 col-sm-2">
                <q-input v-model="form.serie" label="Série *" outlined :rules="[(val) => !!val || 'Campo obrigatório']"
                  :disable="notaBloqueada || form.emitida" />
              </div>

              <!-- Número -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.numero" label="Número" outlined type="number"
                  hint="Deixe em branco para gerar automaticamente" :disable="notaBloqueada || form.emitida"
                  input-class="text-right" />
              </div>


              <!-- CHAVE -->
              <div class="col-12">
                <q-input v-model="form.nfechave" label="Chave de Acesso da NFe *" outlined
                  mask="#### #### #### #### #### #### #### #### #### #### ####" unmasked-value
                  placeholder="Digite os 44 dígitos da chave de acesso" :rules="[
                    (val) => validarChaveNFe(val) || 'Chave de acesso inválida (dígito verificador incorreto)',
                  ]" lazy-rules :disable="notaBloqueada || form.emitida" @paste="handlePaste" />
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
                <SelectPessoa v-model="form.codpessoa" label="Cliente/Fornecedor *" :disable="notaBloqueada"
                  @clear="handleClearPessoa" :search-cnpj="cnpjPessoaRef" />
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
              <div class="col-12">
                <SelectNaturezaOperacao v-model="form.codnaturezaoperacao" label="Natureza de Operação *"
                  :disable="notaBloqueada" />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Valores -->
        <q-card flat bordered class="q-mb-md" v-if="nota?.valorprodutos">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="payments" size="sm" class="q-mr-xs" />
              VALORES
            </div>

            <div class="row q-col-gutter-md">
              <!-- Valor Produtos (somente leitura) -->
              <div class="col-12 col-sm-4" v-if="isEditMode">
                <q-input :model-value="nota?.valorprodutos?.toFixed(2)" label="Produtos" outlined readonly prefix="R$"
                  input-class="text-right" />
              </div>

              <!-- Desconto -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.valordesconto" label="Desconto" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Frete -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.valorfrete" label="Frete" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Seguro -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.valorseguro" label="Seguro" outlined type="number" step="0.01" min="0"
                  prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Outras Despesas -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.valoroutras" label="Outras Despesas" outlined type="number" step="0.01"
                  min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Valor Total (calculado) -->
              <div class="col-12 col-sm-4" v-if="isEditMode">
                <q-input :model-value="notaValorTotal.toFixed(2)" label="Valor Total" outlined readonly prefix="R$"
                  input-class="text-right text-weight-bold" bg-color="blue-grey-1"
                  :rules="[() => notaValorTotal >= 0 || 'Valor total não pode ser negativo']" reactive-rules />
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
                <q-input v-model="form.placa" label="Placa do Veículo" outlined maxlength="7"
                  :disable="notaBloqueada" />
              </div>

              <!-- UF Placa -->
              <div class="col-12 col-sm-4">
                <SelectEstado v-model="form.codestadoplaca" label="UF Placa" :disable="notaBloqueada" />
              </div>

              <!-- Volumes -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.volumes" label="Volumes" outlined type="number" min="0"
                  :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Espécie dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input v-model="form.volumesespecie" label="Espécie" outlined maxlength="60"
                  hint="Ex: Caixa, Pacote, Fardo" :disable="notaBloqueada" />
              </div>

              <!-- Marca dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input v-model="form.volumesmarca" label="Marca" outlined maxlength="60" :disable="notaBloqueada" />
              </div>

              <!-- Numeração dos Volumes -->
              <div class="col-12 col-sm-4">
                <q-input v-model="form.volumesnumero" label="Numeração" outlined maxlength="60"
                  :disable="notaBloqueada" />
              </div>

              <!-- Peso Bruto -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.pesobruto" label="Peso Bruto (Kg)" outlined type="number" step="0.001"
                  min="0" :disable="notaBloqueada" input-class="text-right" />
              </div>

              <!-- Peso Líquido -->
              <div class="col-12 col-sm-4">
                <q-input v-model.number="form.pesoliquido" label="Peso Líquido (Kg)" outlined type="number" step="0.001"
                  min="0" :disable="notaBloqueada" input-class="text-right" />
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
      <!-- FAB para Salvar -->
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab color="primary" icon="save" type="submit" :loading="loading" :disable="notaBloqueada">
          <q-tooltip>Salvar Nota</q-tooltip>
        </q-btn>
      </q-page-sticky>
    </q-form>
  </q-page>
</template>
