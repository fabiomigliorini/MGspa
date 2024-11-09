<script setup>

import { ref, watch, computed, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from 'src/components/pessoa/SelectPessoas.vue'
import moment from 'moment'

const props = defineProps({
  titulosAbertos: {},
  codgrupoeconomico: null,
})

const emit = defineEmits(['update:titulosAbertos'])

const titulosAbertos = computed({
  get() {
    return props.titulosAbertos
  },
  set(value) {
    emit('update:titulosAbertos', value);
  }
});

const columns = [
  { name: 'numero', label: 'Número', field: 'numero', align: 'top-left', sortable: true },
  { name: 'fatura', label: 'Fatura', field: 'fatura', align: 'top-left', sortable: true },
  { name: 'tipotitulo', label: 'Tipo Titulo', field: 'tipotitulo', align: 'top-left', sortable: true },
  { name: 'contacontabil', label: 'Conta Contabil', field: 'contacontabil', align: 'top-left', sortable: true },
  { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'right', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, sortable: true },
  { name: 'emissao', label: 'Emissão', field: 'emissao', align: 'center', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
  { name: 'vencimento', label: 'Vencimento', field: 'vencimento', align: 'center', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()
const modelPessoas = ref({})
const filter = ref('')
const separator = ref('cell')
const pagination = ref({
  rowsPerPage: 5,
  sortBy: 'vencimento',
  ascending: true
})

const filtroTotaisNegocioPessoa = debounce(async () => {

  if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
    modelPessoas.value.codpessoa = route.params.id
  }
  try {
    const ret = await sPessoa.titulosAbertos(route.params.id, modelPessoas.value)
    emit('update:titulosAbertos', ret.data)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'warning',
      message: error.response.data.message
    })
  }
}, 500)

watch(
  () => modelPessoas.value.codpessoa,
  () => filtroTotaisNegocioPessoa(),
  { deep: true }
);

const linkMgSis = async (codtitulo) => {
  var a = document.createElement('a');
  a.target = "_blank";
  a.href = process.env.MGSIS_URL + "index.php?r=titulo/view&id=" + codtitulo
  a.click();
}


const coresVencimento = (vencimento) => {
  if (vencimento >= moment().format('YYYY-MM-DD')) {
    return 'text-green'
  }
  if (vencimento >= moment().subtract(5, 'day').startOf('day').format('YYYY-MM-DD')) {
    return 'text-orange'
  } else {
    return 'text-red'
  }
}

const coresSaldo = (saldo) => {
  if (saldo > 0) {
    return 'text-blue'
  } else {
    return 'text-orange'
  }

}

const linkTitulosAbertos = () => {
  return process.env.MGSIS_URL + "index.php?r=titulo/index&Titulo[status]=A&Titulo[codgrupoeconomico]=" + props.codgrupoeconomico
}

const linkRelatorioTitulosAbertos = () => {
  return process.env.API_URL + "v1/titulo/relatorio-pdf?codgrupoeconomico=" + props.codgrupoeconomico
}

// onMounted(() => {
//     console.log(titulosAbertos)
// })
</script>

<template>
  <q-card class="no-shadow cursor-pointer q-hoverable" bordered>
    <q-card-section class="q-pa-none">
      <q-table title="Titulos Abertos" :filter="filter" @row-click="linkMgSis" :rows="titulosAbertos" :columns="columns"
        no-data-label="Nenhum titulo encontrado" :separator="separator" emit-value v-model:pagination="pagination">

        <template v-slot:top-right>
          <select-pessoas class="q-pa-sm" label="Filtrar por pessoa" v-model="modelPessoas.codpessoa">
          </select-pessoas>

          <q-input v-if="show_filter" outlined dense debounce="300" class="q-pa-sm" v-model="filter"
            placeholder="Pesquisar">
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
          <q-btn-group flat>
            <q-btn flat icon="filter_list" @click="show_filter = !show_filter" />
            <q-btn flat icon="list" :href="linkTitulosAbertos()"
              target="_blank">
              <q-tooltip class="bg-primary" :offset="[10, 10]">
                Ver títulos em aberto!
              </q-tooltip>
            </q-btn>
            <q-btn flat icon="print" :href="linkRelatorioTitulosAbertos()"
              target="_blank">
              <q-tooltip class="bg-primary" :offset="[10, 10]">
                Relatório de Títulos em aberto!
              </q-tooltip>
            </q-btn>
          </q-btn-group>

        </template>

        <template v-slot:body="titulosAbertos">
          <q-tr :props="titulosAbertos" @click="linkMgSis(titulosAbertos.row.codtitulo)">
            <q-td key="numero" :props="titulosAbertos">
              {{ titulosAbertos.row.numero }}
            </q-td>
            <q-td key="fatura" :props="titulosAbertos">
              {{ titulosAbertos.row.fatura }}
            </q-td>
            <q-td key="tipotitulo" :props="titulosAbertos">
              {{ titulosAbertos.row.tipotitulo }}
            </q-td>
            <q-td key="contacontabil" :props="titulosAbertos">
              {{ titulosAbertos.row.contacontabil }}
            </q-td>
            <q-td key="saldo" :props="titulosAbertos" :class="coresSaldo(titulosAbertos.row.saldo)">
              {{ Math.abs(parseFloat(titulosAbertos.row.saldo)).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              }) }}
            </q-td>
            <q-td key="emissao" :props="titulosAbertos">
              {{ Documentos.formataDatasemHr(titulosAbertos.row.emissao) }}
            </q-td>
            <q-td key="vencimento" :class="coresVencimento(titulosAbertos.row.vencimento)" :props="titulosAbertos">
              <!-- <q-badge :color="Documentos.verificaPassadoFuturo(titulosAbertos.row.vencimento) == true ? 'red' : 'green'"> -->
              {{ Documentos.formataDatasemHr(titulosAbertos.row.vencimento) }}
              <!-- </q-badge> -->
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card-section>
  </q-card>
</template>
