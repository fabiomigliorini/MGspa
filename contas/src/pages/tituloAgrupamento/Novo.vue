<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar, date } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import SeletorTitulosAbertos from 'src/components/SeletorTitulosAbertos.vue'
import { formataNumero } from '@components/formatters'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const saving = ref(false)
const dialogFinalizar = ref(false)

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
  codpessoa: null,
  observacao: '',
})

const podeAvancar = computed(() => linhas.value.length > 0)

// Filial sugerida: a com maior soma de "total" entre os títulos selecionados
const codfilialSugerida = computed(() => {
  const acc = new Map()
  for (const l of linhas.value) {
    if (l.codfilial == null) continue
    acc.set(l.codfilial, (acc.get(l.codfilial) || 0) + (Number(l.total) || 0))
  }
  let vencedora = null
  let maior = -Infinity
  for (const [cod, soma] of acc) {
    if (soma > maior) {
      maior = soma
      vencedora = cod
    }
  }
  return vencedora
})

watch(codfilialSugerida, (v) => {
  if (v != null) vencimentos.value.codfilial = v
})

// Pessoa sugerida: filtro se houver; senão, a com maior soma de abs(saldo) entre os títulos selecionados
const codpessoaSugerido = computed(() => {
  if (codpessoaFiltro.value != null) return codpessoaFiltro.value
  const acc = new Map()
  for (const l of linhas.value) {
    if (l.codpessoa == null) continue
    acc.set(l.codpessoa, (acc.get(l.codpessoa) || 0) + Math.abs(Number(l.saldo) || 0))
  }
  let vencedora = null
  let maior = -Infinity
  for (const [cod, soma] of acc) {
    if (soma > maior) {
      maior = soma
      vencedora = cod
    }
  }
  return vencedora
})

const somaParcelas = computed(() =>
  vencimentos.value.parcelasGeradas.reduce((a, p) => a + (Number(p.valor) || 0), 0),
)

