<script setup>
import { ref, watch, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { GrupoEconomicoStore } from "src/stores/GrupoEconomico";
import Chart from "chart.js/auto";
import SelectPessoas from "components/pessoa/SelectPessoas.vue";
import moment from "moment";

const $q = useQuasar();
const route = useRoute();
const sGrupoEconomico = GrupoEconomicoStore();

const canvasRef = ref(null);
const graficoInstance = ref(null);
const filtroPessoa = ref({
  desde: moment().subtract(1, "year").startOf("month").format("YYYY-MM-DD"),
  codpessoa: null,
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

const montaGrafico = async () => {
  try {
    const ret = await sGrupoEconomico.getNegocios(
      route.params.id,
      filtroPessoa.value
    );

    let mes = moment().startOf("month");
    let primeiroMes = filtroPessoa.value.desde;
    if (!primeiroMes) {
      primeiroMes = ret.data.map((item) => item.mes).sort()[0];
    }

    const meses = [];
    do {
      meses.unshift(mes.format("YYYY-MM-DD 00:00:00"));
      mes = mes.subtract(1, "month").startOf("month");
    } while (mes.format("YYYY-MM-DD") > primeiroMes);

    const naturezas = [
      ...new Set(ret.data.slice(1).map((e) => e.naturezaoperacao)),
    ];

    const datasets = naturezas.map((natureza) => {
      const negocios = ret.data.filter(
        (item) => item.naturezaoperacao === natureza
      );
      const serie = meses.map((m) => {
        const registro = negocios.find((item) => item.mes === m);
        return registro ? registro.valortotal : 0;
      });
      return { label: natureza, data: serie, tension: 0.2 };
    });

    const mesesFormatado = meses.map((m) =>
      moment(m, "YYYY-MM-DD 00:00:00").locale("pt-br").format("MMM/YYYY")
    );

    if (graficoInstance.value) {
      graficoInstance.value.destroy();
    }

    graficoInstance.value = new Chart(canvasRef.value, {
      type: "line",
      data: { labels: mesesFormatado, datasets },
      options: {
        responsive: true,
        plugins: { title: { display: true } },
        interaction: { intersect: false },
        scales: {
          x: { display: true, title: { display: true } },
          y: {
            display: true,
            title: { display: true },
            suggestedMin: -10,
            suggestedMax: 200,
          },
        },
      },
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || "Erro ao carregar gráfico",
    });
  }
};

watch(filtroPessoa, () => montaGrafico(), { deep: true });

onMounted(() => {
  montaGrafico();
});
</script>

<template>
  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      NEGÓCIOS
    </q-card-section>

    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-md-6 col-xs-12">
          <select-pessoas
            label="Filtrar pessoa"
            v-model="filtroPessoa.codpessoa"
          />
        </div>
        <div class="col-md-6 col-xs-12">
          <q-select
            outlined
            v-model="filtroPessoa.desde"
            :options="opcoesDesde"
            label="Data"
            dense
            map-options
            emit-value
          />
        </div>
      </div>
    </q-card-section>

    <q-card-section>
      <canvas
        ref="canvasRef"
        :height="$q.screen.width > 1000 ? 50 : 200"
        width="200"
      />
    </q-card-section>
  </q-card>
</template>
