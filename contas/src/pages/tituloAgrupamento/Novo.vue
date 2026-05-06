<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar, date } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import SeletorTitulosAbertos from 'src/components/SeletorTitulosAbertos.vue'
import { formataNumero } from 'src/utils/formatters.js'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const step = ref('titulos')
const saving = ref(false)

const linhas = ref([])
const codpessoaFiltro = ref(route.query.codpessoa ? Number(route.query.codpessoa) : null)
const totalLiquido = ref(0)
const operacao = ref('DB')

const vencimentos = ref({
  codfilial: null,
  codportador: null,
  boleto: false,
  parcelas: 1,
  primeira: 30,
  demais: 30,
  parcelasGeradas: [], // [{ vencimento: 'YYYY-MM-DD', valor: 0 }]
})

const finalizar = ref({
  emissao: date.formatDate(new Date(), 'YYYY-MM-DD'),
  observacao: '',
})

const podeAvancarPasso1 = computed(() => linhas.value.length > 0)
const podeAvancarPasso2 = computed(() => {
  if (vencimentos.value.parcelasGeradas.length === 0) return false
  const soma = vencimentos.value.parcelasGeradas.reduce(
    (a, p) => a + (Number(p.valor) || 0),
    0,
  )
  return Math.abs(soma - totalLiquido.value) < 0.01
})

// Calcula a primeira parcela considerando até 7 dias antes do final do mês
function calcularPrimeiraDias() {
  const hoje = new Date()
  const fimMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0)
  let diff = Math.floor((fimMes - hoje) / 86400000)
  if (diff <= 7) {
    const fimProx = new Date(hoje.getFullYear(), hoje.getMonth() + 2, 0)
    diff = Math.floor((fimProx - hoje) / 86400000)
  }
  return diff
}

function inicializarPrimeiraSugestao() {
  vencimentos.value.primeira = calcularPrimeiraDias()
}
inicializarPrimeiraSugestao()

function calcularParcelas() {
  const parcelas = Math.max(1, Number(vencimentos.value.parcelas) || 1)
  const primeira = Number(vencimentos.value.primeira) || 0
  const demais = Number(vencimentos.value.demais) || 0
  const total = totalLiquido.value
  const dia = new Date()

  const valores = []
  const datas = []
  let acumulado = 0
  const valorBase = Math.floor((total / parcelas) * 100) / 100

  for (let i = 0; i < parcelas; i++) {
    if (i === 0) dia.setDate(dia.getDate() + primeira)
    else dia.setDate(dia.getDate() + demais)
    datas.push(date.formatDate(new Date(dia), 'YYYY-MM-DD'))
    let v
    if (i === parcelas - 1) v = +(total - acumulado).toFixed(2)
    else {
      v = valorBase
      acumulado += v
    }
    valores.push(v)
  }
  vencimentos.value.parcelasGeradas = datas.map((d, i) => ({
    vencimento: d,
    valor: valores[i],
  }))
}

watch(
  () => totalLiquido.value,
  () => {
    if (vencimentos.value.parcelasGeradas.length === 0) return
    // Não recalcula automaticamente — mantém o controle manual.
  },
)

