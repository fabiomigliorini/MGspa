<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import { formataNumero, formataData } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

const props = defineProps({
  modelValue: Boolean,
  colaborador: Object,
  codperiodo: [String, Number],
  dias: { type: Number, default: 5 },
})

const emit = defineEmits(['update:modelValue', 'efetivado'])

const $q = useQuasar()
const sRh = rhStore()

const loading = ref(false)
const salvando = ref(false)
const dadosColaborador = ref(null)
const titulos = ref([])
const observacao = ref('')
const forma = ref('B')
const formaTocada = ref(false)
const dataEvento = ref(hoje())

const FORMA_OPTIONS = [
  { label: 'Recarga Bee', value: 'B' },
  { label: 'Dinheiro', value: 'D' },
  { label: 'Desconto Folha', value: 'F' },
]

function hoje() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// --- COMPUTED TOTAIS ---

const totalPagando = computed(() =>
  titulos.value.reduce((sum, t) => sum + (parseFloat(t.pagando) || 0), 0),
)

const totalDescontando = computed(() =>
  titulos.value.reduce((sum, t) => sum + (parseFloat(t.descontando) || 0), 0),
)

const resultado = computed(() => totalPagando.value - totalDescontando.value)

// Sugestão de forma pelo sinal: positivo → Bee; negativo → Folha (o usuário decide).
const formaSugerida = computed(() => (resultado.value >= 0 ? 'B' : 'F'))

watch(formaSugerida, (val) => {
  if (!formaTocada.value) forma.value = val
})

const formaLabel = computed(() => FORMA_OPTIONS.find((o) => o.value === forma.value)?.label || '')

const onFormaChange = (val) => {
  formaTocada.value = true
  forma.value = val
}

const pctDesconto = computed(() => {
  const salario = dadosColaborador.value?.salario
  if (!salario || salario <= 0 || resultado.value >= 0) return 0
  return (Math.abs(resultado.value) / salario) * 100
})

const excedeLimite = computed(() => {
  if (resultado.value >= 0) return false
  const salario = dadosColaborador.value?.salario
  if (!salario || salario <= 0) return false
  const limite = dadosColaborador.value?.percentual_max_desconto
  if (!limite) return false
  return pctDesconto.value > limite
})

const podeSalvar = computed(() => totalPagando.value > 0 || totalDescontando.value > 0)

// --- LINHAS ---

const linhaKey = (t) => t.codtitulo ?? 'beneficio'

const atualizarPagando = (titulo, val) => {
  const num = parseFloat(val) || 0
  titulo.pagando = Math.min(Math.max(num, 0), Math.abs(titulo.saldo))
}

const atualizarDescontando = (titulo, val) => {
  const num = parseFloat(val) || 0
  titulo.descontando = Math.min(Math.max(num, 0), Math.abs(titulo.saldo))
}

const isPreenchido = (titulo) => {
  if (titulo.saldo < 0) return titulo.pagando !== null
  if (titulo.saldo > 0) return titulo.descontando !== null
  return false
}

const toggleLinha = (titulo) => {
  if (isPreenchido(titulo)) {
    titulo.pagando = null
    titulo.descontando = null
  } else {
    if (titulo.saldo < 0) titulo.pagando = Math.abs(titulo.saldo)
    if (titulo.saldo > 0) titulo.descontando = Math.abs(titulo.saldo)
  }
}

// --- CARREGAMENTO ---

const carregar = async () => {
  if (!props.colaborador) return
  loading.value = true
  titulos.value = []
  observacao.value = ''
  dadosColaborador.value = null
  formaTocada.value = false
  dataEvento.value = hoje()
  try {
    const ret = await sRh.getTitulosAcerto(
      props.codperiodo,
      props.colaborador.codperiodocolaborador,
      props.dias,
    )
    dadosColaborador.value = ret.data.data.colaborador
    titulos.value = ret.data.data.titulos.map((t) => ({
      ...t,
      pagando: t.saldo < 0 ? parseFloat(t.sugestao_pagando) || null : null,
      descontando: t.saldo > 0 ? parseFloat(t.sugestao_descontando) || null : null,
    }))
    forma.value = formaSugerida.value
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar títulos'),
    })
    emit('update:modelValue', false)
  } finally {
    loading.value = false
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) carregar()
  },
  { immediate: true },
)

// --- SUBMIT ---

