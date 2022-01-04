<script>
import { Doughnut } from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-vendas-estoque-filiais',
  extends: Doughnut,
  props: ['locais'],
  data () {
    return {
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            data: null
          },
          {
            label: 'Estoque',
            data: null
          }
        ]
      },
      options: {
        legend: {
						display: false
				},
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          xAxes: [{
            display: false,
            ticks: {
              beginAtZero: true
            }
          }],
          yAxes: [{
            display: false,
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
      let backgrounds = [
        '#9E9E9E',
        '#FFEB3B',
        '#F44336',
        '#2196F3'
      ]

      this.locais.forEach(function (estoquelocal) {
        locais.push(estoquelocal.estoquelocal)
        vendaquantidade.push(Math.floor(estoquelocal.vendaquantidade))
        saldoquantidade.push(Math.floor(estoquelocal.saldoquantidade))
      })

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[0].backgroundColor = backgrounds

      vm.data.datasets[1].data = saldoquantidade
      vm.data.datasets[1].backgroundColor = backgrounds
      vm.data.labels = locais

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
