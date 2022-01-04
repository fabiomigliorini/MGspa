<template >
  <div class="row q-col-gutter-sm q-pt-sm">
    <template v-for="grupo in grupos">

      <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8" :key="grupo.id">
        <q-card class="my-card full-height">
          <q-card-section>
            <div class="text-subtitle1">Vendas das Variações</div>

            <q-tooltip>
              Vendas dos últimos 12 meses de cada variação, comparadas com o saldo atual do estoque.
            </q-tooltip>
          </q-card-section>
          <q-card-section>
            <grafico-vendas-estoque-variacoes-item :height="200" :variacoes="grupo.variacoes"/>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
        <q-card class="my-card full-height">
          <q-card-section>
            <div class="text-subtitle1">Distribuição das Variações</div>
            <q-tooltip>
              Circulo interno representa o estoque, já o externo represeta as vendas.
            </q-tooltip>
          </q-card-section>
          <q-card-main >
            <grafico-vendas-estoque-variacoes-doughnut :height="200" :variacoes="grupo.variacoes"/>
          </q-card-main>
        </q-card>
      </div>

    </template>
  </div>
</template>

<script>
import GraficoVendasEstoqueVariacoesItem from './grafico-vendas-estoque-variacoes-item'
import GraficoVendasEstoqueVariacoesDoughnut from './grafico-vendas-estoque-variacoes-doughnut'


export default {
  name: 'variacoes',
  props: ['variacoes'],
  components: {
    GraficoVendasEstoqueVariacoesItem,
    GraficoVendasEstoqueVariacoesDoughnut
  },
  data () {
    return {
      data: false
    }
  },
  computed: {
    grupos: function () {
      let vm = this

      let tamanho = 9
      let iGrupo = 0
      let iItem = 0

      let grupos = []
      grupos[0] = {
        'id': 0,
        'variacoes': []
      }

      vm.variacoes.forEach(function (item) {
        if (iItem >= tamanho) {
          iItem = 0
          iGrupo++
          grupos[iGrupo] = {
            'id': iGrupo,
            'variacoes': []
          }
        }
        grupos[iGrupo].variacoes.push(item)
        iItem++
      })

      return grupos
    }
  },
  watch: {
  },
  mounted () {
  },
  methods: {
  }
}
</script>