const parcelasOk = computed(() => {
  if (vencimentos.value.parcelasGeradas.length === 0) return false
  return Math.abs(somaParcelas.value - totalLiquido.value) < 0.01
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

function redistribuirValores() {
  const lista = vencimentos.value.parcelasGeradas
  const n = lista.length
  if (n === 0) return
  const total = totalLiquido.value
  const valorBase = Math.floor((total / n) * 100) / 100
  let acumulado = 0
  for (let i = 0; i < n; i++) {
    if (i === n - 1) lista[i].valor = +(total - acumulado).toFixed(2)
    else {
      lista[i].valor = valorBase
      acumulado += valorBase
    }
  }
}

function adicionarParcela() {
  const lista = vencimentos.value.parcelasGeradas
  const ultima = lista[lista.length - 1]
  let baseDate
  if (ultima) {
    const [y, m, d] = ultima.vencimento.split('-').map(Number)
    baseDate = new Date(y, m - 1, d)
  } else {
    baseDate = new Date()
  }
  baseDate.setDate(baseDate.getDate() + (Number(vencimentos.value.demais) || 0))
  lista.push({
    vencimento: date.formatDate(baseDate, 'YYYY-MM-DD'),
    valor: 0,
  })
  redistribuirValores()
}

function removerParcela(i) {
  vencimentos.value.parcelasGeradas.splice(i, 1)
  redistribuirValores()
}

function maxParcela(i) {
  let acc = 0
  for (let k = 0; k < i; k++) {
    acc += Number(vencimentos.value.parcelasGeradas[k].valor) || 0
  }
  return +(totalLiquido.value - acc).toFixed(2)
}

function setValor(i, novo) {
  const lista = vencimentos.value.parcelasGeradas
  lista[i].valor = Number(novo) || 0
  const restantes = lista.length - i - 1
  if (restantes <= 0) return
  let somaAteAqui = 0
  for (let k = 0; k <= i; k++) somaAteAqui += Number(lista[k].valor) || 0
  const restante = Math.max(0, +(totalLiquido.value - somaAteAqui).toFixed(2))
  const valorBase = Math.floor((restante / restantes) * 100) / 100
  let acumulado = 0
  for (let k = i + 1; k < lista.length; k++) {
    if (k === lista.length - 1) lista[k].valor = +(restante - acumulado).toFixed(2)
    else {
      lista[k].valor = valorBase
      acumulado += valorBase
    }
  }
}

function dataEmissao() {
  const [y, m, d] = finalizar.value.emissao.split('-').map(Number)
  return new Date(y, m - 1, d)
}

function getDias(i) {
  const p = vencimentos.value.parcelasGeradas[i]
  if (!p) return 0
  const [y, m, d] = p.vencimento.split('-').map(Number)
  const target = new Date(y, m - 1, d)
  return Math.round((target - dataEmissao()) / 86400000)
}

function setDias(i, dias) {
  const base = dataEmissao()
  base.setDate(base.getDate() + (Number(dias) || 0))
  vencimentos.value.parcelasGeradas[i].vencimento = date.formatDate(base, 'YYYY-MM-DD')
}

watch(dialogFinalizar, (v) => {
  if (v) {
    if (codpessoaSugerido.value != null) {
      finalizar.value.codpessoa = codpessoaSugerido.value
    }
    calcularParcelas()
  }
})

watch(
  () => [vencimentos.value.parcelas, vencimentos.value.primeira, vencimentos.value.demais],
  () => {
    if (dialogFinalizar.value) calcularParcelas()
  },
)

async function salvar() {
  if (!finalizar.value.codpessoa) {
    notifyError({ message: 'Selecione a pessoa!' }, 'Selecione a pessoa')
    return
  }
  if (!parcelasOk.value) {
    notifyError({ message: 'A soma das parcelas deve ser igual ao total!' }, 'Parcelas inválidas')
    return
  }
  $q.dialog({
    title: 'Confirmar',
    message: 'Tem certeza que deseja salvar o agrupamento?',
    cancel: true,
  }).onOk(async () => {
    saving.value = true
    try {
      const payload = {
        codpessoa: finalizar.value.codpessoa,
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
    <div style="max-width: 1086px; margin: auto">
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

      <div>
        <SeletorTitulosAbertos
          v-model="linhas"
          :codpessoa-inicial="codpessoaFiltro"
          @update:codpessoa="(v) => (codpessoaFiltro = v)"
          @update:total-liquido="(v) => (totalLiquido = v)"
          @update:operacao="(v) => (operacao = v)"
        />
      </div>
    </div>

    <!-- FAB Salvar -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="save"
        color="primary"
        :disable="!podeAvancar"
        @click="dialogFinalizar = true"
      >
        <q-tooltip>Finalizar Agrupamento</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Dialog Finalizar -->
    <q-dialog v-model="dialogFinalizar">
      <q-card bordered flat style="width: 600px; max-width: 90vw">
        <q-form @submit.prevent="salvar">
          <q-card-section class="q-pb-none">
            <div class="text-grey-9 text-overline">FINALIZAR AGRUPAMENTO</div>
            <div class="text-grey-7 q-mb-md text-caption">
              {{ linhas.length }} títulos selecionados — Total a parcelar:
              <span
                class="text-weight-bold"
                :class="operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                R$ {{ formataNumero(totalLiquido) }} {{ operacao }}
              </span>
            </div>
          </q-card-section>
          <q-separator inset />

          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-xs-12">
                <SelectPessoa
                  v-model="finalizar.codpessoa"
                  outlined
                  label="Pessoa"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-xs-12 col-sm-3">
                <MgInputData
                  v-model="finalizar.emissao"
                  type="date"
                  label="Emissão"
                  year-digits="2"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-xs-12 col-sm-4">
                <SelectFilial
                  v-model="vencimentos.codfilial"
                  outlined
                  label="Filial"
                  :rules="[(v) => !!v || 'Obrigatório']"
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
            </div>
            <div class="col-xs-12 cursor-pointer" @click="vencimentos.boleto = !vencimentos.boleto">
              <q-toggle v-model="vencimentos.boleto" name="boleto" />
              <span class="text-grey-9" v-if="vencimentos.boleto">
                Serão emitidos boletos para este agrupamento!
              </span>
              <span class="text-grey-9" v-else> Sem Boletos! </span>
            </div>

            <q-list v-if="vencimentos.parcelasGeradas.length" flat class="q-mt-md">
              <q-item v-for="(p, i) in vencimentos.parcelasGeradas" :key="i">
                <q-item-section avatar>
                  <q-avatar color="grey-6" text-color="white">
                    {{ i + 1 }}
                  </q-avatar>
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
                <q-item-section style="flex: 0 0 90px">
                  <q-input
                    type="number"
                    outlined
                    label="Dias"
                    stack-label
                    :bottom-slots="false"
                    :model-value="getDias(i)"
                    @update:model-value="(v) => setDias(i, v)"
                    autofocus
                  />
                </q-item-section>
                <q-item-section>
                  <MgInputValor
                    label="Valor"
                    stack-label
                    :bottom-slots="false"
                    :min="0"
                    :max="maxParcela(i)"
                    :model-value="p.valor"
                    @update:model-value="(v) => setValor(i, v)"
                  />
                </q-item-section>
                <q-item-section side style="flex: 0 0 40px">
                  <q-btn
                    v-if="i > 0"
                    flat
                    size="sm"
                    dense
                    round
                    color="primary"
                    icon="close"
                    @click="removerParcela(i)"
                  >
                    <q-tooltip>Excluir parcela</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
              <q-item class="q-mb-md">
                <q-item-section>
                  <q-btn
                    flat
                    color="primary"
                    icon="add"
                    label="Adicionar Parcela"
                    @click="adicionarParcela"
                  />
                </q-item-section>
              </q-item>
            </q-list>

            <q-card v-if="!parcelasOk" flat class="q-mb-md">
              <q-banner class="bg-negative text-white" inline-actions>
                <div>Total das parcelas não bate com o total dos títulos!</div>
                <div class="text-weight-bold q-mt-sm">
                  Soma: {{ formataNumero(somaParcelas) }} / Esperado:
                  {{ formataNumero(totalLiquido) }}
                </div>
                <template v-slot:action>
                  <q-icon name="error" color="white" size="md" />
                </template>
              </q-banner>
            </q-card>

            <div class="row q-col-gutter-md">
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
          </q-card-section>

          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              flat
              label="Salvar"
              type="submit"
              color="primary"
              :loading="saving"
              :disable="!parcelasOk"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
