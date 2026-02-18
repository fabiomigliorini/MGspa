<script setup>
import { ref, watch, onMounted } from "vue";
import { useQuasar, debounce } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "src/stores/pessoa";
import { formataDataSemHora } from "src/utils/formatador";
import SelectPessoas from "components/pessoa/SelectPessoas.vue";
import moment from "moment";

const props = defineProps({
  codgrupoeconomico: { type: Number, default: null },
});

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();

const titulosAbertos = ref([]);
const filter = ref("");
const showFilter = ref(true);
const modelPessoas = ref({});
const pagination = ref({
  rowsPerPage: 5,
  sortBy: "vencimento",
  ascending: true,
});

const columns = [
  {
    name: "numero",
    label: "Número",
    field: "numero",
    align: "left",
    sortable: true,
  },
  {
    name: "fatura",
    label: "Fatura",
    field: "fatura",
    align: "left",
    sortable: true,
  },
  {
    name: "tipotitulo",
    label: "Tipo Titulo",
    field: "tipotitulo",
    align: "left",
    sortable: true,
  },
  {
    name: "contacontabil",
    label: "Conta Contabil",
    field: "contacontabil",
    align: "left",
    sortable: true,
  },
  {
    name: "saldo",
    label: "Saldo",
    field: "saldo",
    align: "right",
    format: (val) =>
      parseFloat(val).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }),
    sortable: true,
  },
  {
    name: "emissao",
    label: "Emissão",
    field: "emissao",
    align: "center",
    format: (val) => formataDataSemHora(val),
    sortable: true,
  },
  {
    name: "vencimento",
    label: "Vencimento",
    field: "vencimento",
    align: "center",
    format: (val) => formataDataSemHora(val),
    sortable: true,
  },
];

const buscarTitulos = debounce(async () => {
  if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
    modelPessoas.value.codpessoa = route.params.id;
  }
  try {
    const ret = await sPessoa.titulosAbertos(
      route.params.id,
      modelPessoas.value
    );
    titulosAbertos.value = ret.data;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message:
        error.response?.data?.message || "Erro ao carregar títulos abertos",
    });
  }
}, 500);

const linkMgSis = (codtitulo) => {
  window.open(
    process.env.MGSIS_URL + "index.php?r=titulo/view&id=" + codtitulo,
    "_blank"
  );
};

const coresVencimento = (vencimento) => {
  if (vencimento >= moment().format("YYYY-MM-DD")) return "text-green";
  if (
    vencimento >=
    moment().subtract(5, "day").startOf("day").format("YYYY-MM-DD")
  )
    return "text-orange";
  return "text-red";
};

const coresSaldo = (saldo) => {
  return saldo > 0 ? "text-blue" : "text-orange";
};

const linkTitulosAbertos = () => {
  return (
    process.env.MGSIS_URL +
    "index.php?r=titulo/index&Titulo[status]=A&Titulo[codgrupoeconomico]=" +
    props.codgrupoeconomico
  );
};

const linkRelatorioTitulosAbertos = () => {
  return (
    process.env.API_URL +
    "v1/titulo/relatorio-pdf?codgrupoeconomico=" +
    props.codgrupoeconomico
  );
};

watch(
  () => modelPessoas.value.codpessoa,
  () => buscarTitulos(),
  { deep: true }
);

onMounted(() => {
  buscarTitulos();
});
</script>

<template>
  <q-card bordered flat>
      <q-table
        :filter="filter"
        :rows="titulosAbertos"
        :columns="columns"
        separator="cell"
        v-model:pagination="pagination"
        no-data-label="Nenhum titulo encontrado"
      >
        <template v-slot:top-left>
          <div class="text-grey-9 text-overline">TITULOS ABERTOS</div>
        </template>
        <template v-slot:top-right>
          <select-pessoas
            class="q-pa-sm"
            label="Filtrar por pessoa"
            v-model="modelPessoas.codpessoa"
          />
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
          <q-btn-group flat>
            <q-btn
              flat
              icon="filter_list"
              @click="showFilter = !showFilter"
            />
            <q-btn
              flat
              icon="list"
              :href="linkTitulosAbertos()"
              target="_blank"
            >
              <q-tooltip class="bg-primary" :offset="[10, 10]">
                Ver títulos em aberto!
              </q-tooltip>
            </q-btn>
            <q-btn
              flat
              icon="print"
              :href="linkRelatorioTitulosAbertos()"
              target="_blank"
            >
              <q-tooltip class="bg-primary" :offset="[10, 10]">
                Relatório de Títulos em aberto!
              </q-tooltip>
            </q-btn>
          </q-btn-group>
        </template>

        <template v-slot:body="scope">
          <q-tr :props="scope" @click="linkMgSis(scope.row.codtitulo)">
            <q-td key="numero" :props="scope">
              {{ scope.row.numero }}
            </q-td>
            <q-td key="fatura" :props="scope">
              {{ scope.row.fatura }}
            </q-td>
            <q-td key="tipotitulo" :props="scope">
              {{ scope.row.tipotitulo }}
            </q-td>
            <q-td key="contacontabil" :props="scope">
              {{ scope.row.contacontabil }}
            </q-td>
            <q-td
              key="saldo"
              :props="scope"
              :class="coresSaldo(scope.row.saldo)"
            >
              {{
                Math.abs(parseFloat(scope.row.saldo)).toLocaleString("pt-BR", {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })
              }}
            </q-td>
            <q-td key="emissao" :props="scope">
              {{ formataDataSemHora(scope.row.emissao) }}
            </q-td>
            <q-td
              key="vencimento"
              :props="scope"
              :class="coresVencimento(scope.row.vencimento)"
            >
              {{ formataDataSemHora(scope.row.vencimento) }}
            </q-td>
          </q-tr>
        </template>
      </q-table>
  </q-card>
</template>
