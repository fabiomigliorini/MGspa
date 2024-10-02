<script setup>

import { onMounted, watch, ref, onUnmounted } from 'vue';
import metasStore from 'src/stores/metas';
import Chart from 'chart.js/auto'
import moment from 'moment'

const sMeta = metasStore();
const graficoMetasFilialRef = ref(null);
var objGrafico = null;

const props = defineProps({
  filial: {
    type: Object,
    default: null,
  },
});

onMounted(async () => {
  montaGrafico();
});

onUnmounted(() => {
  destroiGrafico();
});

watch(
  () => props.filial,
  () => {
    montaGrafico();
  }
)

const destroiGrafico = async () => {
  if (objGrafico) {
    await objGrafico.destroy();
  }
}

const montaGrafico = async () => {
  await destroiGrafico();

  if (!props.filial) {
    return;
  }

  const dias = [];
  const vendas = [];

  var diaInicial = moment(sMeta.meta.periodoinicial);
  var diaFinal = moment(sMeta.meta.periodofinal);

  for (var dia = moment(diaInicial); dia.isBefore(diaFinal); dia.add(1, 'days')) {
    dias.push(dia.format('dddd ll'));
    let venda = props.filial.dias.find((venda) => venda.dia === dia.format('YYYY-MM-DD'));
    if (venda) {
      vendas.push(venda.valorvenda);
    } else {
      vendas.push(0);
    }
  }

  const data = {
    labels: dias,
    datasets: [
      {
        label: 'Venda',
        data: vendas,
        fill: false,
        cubicInterpolationMode: 'monotone',
        tension: 0.4
      },

    ]
  };

  const config = {
    type: 'line',
    data: data,
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        },
        title: {
          display: false,
          text: 'Vendas'
        },
      },
      interaction: {
        intersect: false,
      },
      scales: {
        x: {
          display: true,
          title: {
            display: false
          },
          ticks: {
            display: false,
          }
        },
        y: {
          display: true,
          title: {
            display: false,
          },
          ticks: {
            display: false,
          }
        }
      }
    },
  };

  objGrafico = new Chart(
    graficoMetasFilialRef.value,
    config
  )

}

</script>
<template>
  <canvas ref="graficoMetasFilialRef" style="max-height: 200px;" />
</template>
