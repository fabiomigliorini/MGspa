<script setup>
import { formataTimestampIso } from '@components/formatters'
import { ref, onMounted, watch } from 'vue'
import { debounce } from 'quasar'
import { useAuthStore } from 'src/stores'
import MGLayout from 'src/layouts/MGLayout.vue'
import MgInputData from '@components/MgInputData.vue'
import moment from 'moment'
import { api } from 'src/boot/axios'

const user = useAuthStore()
const caixas = ref([])

const filtro = ref({
  inicio: formataTimestampIso(moment().add(-10, 'days').startOf('month').toDate()),
  fim: formataTimestampIso(moment().add(-10, 'days').endOf('month').toDate()),
})

const buscar = debounce(async () => {
  try {
    const ret = await api.get('v1/comissao-caixas', { params: filtro.value })
    caixas.value = ret.data
  } catch (error) {
    console.log(error)
  }
}, 500)

watch(
  () => filtro,
  () => buscar(),
  { deep: true },
)

const columns = ref([
  {
    name: 'filial',
    label: 'Filial',
    field: 'filial',
    align: 'left',
    sortable: true,
  },
  {
    name: 'pessoa',
    label: 'Colaborador',
    field: 'pessoa',
    align: 'left',
    sortable: true,
  },
  {
    name: 'cargo',
    label: 'Cargo',
    field: 'cargo',
    align: 'left',
    sortable: true,
  },
  {
    name: 'negocios',
    label: 'Vendas',
    field: 'negocios',
    format: (val) => {
      return new Intl.NumberFormat('pt-BR', {
        style: 'decimal',
      }).format(val)
    },
    align: 'right',
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: 'valor',
    label: 'Valor',
    field: 'valor',
    format: (val) => {
      return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        style: 'decimal',
      }).format(val)
    },
    align: 'right',
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: 'comissaocaixa',
    label: '% Comissão',
    field: 'comissaocaixa',
    format: (val) => {
      return new Intl.NumberFormat('pt-BR', {
        style: 'percent',
        minimumFractionDigits: 2,
      }).format(val / 100)
    },
    align: 'right',
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: 'comissao',
    label: 'Comissão',
    field: 'comissao',
    format: (val) => {
      return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        style: 'decimal',
      }).format(val)
    },
    align: 'right',
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
])

const pagination = ref({
  rowsPerPage: 0,
})

onMounted(() => {
  buscar()
})

const filter = ref('')
</script>
<template>
  <MGLayout drawer v-if="user.temPermissao('Recursos Humanos')">
    <template #tituloPagina> Comissão Caixas </template>
    <template #content>
      <div class="q-pa-md">
        <q-table
          flat
          bordered
          title="Caixas"
          :rows="caixas"
          row-key="codpessoa"
          :columns="columns"
          virtual-scroll
          v-model:pagination="pagination"
          :rows-per-page-options="[0]"
          :filter="filter"
        >
          <template v-slot:top-right>
            <q-input outlined dense debounce="300" v-model="filter" placeholder="Search">
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </template>
        </q-table>
      </div>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Período
              <q-btn icon="replay" @click="buscar()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <div class="q-pa-md q-gutter-md">
          <MgInputData type="timestamp" :seconds="false" v-model="filtro.inicio" label="Início" />
          <MgInputData type="timestamp" :seconds="false" v-model="filtro.fim" label="Fim" />
        </div>
      </div>
    </template>
  </MGLayout>
</template>
