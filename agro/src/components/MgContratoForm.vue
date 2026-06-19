<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { api } from 'src/services/api'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

// Form único de novo/editar contrato (recebe :cad). Cultura e safra não
// aparecem: o contrato vive dentro da safra, então o pai força esse vínculo
// via :fixar. Usado na grid da safra (MgContratosSafra) e na edição do
// detalhe (ContratoDetailPage).
const props = defineProps({
  cad: { type: Object, required: true },
  naturezas: { type: Array, default: () => [] },
  // { codsafra, codcultura } — fixa o vínculo no salvar (criação dentro da safra)
  fixar: { type: Object, default: null },
})
const emit = defineEmits(['saved'])

// Indireção (padrão MgSafraForm): v-model escreve no objeto reativo do cad sem
// disparar vue/no-mutating-props.
const cad = computed(() => props.cad)

// Aba ativa do modal (form reorganizado em abas pra reduzir a rolagem longa).
const aba = ref('negocio')

const tipos = [
  { label: 'Fixo', value: 'FIXO' },
  { label: 'A fixar', value: 'FIXAR' },
  { label: 'Barter', value: 'BARTER' },
]
const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]
const comissaoTipos = [
  { label: '%', value: 'PERCENTUAL' },
  { label: 'R$/sc', value: 'SACA' },
  { label: 'R$ total', value: 'TOTAL' },
]

// Selects de apoio (online).
const filiais = ref([])

// A cultura vem do vínculo da safra (criação) ou do próprio contrato (edição).
const codcultura = computed(() => props.fixar?.codcultura ?? cad.value.form.codcultura)
const filialSel = computed(() => filiais.value.find((f) => f.value === cad.value.form.codfilial))

function fmt(v, dec = 2) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

// Comissão total resolvida do tipo (%, R$/sc, R$ total) — info gerencial, fora
// do líquido. % incide sobre o bruto (preço × quantidade).
const comissaoTotal = computed(() => {
  const v = Number(cad.value.form.comissaovalor) || 0
  const q = Number(cad.value.form.quantidade) || 0
  const p = Number(cad.value.form.preco) || 0
  switch (cad.value.form.comissaotipo) {
    case 'TOTAL':
      return v
    case 'SACA':
      return v * q
    case 'PERCENTUAL':
      return (v / 100) * p * q
    default:
      return 0
  }
})

// ---- Preview do líquido (motor fiscal do agro) ----
const calc = ref(null)
const calculando = ref(false)
async function recalcular() {
  const bruto = Number(cad.value.form.preco) || 0
  if (!codcultura.value || bruto <= 0) {
    calc.value = null
    return
  }
  calculando.value = true
  try {
    const { data } = await api.get('v1/contrato/calculo', {
      params: {
        codcultura: codcultura.value,
        bruto,
        data: cad.value.form.embarquefim || undefined,
        isentofethab: cad.value.form.isentofethab ? 1 : 0,
        funruralvenda: filialSel.value?.funruralvenda ? 1 : 0,
      },
    })
    calc.value = data
  } catch {
    calc.value = null
  } finally {
    calculando.value = false
  }
}

watch(
  () => [
    cad.value.form.preco,
    cad.value.form.isentofethab,
    cad.value.form.embarquefim,
    cad.value.form.codfilial,
    codcultura.value,
    cad.value.dialog,
  ],
  () => {
    if (cad.value.dialog) recalcular()
  },
)

onMounted(async () => {
  try {
    const { data } = await api.get('v1/select/filial')
    filiais.value = data
  } catch {
    // selects são auxiliares; não bloqueiam o form
  }
})

async function salvar() {
  const saved = await props.cad.salvar((f) => ({
    ...f,
    comissaototal: comissaoTotal.value,
    ...(props.fixar || {}),
  }))
  // saved (com codcontrato) sobe pro pai — na criação, ele navega pra tela do contrato.
  if (!props.cad.dialog) emit('saved', saved)
}
</script>

