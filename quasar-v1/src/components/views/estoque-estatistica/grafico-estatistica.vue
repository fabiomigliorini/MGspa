<script>
import {
  Bar
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-estatistica',
  extends: Bar,
  props: ['estatistica', 'saldoquantidade', 'vendaquantidade'],
  data () {
    return {
      data: {
        labels: [
          'Venda Anual',
          'Média Venda Mensal',
          'Saldo do Estoque',
          'Recomendado',
        ],
        datasets: [
          {
            label: 'Venda Anual',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          },
          {
            label: 'Média Venda Mensal',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          },
          {
            label: 'Saldo do Estoque',
            backgroundColor: '#f00',
            data: null
          },
          {
            label: 'Estoque de Segurança',
            backgroundColor: '#f00',
            data: null
          },
          {
            label: 'Estoque Mínimo',
            backgroundColor: '#FFEB3B',
            data: null
          },
          {
            label: 'Estoque Máximo',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          },
        ]
      },
      options: {
        maintainAspectRatio: false,
				responsive: true,
        legend: {
						display: false
				},
				scales: {
					xAxes: [{
            display: false,
						stacked: true,
					}],
					yAxes: [{
            display: false,
						stacked: true,
            ticks: {
              beginAtZero: true
            }
					}]
				}
			}
    }
  },
  watch: {
    estatistica: {
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
    atualizaGrafico: debounce(function () {

      if (!this.estatistica) {
        return
      }

      this.data.datasets[0].data = [
        this.vendaquantidade,
        0,
        0,
        0
      ]
      this.data.datasets[1].data = [
        0,
        Math.floor(this.estatistica.demandamedia),
        0,
        0
      ]
      this.data.datasets[2].data = [
        0,
        0,
        this.saldoquantidade,
        0
      ]
      this.data.datasets[3].data = [
        0,
        0,
        0,
        this.estatistica.estoqueseguranca
      ]
      this.data.datasets[4].data = [
        0,
        0,
        0,
        this.estatistica.estoqueminimo - this.estatistica.estoqueseguranca
      ]
      this.data.datasets[5].data = [
        0,
        0,
        0,
        this.estatistica.estoquemaximo - this.estatistica.estoqueminimo
      ]

      // atualiza grafico
      this.update()
    }, 100)
  }
}
</script>
