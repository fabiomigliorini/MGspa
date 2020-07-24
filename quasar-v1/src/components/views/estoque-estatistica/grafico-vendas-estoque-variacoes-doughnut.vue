<script>
import { Doughnut } from 'vue-chartjs'

export default {
  name: 'grafico-vendas-estoque-variacoes-doughnut',
  extends: Doughnut,
  props: ['variacoes'],
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
      cores: [
        '#FFEB3B',
        '#F44336',
        '#2196F3',
        '#4CAF50',
        '#795548',
        '#F8BBD0',
        '#B2DFDB',
        '#673AB7',
        '#FF9800',
        '#9E9E9E'
      ],
      iCor: 0,
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
    variacoes: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      },
      deep: true
    }
  },
  mounted () {
    this.renderChart(this.data, this.options)
    this.atualizaGrafico()
  },
  methods: {
    update () {
      this.$data._chart.update()
    },
    escolheCor () {
      this.iCor++
      if ((this.iCor - 1) > this.cores.length) {
        return false
      }
      return this.cores[this.iCor - 1]
    },
    atualizaGrafico () {
      let vm = this

      this.iCor = 0

      // acumula dados para os datasets
      let variacoes = []
      let vendaquantidade = []
      let saldoquantidade = []
      let backgrounds = []

      this.variacoes.forEach(function (variacao) {
        variacoes.push(variacao.variacao.substr(0, 12))
        vendaquantidade.push(Math.floor(variacao.vendaquantidade))
        saldoquantidade.push(Math.floor(variacao.saldoquantidade))
        backgrounds.push(vm.escolheCor())
      })

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[0].backgroundColor = backgrounds

      vm.data.datasets[1].data = saldoquantidade
      vm.data.datasets[1].backgroundColor = backgrounds

      vm.data.labels = variacoes

      // atualiza grafico
      vm.update()
    }
  }
}
</script>
