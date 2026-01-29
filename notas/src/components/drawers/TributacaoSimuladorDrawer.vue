<script setup>
import { ref, computed } from 'vue'
import { useQuasar, date } from 'quasar'
import { formatPercent, formatCurrency } from 'src/utils/formatters'

const $q = useQuasar()

// Formulário
const form = ref({
  codnaturezaoperacao: null,
  codcidadedestino: null,
  barras: '',
  data: date.formatDate(Date.now(), 'DD/MM/YYYY'),
  valorBase: null,
})

// Options para autocomplete
const naturezaOptions = ref([])
const cidadeOptions = ref([])

// Estado
const loading = ref(false)
const resultados = ref([])

// Computed
const isFormValid = computed(() => {
  return (
    form.value.codnaturezaoperacao &&
    form.value.codcidadedestino &&
    form.value.barras &&
    form.value.data &&
    form.value.valorBase > 0
  )
})

const totalGeral = computed(() => {
  return resultados.value.reduce((total, r) => total + (r.valor || 0), 0)
})

// Métodos
const filterNatureza = (val, update) => {
  update(() => {
    // TODO: Buscar da API
    naturezaOptions.value = []
  })
}

const filterCidade = (val, update) => {
  update(() => {
    // TODO: Buscar da API
    cidadeOptions.value = []
  })
}

const calcular = async () => {
  if (!isFormValid.value) {
    $q.notify({
      type: 'warning',
      message: 'Preencha todos os campos obrigatórios',
    })
    return
  }

  try {
    loading.value = true

    // TODO: Chamar API de cálculo
    // const response = await api.post('/tributacao/simular', form.value)

    // Mock de resultados
    await new Promise((resolve) => setTimeout(resolve, 1000))

    resultados.value = [
      {
        tributo: 'CBS',
        basereducaopercentual: 0,
        basereducao: 0,
        base: form.value.valorBase,
        aliquota: 8.5,
        valor: form.value.valorBase * 0.085,
        cst: '01',
        cclasstrib: 'XXXX',
        geracredito: true,
        valorcredito: form.value.valorBase * 0.085,
        beneficiocodigo: null,
        fundamentolegal: 'Lei Complementar nº XXX/2024',
      },
      {
        tributo: 'IBS Estadual',
        basereducaopercentual: 0,
        basereducao: 0,
        base: form.value.valorBase,
        aliquota: 17.5,
        valor: form.value.valorBase * 0.175,
        cst: '01',
        cclasstrib: 'YYYY',
        geracredito: true,
        valorcredito: form.value.valorBase * 0.175,
        beneficiocodigo: null,
        fundamentolegal: 'Lei Complementar nº XXX/2024',
      },
      {
        tributo: 'IBS Municipal',
        basereducaopercentual: 30,
        basereducao: form.value.valorBase * 0.3,
        base: form.value.valorBase * 0.7,
        aliquota: 5.5,
        valor: form.value.valorBase * 0.7 * 0.055,
        cst: '02',
        cclasstrib: 'ZZZZ',
        geracredito: false,
        valorcredito: null,
        beneficiocodigo: 'BE001',
        fundamentolegal: 'Lei Municipal nº XXX/2024',
      },
    ]

    $q.notify({
      type: 'positive',
      message: 'Cálculo realizado com sucesso',
      icon: 'check_circle',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao calcular tributos',
      caption: error.message,
    })
  } finally {
    loading.value = false
  }
}

const limparResultados = () => {
  resultados.value = []
}
</script>

