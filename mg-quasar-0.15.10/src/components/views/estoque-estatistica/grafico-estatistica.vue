<script>
import {
  Bar
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-estatistica',
  extends: Bar,
  // props: ['locais', 'saldoquantidade', 'vendaquantidade'],
  props: ['seguranca', 'minimo', 'maximo'],
  data () {
    return {
      data: {
        labels: ['Estatistica'],
        datasets: [
          {
            label: 'Máximo',
            backgroundColor: 'rgba(0,128,0, 0.7)',
            data: null,
          },
          {
            label: 'Minimo',
            backgroundColor: 'rgba(255,165,0, 0.7)',
            data: null
          },
          {
            label: 'Segurança',
            backgroundColor: 'rgba(255,0,0, 0.7)',
            data: null
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          yAxes: [{
            stacked: true,
            ticks: {
              beginAtZero: true
            }
          }],
          yAxes: [{
             stacked: true // this also..
          }]
        }
      }
    }
  },
  watch: {
    locais: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      },
      deep: true
    }
  },
  mounted () {
    this.renderChart(this.data, this.options)
  },
  methods: {


    update () {
      this.$data._chart.update()
    },

    atualizaGrafico: debounce(function () {
      let vm = this

      // acumula dados para os datasets
      let maximo = []
      let minimo = []
      let seguranca = []
      // let locais = []
      // let vendaquantidade = []
      // let saldoquantidade = []

      this.locais.forEach(function (estoquelocal) {
        locais.push(estoquelocal.estoquelocal.substr(0, 3))
        vendaquantidade.push(estoquelocal.vendaquantidade)
        saldoquantidade.push(estoquelocal.saldoquantidade)
      })

      locais.push('Total')
      vendaquantidade.push(vm.vendaquantidade)
      saldoquantidade.push(vm.saldoquantidade)

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = maximo
      vm.data.datasets[1].data = minimo
      vm.data.datasets[2].data = seguranca
      vm.data.labels = estatistica

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
