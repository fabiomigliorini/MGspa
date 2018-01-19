<template>
  <div>
    <div v-for="(grupos, index) in data">
      <grafico-vendas-estoque-variacoes-item :chart-id="'chart' + index" :height="150" :variacoes="grupos"></grafico-vendas-estoque-variacoes-item>
    </div>
  </div>
</template>

<script>
import { debounce } from 'quasar'
// import { GraficoVendasEstoqueVariacoesItem } from './grafico-vendas-estoque-variacoes-item'
const GraficoVendasEstoqueVariacoesItem = require('./grafico-vendas-estoque-variacoes-item').default
export default {
  name: 'grafico-vendas-estoque-variacoes',
  props: ['variacoes'],
  components: {
    GraficoVendasEstoqueVariacoesItem
  },
  data () {
    return {
      data: false
    }
  },
  watch: {
    variacoes: {
      handler: function (val, oldVal) {
        this.montaDados()
      },
      deep: true
    }
  },
  mounted () {
    // console.log(this.$options.components)
    this.montaDados()
  },
  methods: {
    montaDados: debounce(function () {
      let vm = this
      let grupos = []
      let i = 0
      let tamanho = 10

      vm.variacoes.forEach(function () {
        i += tamanho
        grupos.push(vm.variacoes.slice(i, i + tamanho))
      })

      vm.data = grupos.filter(function (grupo) {
        return grupo.length > 0
      })
    }, 100)
  }
}
</script>
