<template >

  <q-card no-border >
    <q-card-main v-for="grupo in grupos" :key="grupo.id" class="q-pa-sm">
      <div class="row  gutter-xs" >
        <div class="col-md-8" >
          <q-card>
            <q-card-title>
              Vendas das Variações
              <q-tooltip>
                Vendas dos últimos 12 meses de cada variação, comparadas com o saldo atual do estoque.
              </q-tooltip>
            </q-card-title>
            <q-card-main >
              <grafico-vendas-estoque-variacoes-item :height="200" :variacoes="grupo.variacoes"/>
            </q-card-main>
          </q-card>
        </div>

        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Distribuição das Variações
              <q-tooltip>
                Circulo interno representa o estoque, já o externo represeta as vendas.
              </q-tooltip>
            </q-card-title>
            <q-card-main >
              <grafico-vendas-estoque-variacoes-doughnut :height="200" :variacoes="grupo.variacoes"/>
            </q-card-main>
          </q-card>
        </div>
      </div>
    </q-card-main>
  </q-card>
</template>

<script>
import GraficoVendasEstoqueVariacoesItem from './grafico-vendas-estoque-variacoes-item'
import GraficoVendasEstoqueVariacoesDoughnut from './grafico-vendas-estoque-variacoes-doughnut'

import {
  QCard,
  QCardTitle,
  QCardSeparator,
  QCardMain
} from 'quasar'

export default {
  name: 'variacoes',
  props: ['variacoes'],
  components: {
    QCard,
    QCardTitle,
    QCardSeparator,
    QCardMain,
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
