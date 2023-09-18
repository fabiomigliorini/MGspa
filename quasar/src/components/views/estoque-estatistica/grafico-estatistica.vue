<script>
import {
  Bar
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-estatistica',
  extends: Bar,
  props: ['estoqueminimo', 'estoquemaximo', 'saldoquantidade', 'vendaquantidade'],
  data () {
    return {
      data: {
        labels: [
          'Venda Anual',
          'Saldo do Estoque',
        ],
        datasets: [
          {
            label: 'Mín',
            backgroundColor: 'rgba(255, 0, 0, 0.1)',
            data: null,
            type: 'line',
            borderWidth: 0,
            borderColor: 'rgba(0, 0, 0, 0)',
            pointRadius: 0,
            pointHoverRadius: 0
          },
          {
            label: 'Máx',
            backgroundColor: 'rgba(63, 81, 181, 0.2)',
            data: null,
            type: 'line',
            borderWidth: 0,
            borderColor: 'rgba(0, 0, 0, 0)',
            pointRadius: 0,
            pointHoverRadius: 0
          },
          {
            label: 'Venda Anual',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            data: null
          },
          {
            label: 'Saldo do Estoque',
            backgroundColor: '#f00',
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
						stacked: false,
            ticks: {
              beginAtZero: true
            }
					}]
				}
			}
    }
  },
  watch: {
    vendaquantidade: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      },
      deep: true
    },
    saldoquantidade: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      },
      deep: true
    },
    estoqueminimo: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      },
      deep: true
    },
    estoquemaximo: {
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

      this.data.datasets[0].data = [
        this.estoqueminimo,
        this.estoqueminimo,
      ]
      this.data.datasets[1].data = [
        this.estoquemaximo,
        this.estoquemaximo,
      ]
      this.data.datasets[2].data = [
        this.vendaquantidade,
        0,
      ]
      this.data.datasets[3].data = [
        0,
        this.saldoquantidade,
      ]

      // atualiza grafico
      this.update()
    }, 100)
  }
}
</script>