const confirmar = async () => {
  salvando.value = true
  try {
    const payload = {
      forma: forma.value,
      data: dataEvento.value,
      observacao: observacao.value,
      titulos: titulos.value.map((t) => ({
        codtitulo: t.codtitulo ?? null,
        descontando: parseFloat(t.descontando) || 0,
        pagando: parseFloat(t.pagando) || 0,
      })),
    }
    await sRh.efetivarAcerto(props.codperiodo, props.colaborador.codperiodocolaborador, payload)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Acerto registrado',
    })
    emit('efetivado')
    emit('update:modelValue', false)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao registrar acerto'),
    })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :maximized="$q.screen.lt.md"
  >
    <q-card flat style="width: 820px; max-width: 95vw; min-height: 200px">
      <q-inner-loading :showing="loading" />

      <template v-if="!loading && dadosColaborador">
        <!-- CABEÇALHO -->
        <q-card-section class="q-pb-sm">
          <div class="text-h6 text-grey-9">{{ dadosColaborador.nome }}</div>
          <div class="text-caption text-grey-7">
            {{ dadosColaborador.cargo }}
            <template v-if="dadosColaborador.tempo_casa">
              · {{ dadosColaborador.tempo_casa }}
            </template>
            <template v-if="dadosColaborador.salario">
              · Salário: {{ formataNumero(dadosColaborador.salario) }}
            </template>
          </div>
        </q-card-section>

        <q-separator />

        <!-- TÍTULOS (grade) -->
        <q-card-section class="q-pa-none">
          <div
            class="row items-center q-px-md q-py-xs text-caption text-weight-medium text-grey-7 bg-grey-2"
          >
            <div class="col-3">Título</div>
            <div class="col-2">Vencimento</div>
            <div class="col-3 text-center">Saldo</div>
            <div class="col-2 text-center">Pagando</div>
            <div class="col-2 text-center">Descontando</div>
          </div>
          <q-separator />

          <q-scroll-area style="height: 240px">
            <template v-for="titulo in titulos" :key="linhaKey(titulo)">
              <div class="row items-center q-col-gutter-md q-pa-sm">
                <div class="col-3 text-body2">
                  {{ titulo.numero }}
                  <div v-if="titulo.codtitulo === null" class="text-caption text-grey-6">
                    benefício
                  </div>
                </div>
                <div class="col-2 text-body2 q-px-sm q-py-xs">
                  <template v-if="titulo.vencimento">{{ formataData(titulo.vencimento) }}</template>
                  <span v-else class="text-grey-5">—</span>
                </div>
                <div
                  class="col-3 text-right text-weight-medium"
                  :class="titulo.saldo < 0 ? 'text-green-8' : 'text-red-7'"
                >
                  {{ formataNumero(Math.abs(titulo.saldo)) }}
                  <q-icon :name="titulo.saldo < 0 ? 'south' : 'north'" size="12px" />
                  <q-btn
                    flat
                    round
                    size="sm"
                    :icon="isPreenchido(titulo) ? 'close' : 'add'"
                    :color="isPreenchido(titulo) ? 'grey-6' : 'primary'"
                    tabindex="-1"
                    @click="toggleLinha(titulo)"
                  >
                    <q-tooltip>{{ isPreenchido(titulo) ? 'Remover' : 'Adicionar' }}</q-tooltip>
                  </q-btn>
                </div>
                <!-- Pagando -->
                <div class="col-2">
                  <MgInputValor
                    v-if="titulo.saldo < 0"
                    :model-value="titulo.pagando"
                    @update:model-value="(val) => atualizarPagando(titulo, val)"
                    class="full-width"
                    :min="0"
                    :max="Math.abs(titulo.saldo)"
                  />
                </div>
                <!-- Descontando -->
                <div class="col-2">
                  <MgInputValor
                    v-if="titulo.saldo > 0"
                    :model-value="titulo.descontando"
                    @update:model-value="(val) => atualizarDescontando(titulo, val)"
                    class="full-width"
                    :min="0"
                    :max="Math.abs(titulo.saldo)"
                  />
                </div>
              </div>
              <q-separator />
            </template>

            <div v-if="titulos.length === 0" class="q-pa-md text-center text-grey">
              Nada a acertar
            </div>
          </q-scroll-area>
          <q-separator />

          <!-- Totais -->
          <div class="row items-center q-px-md q-py-sm text-weight-medium bg-grey-2">
            <div class="col-8"></div>
            <div class="col-2 text-center text-positive">{{ formataNumero(totalPagando) }}</div>
            <div class="col-2 text-center text-negative">{{ formataNumero(totalDescontando) }}</div>
          </div>
          <div class="row items-center q-px-md q-py-sm text-weight-bold bg-grey-2">
            <div class="col-8 text-right">{{ formaLabel }}:</div>
            <div class="col-4 text-center text-primary">
              {{ formataNumero(Math.abs(resultado)) }}
            </div>
          </div>
        </q-card-section>

        <!-- ALERTA LIMITE FOLHA -->
        <q-banner v-if="excedeLimite" class="bg-orange-1 text-orange-9 q-mx-md q-mb-sm" rounded>
          <template #avatar>
            <q-icon name="warning" color="orange" />
          </template>
          Atenção: desconto de {{ formataNumero(Math.abs(resultado)) }} representa
          {{ pctDesconto.toFixed(1) }}% do salário ({{ formataNumero(dadosColaborador.salario) }}).
          Limite configurado: {{ dadosColaborador.percentual_max_desconto }}%.
        </q-banner>

        <!-- FORMA + DATA -->
        <q-card-section class="q-pb-none">
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-sm-8">
              <div class="text-caption text-grey-7 q-mb-xs">Forma</div>
              <q-option-group
                :model-value="forma"
                @update:model-value="onFormaChange"
                :options="FORMA_OPTIONS"
                color="primary"
                inline
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgInputData v-model="dataEvento" label="Data do acerto" />
            </div>
          </div>
        </q-card-section>

        <!-- OBSERVAÇÃO -->
        <q-card-section class="q-pt-sm">
          <q-input
            v-model="observacao"
            type="textarea"
            label="Observação (opcional)"
            outlined
            rows="2"
            autogrow
            maxlength="200"
          />
        </q-card-section>

        <q-separator inset />

        <!-- AÇÕES -->
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn
            flat
            label="Confirmar"
            :disable="!podeSalvar"
            :loading="salvando"
            @click="confirmar()"
          />
        </q-card-actions>
      </template>
    </q-card>
  </q-dialog>
</template>
