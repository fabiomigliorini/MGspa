<script>
import { Bar } from 'vue-chartjs'

export default {
  name: 'grafico-vendas-estoque-variacoes-item',
  extends: Bar,
  props: ['variacoes'],
  data () {
    return {
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          },
          {
            label: 'Estoque',
            backgroundColor: '#f00',
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
          yAxes: [{
            display: false,
            ticks: {
              beginAtZero: true
            }
          }],
          xAxes: [{
            display: false,
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
    atualizaGrafico () {
      let vm = this

      // acumula dados para os datasets
      let variacoes = []
      let vendaquantidade = []
      let saldoquantidade = []

      this.variacoes.forEach(function (variacao) {
        variacoes.push(variacao.variacao.substr(0, 12))
        vendaquantidade.push(variacao.vendaquantidade)
        saldoquantidade.push(variacao.saldoquantidade)
      })

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[1].data = saldoquantidade
      vm.data.labels = variacoes

      // atualiza grafico
      vm.update()
    }
  }
}
</script>
