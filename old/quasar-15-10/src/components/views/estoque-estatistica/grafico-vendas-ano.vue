<script>
import { Bar } from 'vue-chartjs'
import { debounce } from 'quasar'

export default {
  name: 'grafico-vendas-ano',
  extends: Bar,
  props: ['vendaquantidade', 'saldoquantidade'],
  data () {
    return {
      data: {
        labels: ['Vendas', 'Estoque'],
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
        responsive: true,
        maintainAspectRatio: false,
        scales: {
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
    saldoquantidade: {
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
      // passa para datasets os valores
      vm.data.datasets[0].data = [vm.vendaquantidade]
      vm.data.datasets[1].data = [vm.saldoquantidade]

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
