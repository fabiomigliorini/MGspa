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

const nfeTerceiro = ref([]);
const filter = ref("");
const showFilter = ref(true);
const modelPessoas = ref({
  desde: moment().subtract(1, "year").startOf("month").format("YYYY-MM-DD"),
});
const pagination = ref({
  rowsPerPage: 5,
  sortBy: "emissao",
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
    name: "nfechave",
    label: "Chave Nfe",
    field: "nfechave",
    align: "left",
    sortable: true,
  },
  {
    name: "serie",
    label: "Série",
    field: "serie",
    align: "left",
    sortable: true,
  },
  {
    name: "numero",
    label: "Número",
    field: "numero",
    align: "left",
    sortable: true,
  },
  {
    name: "emissao",
    label: "Emissão",
    field: "emissao",
    align: "left",
    format: (val) => formataDataSemHora(val),
    sortable: true,
  },
  {
    name: "entrada",
    label: "Entrada",
    field: "entrada",
    align: "left",
    format: (val) => formataDataSemHora(val),
    sortable: true,
  },
  {
    name: "indsituacao",
    label: "indsituacao",
    field: "indsituacao",
    align: "left",
    sortable: true,
  },
  {
    name: "indmanifestacao",
    label: "indmanifestacao",
    field: "indmanifestacao",
    align: "left",
    sortable: true,
  },
  {
    name: "valortotal",
    label: "Valor Total",
    field: "valortotal",
    align: "left",
    format: (val) =>
      parseFloat(val).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }),
    sortable: true,
  },
];

const buscarNfeTerceiro = debounce(async () => {
  if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
    modelPessoas.value.codpessoa = route.params.id;
  }
  try {
    const ret = await sPessoa.nfeTerceiro(route.params.id, modelPessoas.value);
    nfeTerceiro.value = ret.data;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message:
        error.response?.data?.message || "Erro ao carregar nfe terceiro",
    });
  }
}, 500);

const linkMgSis = (event, row) => {
  window.open(
    process.env.MGSIS_URL +
      "index.php?r=nfeTerceiro/view&id=" +
      row.codnfeterceiro,
    "_blank"
  );
};

watch(() => modelPessoas.value, () => buscarNfeTerceiro(), { deep: true });

onMounted(() => {
  buscarNfeTerceiro();
});
</script>

<template>
  <q-card bordered flat>
      <q-table
        :filter="filter"
        :rows="nfeTerceiro"
        :columns="columns"
        separator="cell"
        v-model:pagination="pagination"
        no-data-label="Nenhuma nfe de terceiro encontrada"
        @row-click="linkMgSis"
      >
        <template v-slot:top-left>
          <div class="text-grey-9 text-overline">NFE TERCEIRO</div>
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
