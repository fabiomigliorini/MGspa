<script>
import { Line } from 'vue-chartjs'
export default {
  extends: Line,
  name: 'mg-grafico-estoque-estatistica',
  props: ['data', 'options'],
  mounted () {
    this.renderChart(this.data, this.options)
  }
}
</script>
