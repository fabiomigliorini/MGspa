<script setup>
import { hoje, inicioAno, inicioMes, somaAnos } from '@components/utils/datas'
import { ref, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico'
import Chart from 'chart.js/auto'
import SelectPessoas from 'components/pessoa/SelectPessoas.vue'

const $q = useQuasar()
const route = useRoute()
const sGrupoEconomico = GrupoEconomicoStore()

const canvasRef = ref(null)
const graficoInstance = ref(null)
const filtroPessoa = ref({
  desde: inicioMes(somaAnos(hoje(), -1)),
  codpessoa: null,
})

const opcoesDesde = [
  {
    label: 'Este Ano',
    value: inicioAno(hoje()),
  },
  {
    label: '1 Ano',
    value: inicioMes(somaAnos(hoje(), -1)),
  },
  {
    label: '2 Anos',
    value: inicioMes(somaAnos(hoje(), -2)),
  },
  { label: 'Tudo', value: null },
]

const montaGrafico = async () => {
  try {
    const ret = await sGrupoEconomico.getTopProdutos(route.params.id, filtroPessoa.value)

    const valortotal = ret.data.map((v) => v.valortotal)
    const produtos = ret.data.map((v) => v.produto)

    if (graficoInstance.value) {
      graficoInstance.value.destroy()
    }

    graficoInstance.value = new Chart(canvasRef.value, {
      type: 'doughnut',
      data: {
        labels: produtos,
        datasets: [{ label: 'Valor Total', data: valortotal }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true },
        },
      },
    })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao carregar top produtos',
    })
  }
}

watch(filtroPessoa, () => montaGrafico(), { deep: true })

onMounted(() => {
  montaGrafico()
})
</script>

<template>
  <q-card bordered flat class="full-height">
    <q-card-section class="text-grey-9 text-overline row items-center">
      TOP 10 PRODUTOS
    </q-card-section>

    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-md-6 col-xs-12">
          <select-pessoas label="Filtrar pessoa" v-model="filtroPessoa.codpessoa" />
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
      <canvas ref="canvasRef" height="200" width="200" />
    </q-card-section>
  </q-card>
</template>
