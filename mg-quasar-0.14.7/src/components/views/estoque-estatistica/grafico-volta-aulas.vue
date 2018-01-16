<script>
import {
  Bar
} from 'vue-chartjs'

import { debounce } from 'quasar'

export default {
  name: 'grafico-volta-aulas',
  extends: Bar,
  props: ['vendas', 'saldoquantidade'],
  data () {
    return {
      mesInicial: null,
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            // backgroundColor: '#ccf',
            data: null,
            type: 'line'
          },
          {
            label: 'Estoque',
            backgroundColor: '#f00',
            data: null,
            type: 'bar'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    }
  },
  watch: {
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

      // acumula dados para os datasets
      let anos = []
      let vendaquantidade = []
      let saldoquantidade = []

      Object.entries(this.vendas).forEach(([key, value]) => {
        anos.push(value.ano)
        saldoquantidade.push(null)
        vendaquantidade.push(value.vendaquantidade)
      })

      // adiciona saldo do estoque na ultima coluna
      saldoquantidade[saldoquantidade.length - 1] = vm.saldoquantidade

      // passa para datasets os valores acumulados
      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[1].data = saldoquantidade
      vm.data.labels = anos

      // atualiza grafico
      vm.update()
    }, 100)
  }
}
</script>
