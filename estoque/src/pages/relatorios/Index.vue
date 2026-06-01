<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { abrirPdf } from 'src/utils/abrirPdf'
import { notifyError } from 'src/utils/notify'
import MgAutocomplete from 'src/components/MgAutocomplete.vue'

const locais = ref([])

// Comparativo de vendas
const cmp = ref({
  codestoquelocaldeposito: null,
  codestoquelocalfilial: null,
  datainicial: '',
  datafinal: '',
  dias_previsao: 15,
  saldo_deposito: 1,
  saldo_filial: -1,
  minimo: null,
  maximo: null,
  codmarca: null,
})

// Físico × Fiscal
const ff = ref({
  codempresa: 1,
  mes: new Date().getMonth() + 1,
  ano: new Date().getFullYear(),
  codestoquelocal: null,
  codmarca: null,
  saldo_fisico: null,
  saldo_fiscal: null,
  saldo_fisico_fiscal: null,
})

// Transferências
const tr = ref({
  codestoquelocalorigem: null,
  codestoquelocaldestino: null,
  codmarca: null,
  abc: null,
})

const fiscalOptions = [
  { label: 'Indiferente', value: null },
  { label: 'Negativo', value: -1 },
  { label: 'Positivo', value: 1 },
  { label: 'Zerado', value: 9 },
]
const comparaOptions = [
  { label: 'Indiferente', value: null },
  { label: 'Fiscal menor que físico', value: -1 },
  { label: 'Fiscal maior que físico', value: 1 },
]
const abcOptions = [
  { label: 'Todas', value: null },
  { label: 'AB (Urgentes)', value: 'AB' },
  { label: 'A — Contínuo', value: 'A' },
  { label: 'B — Alto Giro', value: 'B' },
  { label: 'C — Comum', value: 'C' },
  { label: 'D — Sazonal', value: 'D' },
]

function limpos(obj) {
  const out = {}
  for (const [k, v] of Object.entries(obj)) {
    if (v !== null && v !== '') out[k] = v
  }
  return out
}

const gerarComparativo = () => {
  if (!cmp.value.codestoquelocalfilial) {
    notifyError(null, 'Selecione a filial')
    return
  }
  abrirPdf('v1/estoque-saldo/relatorio/comparativo-vendas', limpos(cmp.value), {
    title: 'Comparativo de Vendas',
  })
}
const gerarFisicoFiscal = () =>
  abrirPdf('v1/estoque-saldo/relatorio/fisico-fiscal', limpos(ff.value), {
    title: 'Físico × Fiscal',
  })
const gerarTransferencias = () => {
  if (!tr.value.codestoquelocalorigem || !tr.value.codestoquelocaldestino) {
    notifyError(null, 'Selecione origem e destino')
    return
  }
  abrirPdf('v1/estoque-saldo/relatorio/transferencias', limpos(tr.value), {
    title: 'Transferências',
  })
}

onMounted(async () => {
  const { data } = await api.get('v1/select/estoque-local')
  locais.value = data
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div class="text-h6 q-mb-md">Relatórios de Estoque</div>

      <!-- Comparativo de Vendas -->
      <q-card bordered flat class="q-mb-md">
        <q-expansion-item icon="compare_arrows" label="Comparativo de Vendas (Depósito × Filial)" default-opened>
          <q-card-section class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="cmp.codestoquelocaldeposito"
                :options="locais"
                emit-value
                map-options
                outlined
                clearable
                label="Depósito"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="cmp.codestoquelocalfilial"
                :options="locais"
                emit-value
                map-options
                outlined
                label="Filial"
              />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model="cmp.datainicial" outlined type="date" label="Data inicial" stack-label />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model="cmp.datafinal" outlined type="date" label="Data final" stack-label />
            </div>
            <div class="col-6 col-sm-4">
              <q-input v-model.number="cmp.dias_previsao" outlined type="number" label="Dias previsão" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="cmp.saldo_deposito" :options="[{label:'Com saldo no depósito',value:1},{label:'Sem saldo no depósito',value:-1},{label:'Indiferente',value:null}]" emit-value map-options outlined label="Saldo depósito" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="cmp.saldo_filial" :options="[{label:'Acima da previsão',value:1},{label:'Abaixo da previsão',value:-1},{label:'Indiferente',value:null}]" emit-value map-options outlined label="Saldo filial" />
            </div>
            <div class="col-12 col-sm-4">
              <MgAutocomplete v-model="cmp.codmarca" endpoint="v1/marca/autocompletar" search-param="marca" label="Marca (opcional)" />
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn unelevated color="primary" icon="print" label="Gerar PDF" @click="gerarComparativo" />
          </q-card-actions>
        </q-expansion-item>
      </q-card>

      <!-- Físico × Fiscal -->
      <q-card bordered flat class="q-mb-md">
        <q-expansion-item icon="balance" label="Físico × Fiscal">
          <q-card-section class="row q-col-gutter-md">
            <div class="col-6 col-sm-3">
              <q-input v-model.number="ff.codempresa" outlined type="number" label="Empresa" />
            </div>
            <div class="col-6 col-sm-3">
              <q-input v-model.number="ff.mes" outlined type="number" label="Mês" />
            </div>
            <div class="col-6 col-sm-3">
              <q-input v-model.number="ff.ano" outlined type="number" label="Ano" />
            </div>
            <div class="col-6 col-sm-3">
              <q-select v-model="ff.codestoquelocal" :options="locais" emit-value map-options outlined clearable label="Depósito" />
            </div>
            <div class="col-12 col-sm-4">
              <MgAutocomplete v-model="ff.codmarca" endpoint="v1/marca/autocompletar" search-param="marca" label="Marca (opcional)" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="ff.saldo_fisico" :options="fiscalOptions" emit-value map-options outlined label="Saldo físico" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="ff.saldo_fiscal" :options="fiscalOptions" emit-value map-options outlined label="Saldo fiscal" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select v-model="ff.saldo_fisico_fiscal" :options="comparaOptions" emit-value map-options outlined label="Comparação físico × fiscal" />
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn unelevated color="primary" icon="print" label="Gerar PDF" @click="gerarFisicoFiscal" />
          </q-card-actions>
        </q-expansion-item>
      </q-card>

      <!-- Transferências -->
      <q-card bordered flat>
        <q-expansion-item icon="swap_horiz" label="Sugestão de Transferências">
          <q-card-section class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-select v-model="tr.codestoquelocalorigem" :options="locais" emit-value map-options outlined label="Local de origem" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select v-model="tr.codestoquelocaldestino" :options="locais" emit-value map-options outlined label="Local de destino" />
            </div>
            <div class="col-12 col-sm-6">
              <MgAutocomplete v-model="tr.codmarca" endpoint="v1/marca/autocompletar" search-param="marca" label="Marca (opcional)" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select v-model="tr.abc" :options="abcOptions" emit-value map-options outlined label="Curva ABC" />
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn unelevated color="primary" icon="print" label="Gerar PDF" @click="gerarTransferencias" />
          </q-card-actions>
        </q-expansion-item>
      </q-card>
    </div>
  </q-page>
</template>
