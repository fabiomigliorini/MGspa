<script setup>
import { ref, watch, onMounted } from "vue";
import { useQuasar, debounce } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "src/stores/pessoa";
import { formataDataSemHora } from "src/utils/formatador";
import SelectPessoas from "components/pessoa/SelectPessoas.vue";
import moment from "moment";

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();

const totaisNegocios = ref([]);
const filter = ref("");
const showFilter = ref(true);
const modelPessoas = ref({
  desde: moment().subtract(1, "year").startOf("month").format("YYYY-MM-DD"),
});
const pagination = ref({
  rowsPerPage: 7,
  sortBy: "negocios",
  descending: true,
});
const opcoesDesde = [
  {
    label: "Este Ano",
    value: moment().startOf("year").format("YYYY-MM-DD"),
  },
  {
    label: "1 Ano",
    value: moment().subtract(1, "year").startOf("month").format("YYYY-MM-DD"),
  },
  {
    label: "2 Anos",
    value: moment().subtract(2, "year").startOf("month").format("YYYY-MM-DD"),
  },
  { label: "Tudo", value: null },
];

const columns = [
  {
    name: "produto",
    label: "Produto",
    field: "produto",
    align: "left",
    sortable: true,
  },
  {
    name: "variacao",
    label: "Variação",
    field: "variacao",
    align: "left",
    sortable: true,
  },
  {
    name: "lancamento",
    label: "Data",
    field: "lancamento",
    align: "center",
    format: (val) => formataDataSemHora(val),
    sortable: true,
  },
  {
    name: "negocios",
    label: "Negócios",
    field: "negocios",
    align: "right",
    sortable: true,
  },
  {
    name: "quantidade",
    label: "Quantidade",
    field: "quantidade",
    align: "right",
    format: (val) =>
      parseFloat(val).toLocaleString("pt-BR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }),
    sortable: true,
  },
  {
    name: "valortotal",
    label: "Valor Total",
    field: "valortotal",
    align: "right",
    format: (val) =>
      parseFloat(val).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }),
    sortable: true,
  },
];

const buscarTotais = debounce(async () => {
  if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
    modelPessoas.value.codpessoa = route.params.id;
  }
  try {
    const ret = await sPessoa.totaisNegocios(
      route.params.id,
      modelPessoas.value
    );
    totaisNegocios.value = ret.data;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message: error.response?.data?.message || "Erro ao carregar negócios",
    });
  }
}, 500);

const linkMgLara = (event, row) => {
  window.open(process.env.MGLARA_URL + "produto/" + row.codproduto, "_blank");
};

watch(
  () => modelPessoas.value,
  () => buscarTotais(),
  { deep: true }
);

onMounted(() => {
  buscarTotais();
});
</script>

<template>
  <q-card bordered flat class="full-height">
      <q-table
        :filter="filter"
        :rows="totaisNegocios"
        :columns="columns"
        separator="cell"
        v-model:pagination="pagination"
        no-data-label="Nenhum negócio encontrado"
        @row-click="linkMgLara"
      >
        <template v-slot:top-left>
          <div class="text-grey-9 text-overline">PRODUTOS</div>
        </template>
        <template v-slot:top-right>
          <select-pessoas
            class="q-pa-sm"
            label="Filtrar por pessoa"
            v-model="modelPessoas.codpessoa"
          />
          <div class="col-md-6 q-pl-md q-pr-md">
            <q-select
              outlined
              v-model="modelPessoas.desde"
              map-options
              emit-value
              :options="opcoesDesde"
              label="Data"
              dense
            />
          </div>
          <q-input
            v-if="showFilter"
            outlined
            dense
            debounce="300"
            class="q-pa-sm"
            v-model="filter"
            placeholder="Pesquisar"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
          <q-btn
            class="q-ml-sm"
            icon="filter_list"
            @click="showFilter = !showFilter"
            flat
          />
        </template>
      </q-table>
  </q-card>
</template>