<template>
  <div class="column full-height">
    <!-- Header -->
    <div class="q-pa-md bg-green text-white">
      <div class="text-h6">
        <q-icon name="calculate" class="q-mr-sm" />
        Simulador
      </div>
      <div class="text-caption">Calcule os tributos</div>
    </div>

    <q-separator />

    <!-- Formulário -->
    <q-scroll-area class="col">
      <div class="q-pa-md">
        <div class="text-subtitle1 text-weight-medium q-mb-md">Dados para Simulação</div>

        <!-- Natureza de Operação -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Natureza de Operação *</div>
          <q-select
            v-model="form.codnaturezaoperacao"
            outlined
            dense
            clearable
            use-input
            input-debounce="300"
            :options="naturezaOptions"
            @filter="filterNatureza"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">Nenhum resultado</q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Cidade Destino -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Cidade Destino *</div>
          <q-select
            v-model="form.codcidadedestino"
            outlined
            dense
            clearable
            use-input
            input-debounce="300"
            :options="cidadeOptions"
            @filter="filterCidade"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">Nenhum resultado</q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Código de Barras -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Código de Barras *</div>
          <q-input
            v-model="form.barras"
            outlined
            dense
            clearable
            placeholder="Digite o código de barras"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          />
        </div>

        <!-- Data -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Data *</div>
          <q-input
            v-model="form.data"
            outlined
            dense
            mask="##/##/####"
            placeholder="DD/MM/AAAA"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          >
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="form.data" mask="DD/MM/YYYY">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="OK" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>

        <!-- Valor Base -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Valor Base *</div>
          <q-input
            v-model.number="form.valorBase"
            outlined
            dense
            type="number"
            min="0"
            step="0.01"
            prefix="R$"
            placeholder="0,00"
            :rules="[(val) => val > 0 || 'Valor deve ser maior que zero']"
          />
        </div>

        <!-- Botão Calcular -->
        <q-btn
          unelevated
          color="green"
          label="Calcular"
          icon="calculate"
          class="full-width"
          @click="calcular"
          :loading="loading"
          :disable="!isFormValid"
        />

        <!-- Resultados -->
        <div v-if="resultados.length > 0" class="q-mt-lg">
          <q-separator class="q-mb-md" />

          <div class="text-subtitle1 text-weight-medium q-mb-md">Resultados</div>

          <!-- Card de cada tributo -->
          <q-expansion-item
            v-for="(resultado, index) in resultados"
            :key="index"
            expand-separator
            :label="resultado.tributo"
            :caption="`Valor: ${formatCurrency(resultado.valor)}`"
            class="q-mb-sm bg-grey-2 rounded-borders"
            header-class="text-weight-medium"
            default-opened
          >
            <q-card flat bordered>
              <q-card-section>
                <!-- Base Redução % -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Base Redução %:</div>
                  <div class="col-6 text-right text-weight-medium">
                    {{ formatPercent(resultado.basereducaopercentual) }}
                  </div>
                </div>

                <!-- Base Redução R$ -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Base Redução R$:</div>
                  <div class="col-6 text-right text-weight-medium">
                    {{ formatCurrency(resultado.basereducao) }}
                  </div>
                </div>

                <q-separator class="q-my-sm" />

                <!-- Base de Cálculo -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Base de Cálculo:</div>
                  <div class="col-6 text-right text-weight-medium text-primary">
                    {{ formatCurrency(resultado.base) }}
                  </div>
                </div>

                <!-- Alíquota -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Alíquota:</div>
                  <div class="col-6 text-right text-weight-medium">
                    {{ formatPercent(resultado.aliquota) }}
                  </div>
                </div>

                <q-separator class="q-my-sm" />

                <!-- Valor do Tributo -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7 text-weight-bold">Valor:</div>
                  <div class="col-6 text-right text-weight-bold text-green text-h6">
                    {{ formatCurrency(resultado.valor) }}
                  </div>
                </div>

                <q-separator class="q-my-sm" />

                <!-- CST -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">CST:</div>
                  <div class="col-6 text-right">
                    <q-badge v-if="resultado.cst" color="primary" outline>
                      {{ resultado.cst }}
                    </q-badge>
                    <span v-else class="text-grey-5">-</span>
                  </div>
                </div>

                <!-- Classificação Tributária -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Class. Tributária:</div>
                  <div class="col-6 text-right">
                    {{ resultado.cclasstrib || '-' }}
                  </div>
                </div>

                <!-- Gera Crédito -->
                <div class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Gera Crédito:</div>
                  <div class="col-6 text-right">
                    <q-icon
                      :name="resultado.geracredito ? 'check_circle' : 'cancel'"
                      :color="resultado.geracredito ? 'positive' : 'grey-5'"
                      size="sm"
                    />
                    {{ resultado.geracredito ? 'Sim' : 'Não' }}
                  </div>
                </div>

                <!-- Valor Crédito -->
                <div v-if="resultado.geracredito" class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Valor Crédito:</div>
                  <div class="col-6 text-right text-weight-medium text-positive">
                    {{ formatCurrency(resultado.valorcredito) }}
                  </div>
                </div>

                <!-- Benefício -->
                <div v-if="resultado.beneficiocodigo" class="row items-center q-mb-sm">
                  <div class="col-6 text-grey-7">Benefício:</div>
                  <div class="col-6 text-right">
                    <q-badge color="orange" outline>
                      {{ resultado.beneficiocodigo }}
                    </q-badge>
                  </div>
                </div>

                <!-- Fundamento Legal -->
                <div v-if="resultado.fundamentolegal" class="q-mt-sm">
                  <div class="text-grey-7 text-caption">Fundamento Legal:</div>
                  <div class="text-caption">{{ resultado.fundamentolegal }}</div>
                </div>
              </q-card-section>
            </q-card>
          </q-expansion-item>

          <!-- Total Geral -->
          <q-card flat bordered class="bg-green-1 q-mt-md">
            <q-card-section>
              <div class="row items-center">
                <div class="col text-h6 text-weight-bold">TOTAL GERAL</div>
                <div class="col text-right text-h5 text-weight-bold text-green">
                  {{ formatCurrency(totalGeral) }}
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Botão Limpar -->
          <q-btn
            outline
            color="grey-8"
            label="Limpar Resultados"
            icon="clear"
            class="full-width q-mt-md"
            @click="limparResultados"
          />
        </div>
      </div>
    </q-scroll-area>
  </div>
</template>
