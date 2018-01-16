<script>
import {
  Bar
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-vendas-ano-filiais',
  extends: Bar,
  props: ['locais'],
  data () {
    return {
      data: {
        labels: ['Centro', 'Imperial', 'Botanico'],
        datasets: [
          {
            label: 'Estoque',
            backgroundColor: '#f00',
            data: null
          },
          {
            label: 'Vendas',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true
            }
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
      let locais = []
      let vendaquantidade = []
      let saldoquantidade = []

      this.locais.forEach(function (estoquelocal) {
        locais.push(estoquelocal.estoquelocal)
        vendaquantidade.push(estoquelocal.vendaquantidade)
        saldoquantidade.push(estoquelocal.saldoquantidade)
      })

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = saldoquantidade
      vm.data.datasets[1].data = vendaquantidade
      vm.data.labels = locais

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
