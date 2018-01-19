<script>
import { Doughnut } from 'vue-chartjs'

import { debounce } from 'quasar'

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
      options: {
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
            display: true,
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
  },
  methods: {
    update () {
      this.$data._chart.update()
    },
    geraBackgrounds () {
      let opcoes = '0123456789ABCDEF'
      let cor = '#'
      for (var i = 0; i < 6; i++) {
        cor += opcoes[Math.floor(Math.random() * 16)]
      }
      return cor
    },
    atualizaGrafico: debounce(function () {
      let vm = this

      // acumula dados para os datasets
      let variacoes = []
      let vendaquantidade = []
      let saldoquantidade = []
      let backgrounds = []

      this.variacoes.forEach(function (variacao) {
        variacoes.push(variacao.variacao)
        vendaquantidade.push(Math.floor(variacao.vendaquantidade))
        saldoquantidade.push(Math.floor(variacao.saldoquantidade))
        backgrounds.push(vm.geraBackgrounds())
      })

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[0].backgroundColor = backgrounds

      vm.data.datasets[1].data = saldoquantidade
      vm.data.datasets[1].backgroundColor = backgrounds

      vm.data.labels = variacoes

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
