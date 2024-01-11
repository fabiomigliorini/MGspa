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
        <q-select outlined v-model="filtroPessoa.date" :options="[
          'Este ano', '1 Ano', '2 Anos', 'Tudo']" label="Data" dense />
      </div>
    </div>
    <canvas id="graficoNegocios" :height="$q.screen.width > '1000' ? '50' : '200'" width="200">
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

    async montaGrafico() {
      let valortotal = []
      let label = []
      this.meses = []

      const ret = await this.sGrupoEconomico.getNegocios(this.$route.params.id, this.filtroPessoa);

      ret.data.forEach(valores => {
        valortotal.push(valores.valortotal)
        label.push(valores.naturezaoperacao)
        this.meses.push(this.Documentos.formataMes(valores.mes))
      });

      if (this.filtroPessoa.date !== undefined) {

        if (this.filtroPessoa.date == 'Este ano') {

          var anoAtual = moment().year();
          var inicio = new Date("1/1/" + anoAtual);
          var primeiroDia = moment(inicio.valueOf()).format('YYYY-MM-DD');

          var arrayEsteAno = ret.data.filter(a => {
            return (a.mes >= primeiroDia);
          });

          valortotal = []
          label = []
          this.meses = []

          arrayEsteAno.forEach(valores => {
            valortotal.push(valores.valortotal)
            label.push(valores.naturezaoperacao)
            this.meses.push(this.Documentos.formataMes(valores.mes))
          });
        }

        if (this.filtroPessoa.date == '1 Ano') {
          var inicio = moment().subtract(1, 'year').format('YYYY-MM-DD')
          var arrayAnoAtras = ret.data.filter(a => {
            return (a.mes >= inicio);
          });

          valortotal = []
          label = []
          this.meses = []

          arrayAnoAtras.forEach(valores => {
            valortotal.push(valores.valortotal)
            label.push(valores.naturezaoperacao)
            this.meses.push(this.Documentos.formataMes(valores.mes))
          });
        }


        if (this.filtroPessoa.date == '2 Anos') {
          var inicio = moment().subtract(2, 'year').format('YYYY-MM-DD')
          var arrayDoisAnosAtras = ret.data.filter(a => {
            return (a.mes >= inicio);
          });

          valortotal = []
          label = []
          this.meses = []

          arrayDoisAnosAtras.forEach(valores => {
            valortotal.push(valores.valortotal)
            label.push(valores.naturezaoperacao)
            this.meses.push(this.Documentos.formataMes(valores.mes))
          });
        }

      }

      const data = {
        labels: this.meses,
        datasets: [
          {
            label: 'Valor Total',
            data: null,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgb(255, 99, 132)',
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
              position: 'top',
            },
            tooltip: {
              mode: 'index',
              intersect: false
            },
            title: {
              display: true,
              // text: 'Chart.js Line Chart'
            },
            hover: {
            mode: 'nearest',
            intersect: true
          },
          }
        },
      };

      data.datasets[0].data = valortotal
      // data.datasets[0].label = ['Negócios']
      this.graficoNegocios = new Chart(document.getElementById('graficoNegocios'), config)
    },

    atualizaGrafico() {
      this.graficoNegocios.destroy()
      this.montaGrafico()
    },
  },

  data() {

    watch(
      () => this.filtroPessoa.codpessoa,
      () => this.atualizaGrafico(),
      { deep: true }
    );

    watch(
      () => this.filtroPessoa.date,
      () => this.atualizaGrafico(),
      { deep: true }
    );

    return {
      model: null,
    }
  },

  async mounted() {
    this.montaGrafico()
  },

  setup() {

    const sGrupoEconomico = GrupoEconomicoStore()
    const filtroPessoa = ref({ date: '1 Ano' })
    const graficoNegocios = ref('')
    const meses = ref([])
    const Documentos = formataDocumetos()
    const $q = useQuasar()

    return {
      sGrupoEconomico,
      filtroPessoa,
      graficoNegocios,
      meses,
      Documentos
    }
  },

})
</script>

<style scoped></style>