async function salvar() {
  $q.dialog({
    title: 'Confirmar',
    message: 'Tem certeza que deseja salvar o agrupamento?',
    cancel: true,
  }).onOk(async () => {
    saving.value = true
    try {
      const codpessoa = codpessoaFiltro.value
      if (!codpessoa) throw new Error('Selecione a pessoa nos filtros do passo 1!')
      const payload = {
        codpessoa,
        codfilial: vencimentos.value.codfilial,
        codportador: vencimentos.value.codportador || null,
        emissao: finalizar.value.emissao,
        observacao: finalizar.value.observacao || null,
        boleto: !!vencimentos.value.boleto,
        titulos: linhas.value.map((l) => ({
          codtitulo: l.codtitulo,
          saldo: l.saldo,
          multa: l.multa,
          juros: l.juros,
          desconto: l.desconto,
          total: l.total,
        })),
        vencimentos: vencimentos.value.parcelasGeradas.map((p) => p.vencimento),
        valores: vencimentos.value.parcelasGeradas.map((p) => p.valor),
      }
      const { data } = await api.post('v1/titulo-agrupamento', payload)
      notifySuccess('Agrupamento criado')
      router.replace({
        name: 'agrupamento-detalhe',
        params: { id: data.data.codtituloagrupamento },
      })
    } catch (e) {
      notifyError(e, 'Erro ao salvar agrupamento')
    } finally {
      saving.value = false
    }
  })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1200px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            :to="{ name: 'agrupamento' }"
            aria-label="Voltar"
          />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Novo Agrupamento</div>
        </q-item-section>
      </q-item>

      <q-stepper v-model="step" header-nav animated flat bordered>
        <q-step
          name="titulos"
          title="Títulos"
          icon="receipt_long"
          :done="podeAvancarPasso1"
        >
          <SeletorTitulosAbertos
            v-model="linhas"
            :codpessoa-inicial="codpessoaFiltro"
            @update:codpessoa="(v) => (codpessoaFiltro = v)"
            @update:total-liquido="(v) => (totalLiquido = v)"
            @update:operacao="(v) => (operacao = v)"
          />
        </q-step>

        <q-step
          name="vencimentos"
          title="Vencimentos"
          icon="event"
          :disable="!podeAvancarPasso1"
          :done="podeAvancarPasso2"
        >
          <div class="text-grey-7 q-mb-md">
            Total a parcelar:
            <span class="text-weight-bold" :class="operacao === 'CR' ? 'text-orange' : 'text-green'">
              {{ formataNumero(totalLiquido) }} {{ operacao }}
            </span>
          </div>

          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-xs-12 col-sm-4">
              <SelectFilial
                v-model="vencimentos.codfilial"
                outlined
                label="Filial"
                :rules="[(v) => !!v || 'Obrigatório']"
                autofocus
              />
            </div>
            <div class="col-xs-12 col-sm-5">
              <SelectPortador
                v-model="vencimentos.codportador"
                outlined
                clearable
                label="Portador"
              />
            </div>
            <div class="col-xs-12 col-sm-3">
              <q-toggle
                v-model="vencimentos.boleto"
                label="Emitir Boleto"
                left-label
              />
            </div>
            <div class="col-xs-4 col-sm-2">
              <q-input
                v-model.number="vencimentos.parcelas"
                type="number"
                outlined
                label="Parcelas"
                :bottom-slots="false"
              />
            </div>
            <div class="col-xs-4 col-sm-2">
              <q-input
                v-model.number="vencimentos.primeira"
                type="number"
                outlined
                label="Dias 1ª"
                :bottom-slots="false"
              />
            </div>
            <div class="col-xs-4 col-sm-2">
              <q-input
                v-model.number="vencimentos.demais"
                type="number"
                outlined
                label="Dias demais"
                :bottom-slots="false"
              />
            </div>
            <div class="col-xs-12 col-sm-6 flex items-center">
              <q-btn
                color="primary"
                icon="calculate"
                label="Calcular Parcelas"
                @click="calcularParcelas"
              />
            </div>
          </div>

          <q-card v-if="vencimentos.parcelasGeradas.length" flat bordered>
            <q-list separator>
              <q-item v-for="(p, i) in vencimentos.parcelasGeradas" :key="i">
                <q-item-section style="flex: 0 0 60px">
                  <q-item-label class="text-grey-7">#{{ i + 1 }}</q-item-label>
                </q-item-section>
                <q-item-section>
                  <MgInputData
                    v-model="p.vencimento"
                    type="date"
                    label="Vencimento"
                    stack-label
                    :bottom-slots="false"
                  />
                </q-item-section>
                <q-item-section>
                  <MgInputValor
                    v-model="p.valor"
                    label="Valor"
                    stack-label
                    :bottom-slots="false"
                  />
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-section
              class="row items-center"
              :class="podeAvancarPasso2 ? 'bg-green-1' : 'bg-red-1'"
            >
              <div>
                Soma: {{ formataNumero(vencimentos.parcelasGeradas.reduce((a, p) => a + (Number(p.valor) || 0), 0)) }}
                / Esperado: {{ formataNumero(totalLiquido) }}
              </div>
              <q-space />
              <q-icon v-if="podeAvancarPasso2" name="check_circle" color="green" />
              <q-icon v-else name="error" color="red" />
            </q-card-section>
          </q-card>
        </q-step>

        <q-step
          name="finalizar"
          title="Finalizar"
          icon="check_circle"
          :disable="!podeAvancarPasso2"
        >
          <q-form @submit.prevent="salvar">
            <div class="row q-col-gutter-md">
              <div class="col-xs-12 col-sm-4">
                <MgInputData
                  v-model="finalizar.emissao"
                  type="date"
                  label="Emissão"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                  autofocus
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="finalizar.observacao"
                  outlined
                  type="textarea"
                  label="Observação"
                  maxlength="200"
                  autogrow
                />
              </div>
            </div>
            <div class="q-mt-md text-right">
              <q-btn label="Salvar" type="submit" color="primary" :loading="saving" />
            </div>
          </q-form>
        </q-step>

        <template #navigation>
          <q-stepper-navigation>
            <q-btn
              v-if="step === 'titulos'"
              :disable="!podeAvancarPasso1"
              color="primary"
              label="Continuar"
              @click="step = 'vencimentos'"
            />
            <template v-else-if="step === 'vencimentos'">
              <q-btn
                flat
                color="primary"
                label="Voltar"
                @click="step = 'titulos'"
              />
              <q-btn
                :disable="!podeAvancarPasso2"
                color="primary"
                label="Continuar"
                class="q-ml-sm"
                @click="step = 'finalizar'"
              />
            </template>
            <q-btn
              v-else
              flat
              color="primary"
              label="Voltar"
              class="q-ml-sm"
              @click="step = 'vencimentos'"
            />
          </q-stepper-navigation>
        </template>
      </q-stepper>
    </div>
  </q-page>
</template>
