<script>
import {
  Bar,
  mixins
} from 'vue-chartjs'
const { reactiveProp } = mixins

export default {
  name: 'mg-grafico-estoque-estatistica',
  extends: Bar,
  mixins: [reactiveProp],
  props: ['chartData', 'options', 'periodo'],
  data () {
    return {
      periodo: null
    }
  },
  watch: {
    periodo: {
      handler: function (val, oldVal) {
        this.update()
      },
      deep: true
    }
  },
  mounted () {
    this.renderChart(this.chartData, this.options)
    this.periodo = this.periodo
  },
  methods: {
    update () {
      this.$data._chart.update()
    }
  }
}
</script>
