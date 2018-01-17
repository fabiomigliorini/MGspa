<script>
import { Doughnut } from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-estoque-vendas-filiais',
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
      let locais = []
      let vendaquantidade = []
      let saldoquantidade = []
      let backgrounds = []

      this.locais.forEach(function (estoquelocal) {
        locais.push(estoquelocal.estoquelocal)
        vendaquantidade.push(estoquelocal.vendaquantidade)
        saldoquantidade.push(estoquelocal.saldoquantidade)
        backgrounds.push(vm.geraBackgrounds())
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
