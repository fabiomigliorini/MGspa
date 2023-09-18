<script>
import {
  Line
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-vendas-geral',
  extends: Line,
  props: ['meses', 'vendas', 'saldoquantidade', 'estoqueminimo', 'estoquemaximo'],
  data () {
    return {
      mesInicial: null,
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            backgroundColor: 'rgba(63, 81, 181, 0.7)',
            borderColor: 'rgba(63, 81, 181, 0.7)',
            data: null,
            type: 'line',
            fill: false
          },
          {
            label: 'Estoque',
            backgroundColor: '#f00',
            borderColor: '#f00',
            data: null,
            type: 'line',
            fill: false
          },
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
        ]
      },
      options: {
        responsive: true,
        /*
        legend: {
						display: false
				},
        */
        maintainAspectRatio: false,
        tooltips: {
          mode: 'index',
          intersect: false
        },
        hover: {
          mode: 'nearest',
          intersect: true
        },
        scales: {
          xAxes: [
            {
              type: 'time',
              display: false,
              time: {
                displayFormats: {
                  'miliseconds': 'MMM/YY',
                  'second': 'MMM/YY',
                  'minute': 'MMM/YY',
                  'hour': 'MMM/YY',
                  'day': 'MMM/YY',
                  'week': 'MMM/YY',
                  'month': 'MMM/YY',
                  'quarter': 'MMM/YY',
                  'year': 'YYYY'
                },
                'tooltipFormat': 'MMMM/YYYY'
              }
            }
          ],
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
    meses: {
      handler: function (val, oldVal) {
        this.atualizaGrafico()
      }
    },
    vendas: {
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

      // Define Mes Inicial
      if (vm.meses != null) {
        vm.mesInicial = vm.moment().subtract(this.meses - 1, 'months').startOf('month')
      }
      else {
        vm.mesInicial = vm.moment(this.vendas[0].mes)
      }

      // acumula dados para os datasets
      let meses = []
      let vendaquantidade = []
      let saldoquantidade = []
      let estoqueminimo = []
      let estoquemaximo = []
      this.vendas.forEach(function (venda) {
        let mes = vm.moment(venda.mes)

        // Se mes anterior cai fora
        if (mes < vm.mesInicial) {
          return false
        }

        // utiliza o ultimo dia do mes, senao chartjs mostra como se fosse mes anterior
        mes = mes.endOf('month')

        meses.push(mes)
        vendaquantidade.push(venda.vendaquantidade)
        saldoquantidade.push(venda.saldoquantidade)
        estoqueminimo.push(vm.estoqueminimo)
        estoquemaximo.push(vm.estoquemaximo)
      })

      // adiciona mais um mes pra barra de saldo do estoque não ficar cortada
      meses.push(vm.moment().add(1, 'months'))

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[1].data = saldoquantidade
      vm.data.datasets[2].data = estoqueminimo
      vm.data.datasets[3].data = estoquemaximo
      vm.data.labels = meses

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
