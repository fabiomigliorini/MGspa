<template>
  <q-card>
    <q-item-label header>
      Negócios
    </q-item-label>

    <div class="row">
      <div class="q-pl-md col-md-6">
        <select-pessoas label="Filtrar pessoa" v-model="filtroPessoa.codpessoa"></select-pessoas>
      </div>

      <div class="col-md-6 q-pl-md q-pr-md">
        <q-select outlined v-model="filtroPessoa.desde" :options="opcoesDesde" label="Data" dense map-options
          emit-value />
      </div>
    </div>
    <canvas id="graficoNegocios" class="q-pl-md" :height="$q.screen.width > '1000' ? '50' : '200'" width="200">
    </canvas>
  </q-card>
</template>

<script>
import { defineComponent, defineAsyncComponent, watch } from 'vue'
import { ref } from 'vue'
import Chart from 'chart.js/auto'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico';
import { formataDocumetos } from 'src/stores/formataDocumentos';
import moment from 'moment';
import { useQuasar } from 'quasar';

export default defineComponent({
  name: "GraficoNegocios",
  components: {
    SelectPessoas: defineAsyncComponent(() => import('components/pessoa/SelectPessoas.vue')),
  },

  methods: {

    async montaOpcoesDesde() {
      this.opcoesDesde = [
        {
          label: 'Este Ano',
          value: moment().startOf('year').format('YYYY-MM-DD')
        },
        {
          label: '1 Ano',
          value: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD')
        },
        {
          label: '2 Anos',
          value: moment().subtract(2, 'year').startOf('month').format('YYYY-MM-DD')
        },
        {
          label: 'Tudo',
          value: null
        },
      ]
    },

    async montaGrafico() {

      // busca negocios na api
      const ret = await this.sGrupoEconomico.getNegocios(this.$route.params.id, this.filtroPessoa);

      // monta array de meses
      let mes = moment().startOf('month');
      let primeiroMes = this.filtroPessoa.desde;
      if (primeiroMes == null) {
        primeiroMes = (ret.data.map(item => item.mes).sort())[0];
      }
      let meses = [];
      do {
        meses.unshift(mes.format('YYYY-MM-DD 00:00:00'));
        mes = mes.subtract(1, 'month').startOf('month');
      } while (mes.format('YYYY-MM-DD') > primeiroMes);
      this.meses = meses

      // pega todas as naturezas operação e remove os nomes duplicados
      const naturezas = Array.from(new Set(Object.values(ret.data.slice(1).map(element => element.naturezaoperacao))))

      let datasets = [];

      naturezas.forEach(natureza => {
        let negocios = ret.data.filter(item => item.naturezaoperacao === natureza);
        let serie = [];
        meses.forEach(mes => {
          const registro = negocios.filter(item => item.mes === mes);
          if (registro.length > 0) {
            serie.push(registro[0].valortotal);
          } else {
            serie.push(0);
          }
        });

        let dataset = {
          label: natureza,
          data: serie,
          tension: 0.2
        };

        datasets.push(dataset);

      });
      
      // const formataMes = moment(this.meses, 'YYYY-MM-DD 00:00:00').format('YYYY-MM-DD')
      // console.log(formataMes)
      let mesesFormatado = []

      this.meses.forEach(mesFormat => {
        mesesFormatado.push(moment(mesFormat, 'YYYY-MM-DD 00:00:00').locale('Pt-Br').format('MMM/YYYY'))
      });

      const data = {
        labels: mesesFormatado,
        datasets: datasets,
      };

      const config = {
        type: 'line',
        data: data,
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
            },
          },
          interaction: {
            intersect: false,
          },
          scales: {
            x: {
              display: true,
              title: {
                display: true
              }
            },
            y: {
              display: true,
              title: {
                display: true,
                // text: 'Value'
              },
              suggestedMin: -10,
              suggestedMax: 200
            }
          }
        },
      };

      // console.log('Os Data Sets:', data.datasets)



      // // separa os valores que estao no dataset
      // data.datasets.forEach(dataset => {
      //   const valores = dataset.data.map(valor => valor.valortotal)

      //   data.labels = Array.from(new Set(this.meses))
      //   this.arrayNegocios = []
      //   dataset.data = []
      //   dataset.data = valores
      //   // console.log(dataset.data)
      // });

      this.graficoNegocios = new Chart(document.getElementById('graficoNegocios'), config)
    },

    atualizaGrafico() {
      this.graficoNegocios.destroy()
      this.montaGrafico()
    },

  },

  data() {

    watch(
      () => this.filtroPessoa,
      () => this.atualizaGrafico(),
      { deep: true }
    );

    return {
      model: null,
    }
  },

  async mounted() {
    this.montaOpcoesDesde();
    this.montaGrafico();
  },

  setup() {

    const sGrupoEconomico = GrupoEconomicoStore()
    const filtroPessoa = ref({
      desde: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD'),
      codpessoa: null,
    })
    const graficoNegocios = ref('')
    const meses = ref([])
    const Documentos = formataDocumetos()
    const $q = useQuasar()
    const opcoesDesde = ref([]);

    return {
      sGrupoEconomico,
      filtroPessoa,
      graficoNegocios,
      opcoesDesde,
      meses,
      Documentos
    }
  },

})
</script>

<style scoped></style>