<template>
  <q-dialog v-model="cad.dialog" @show="aba = 'negocio'">
    <q-card flat style="width: 760px; max-width: 95vw">
      <q-form @submit="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">{{ cad.isNovo ? 'Novo Contrato' : 'Editar Contrato' }}</div>
        </q-card-section>

        <!-- NOVO: rascunho mínimo (só identificação). Quantidade, preço, fixação,
             entrega e fiscal configuram-se depois na tela do contrato. -->
        <q-card-section v-if="cad.isNovo" class="row q-col-gutter-md q-pt-md">
          <div class="col-12 text-overline text-grey-7">Identificação</div>
          <div class="col-12 col-sm-4">
            <q-input v-model="cad.form.contrato" label="Nº / identificação" outlined autofocus />
          </div>
          <div class="col-12 col-sm-4">
            <q-select
              v-model="cad.form.codfilial"
              :options="filiais"
              emit-value
              map-options
              outlined
              clearable
              label="Produtor (filial)"
            />
          </div>
          <div class="col-12 col-sm-4">
            <MgInputData v-model="cad.form.datacontrato" label="Data do contrato" type="date" />
          </div>
          <div class="col-12">
            <MgSelectPessoa v-model="cad.form.codpessoa" label="Comprador" />
          </div>
          <div class="col-12 text-caption text-grey-6">
            Quantidade, preço, fixação e dados fiscais você configura na tela do contrato.
          </div>
        </q-card-section>

        <!-- EDIÇÃO: formulário completo em abas. -->
        <q-tabs
          v-if="!cad.isNovo"
          v-model="aba"
          dense
          no-caps
          align="justify"
          class="bg-grey-2 text-grey-8"
          active-color="primary"
          indicator-color="primary"
        >
          <q-tab name="negocio" label="Negócio" />
          <q-tab name="entrega" label="Entrega" />
          <q-tab name="intermediarios" label="Intermediários" />
          <q-tab name="fiscal" label="Fiscal" />
        </q-tabs>
        <q-separator v-if="!cad.isNovo" />

        <q-tab-panels v-if="!cad.isNovo" v-model="aba" animated class="scroll" style="min-height: 360px; max-height: 70vh">
          <!-- ===== Aba Negócio (identificação + valores) ===== -->
          <q-tab-panel name="negocio" class="row q-col-gutter-md">
            <div class="col-12 text-overline text-grey-7">Identificação</div>
            <div class="col-12 col-sm-4">
              <q-input v-model="cad.form.contrato" label="Nº / identificação" outlined autofocus />
            </div>
            <div class="col-12 col-sm-4">
              <q-select
                v-model="cad.form.codfilial"
                :options="filiais"
                emit-value
                map-options
                outlined
                clearable
                label="Produtor (filial)"
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgInputData v-model="cad.form.datacontrato" label="Data do contrato" type="date" />
            </div>
            <div class="col-12">
              <MgSelectPessoa v-model="cad.form.codpessoa" label="Comprador" />
            </div>

            <div class="col-12 text-overline text-grey-7 q-mt-sm">Negócio</div>
            <div class="col-12">
              <q-btn-toggle
                v-model="cad.form.tipo"
                :options="tipos"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputValor
                v-model="cad.form.quantidade"
                :decimals="0"
                suffix="sc"
                label="Quantidade"
              />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputValor
                v-model="cad.form.preco"
                :decimals="2"
                :label="cad.form.tipo === 'FIXO' ? 'Preço / saca' : 'Preço referência'"
              />
            </div>
            <div class="col-6 col-sm-3 self-center">
              <q-btn-toggle
                v-model="cad.form.moeda"
                :options="moedas"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>
            <div class="col-6 col-sm-3 self-center">
              <q-checkbox v-model="cad.form.isentofethab" label="Isento de FETHAB" />
            </div>

            <!-- Preview do líquido -->
            <div class="col-12">
              <q-banner v-if="calc" rounded class="bg-green-1 text-green-10">
                <template #avatar><q-icon name="savings" color="green-7" /></template>
                <div class="row items-center justify-between">
                  <div>
                    Líquido estimado <b>R$ {{ fmt(calc.liquido) }}/sc</b>
                    <span class="text-caption">
                      (bruto R$ {{ fmt(calc.bruto) }} − deduções R$ {{ fmt(calc.totaldeducao) }})
                    </span>
                  </div>
                  <div class="text-caption">
                    <span v-for="it in calc.itens" :key="it.codtributo" class="q-ml-sm">
                      {{ it.codigo }} {{ fmt(it.valor) }}
                    </span>
                  </div>
                </div>
                <div v-if="calc.unidade" class="text-caption text-grey-8 q-mt-xs">
                  UPF aplicável: {{ calc.unidade.competencia }} = R$
                  {{ fmt(calc.unidade.valor, 4) }}
                </div>
              </q-banner>
              <q-banner v-else-if="calculando" rounded class="bg-grey-2 text-grey-7">
                Calculando líquido…
              </q-banner>
            </div>
          </q-tab-panel>

          <!-- ===== Aba Entrega (embarque) ===== -->
          <q-tab-panel name="entrega" class="row q-col-gutter-md">
            <div class="col-12 text-overline text-grey-7">Embarque &amp; entrega</div>
            <div class="col-6 col-sm-3">
              <MgInputData v-model="cad.form.embarqueinicio" label="Embarque de" type="date" />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputData v-model="cad.form.embarquefim" label="Embarque até" type="date" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="cad.form.localentrega" label="Local / FOB-CIF" outlined />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectPortador
                v-model="cad.form.codportador"
                label="Portador (conta que recebe)"
              />
            </div>
            <div class="col-12">
              <q-checkbox
                v-model="cad.form.semlimite"
                label="Sem limite de carregamento (leva o saldo do silo)"
              >
                <q-tooltip>
                  Pula o bloqueio de excesso no embarque. Use em contratos de sobra de safra que
                  levam o saldo do silo.
                </q-tooltip>
              </q-checkbox>
            </div>
          </q-tab-panel>

          <!-- ===== Aba Intermediários (cooperativa / corretora + nºs) ===== -->
          <q-tab-panel name="intermediarios" class="row q-col-gutter-md">
            <div class="col-12 text-overline text-grey-7">Intermediários</div>
            <div class="col-12 col-sm-3 self-center">
              <q-checkbox v-model="cad.form.viacooperativa" label="Via cooperativa" />
            </div>
            <div class="col-12 col-sm-9">
              <MgSelectPessoa
                v-if="cad.form.viacooperativa"
                v-model="cad.form.codpessoacooperativa"
                label="Cooperativa"
              />
            </div>
            <div class="col-12 col-sm-5">
              <MgSelectPessoa v-model="cad.form.codpessoacorretora" label="Corretora" />
            </div>
            <div class="col-6 col-sm-3">
              <MgInputValor v-model="cad.form.comissaovalor" :decimals="2" label="Comissão" />
            </div>
            <div class="col-6 col-sm-4 self-center">
              <q-btn-toggle
                v-model="cad.form.comissaotipo"
                :options="comissaoTipos"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                :model-value="fmt(comissaoTotal)"
                label="Comissão total"
                prefix="R$"
                outlined
                readonly
              />
            </div>

            <div class="col-12 text-overline text-grey-7 q-mt-sm">Números do contrato</div>
            <div class="col-12 col-sm-4">
              <q-input v-model="cad.form.numerocomprador" label="Nº no comprador" outlined />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="cad.form.numerocorretora" label="Nº na corretora" outlined />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="cad.form.numerocooperativa" label="Nº na cooperativa" outlined />
            </div>
          </q-tab-panel>

          <!-- ===== Aba Fiscal (NF + observações) ===== -->
          <q-tab-panel name="fiscal" class="row q-col-gutter-md">
            <div class="col-12 text-overline text-grey-7">Dados fiscais (NF)</div>
            <div class="col-12">
              <q-select
                v-model="cad.form.codnaturezaoperacao"
                :options="naturezas"
                option-value="codnaturezaoperacao"
                option-label="naturezaoperacao"
                emit-value
                map-options
                outlined
                clearable
                label="Natureza da operação"
              />
            </div>
            <div class="col-12">
              <MgSelectPessoa
                v-model="cad.form.codpessoanf"
                label="Emitir NF para (destinatário)"
              />
            </div>
            <div class="col-12">
              <q-input
                v-model="cad.form.observacaonf"
                label="Observações da NF"
                type="textarea"
                autogrow
                outlined
              />
            </div>

            <div class="col-12 text-overline text-grey-7 q-mt-sm">Observações</div>
            <div class="col-12">
              <q-input
                v-model="cad.form.observacao"
                label="Observações gerais"
                type="textarea"
                autogrow
                outlined
              />
            </div>
          </q-tab-panel>
        </q-tab-panels>

        <q-separator />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            type="submit"
            flat
            :label="cad.isNovo ? 'Criar e configurar' : 'Salvar'"
            color="primary"
            :loading="cad.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
