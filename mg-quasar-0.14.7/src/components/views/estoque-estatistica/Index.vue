<template>
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      #{{ numeral(item.codproduto).format('00000000') }} {{ item.produto }}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer" width="200" style="width: 200px;">
      <q-list no-border>
        <q-list-header>Variações</q-list-header>
        <q-item tag="label" v-for="variacao in item.variacoes" :key="variacao.codvariacao">
          <q-item-main>
            <q-item-tile title>{{ variacao.variacao }}</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.a" val="a" />
          </q-item-side>
        </q-item>
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.a" val="a" />
          </q-item-side>
        </q-item>
        <q-list-header>Local de Estoque</q-list-header>
        <q-item tag="label" v-for="local in item.locais" :key="local.codvariacao">
          <q-item-main>
            <q-item-tile title>{{ local.estoquelocal }}</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.a" val="a" />
          </q-item-side>
        </q-item>
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.a" val="a" />
          </q-item-side>
        </q-item>
      </q-list>
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <div class="row">
        <div class="col-md-9">
          <q-card>
            <q-card-title>
              Desde início / 3 anos / 1 ano / 6 meses
            </q-card-title>
            <q-card-separator />
            <q-card-main>
              <div id="chart1"></div>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Estatísticas
            </q-card-title>
            <q-card-separator />
            <q-card-main>
              <p>Demanda média: {{ item.estatistica.demanda_media }}</p>
              <p>Desvio padrao: {{ item.estatistica.desvio_padrao }}</p>
              <p>Estoque máximo: {{ item.estatistica.estoque_maximo }}</p>
              <p>Estoque seguranca:{{ item.estatistica.estoque_seguranca }}</p>
              <p>Nível de servico: {{ item.estatistica.nivel_servico }}</p>
              <p>Ponto de pedido: {{ item.estatistica.ponto_pedido }}</p>
            </q-card-main>
          </q-card>
        </div>
      </div>

<!--
      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="edit"
          active-icon="edit"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/marca/' + item.codmarca + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-fixed-position>
 -->
    </div>

    <div slot="footer">
    </div>

  </mg-layout>
</template>

<script>
import { GoogleCharts } from 'google-charts'

function drawChart (serie) {
  const data = GoogleCharts.api.visualization.arrayToDataTable([serie])
  const pieChart = new GoogleCharts.api.visualization.PieChart(document.getElementById('chart1'))
  pieChart.draw(data)
}

import MgLayout from '../../layouts/MgLayout'
import {
  QIcon,
  QCard,
  QCardMedia,
  QCardTitle,
  QCardSeparator,
  QCardActions,
  QRating,
  debounce,
  QBtn,
  QFixedPosition,
  QFab,
  QFabAction,
  QTooltip,
  QCardMain,
  QToggle,
  QCollapsible,
  QList,
  QPopover,
  QItem,
  QItemMain,
  QItemTile,
  QItemSide,
  QRadio,
  QListHeader
} from 'quasar'

export default {

  components: {
    MgLayout,
    QIcon,
    QCard,
    QCardMedia,
    QCardTitle,
    QCardMain,
    QCardSeparator,
    QCardActions,
    QRating,
    QBtn,
    QFixedPosition,
    QFabAction,
    QFab,
    QTooltip,
    QToggle,
    QCollapsible,
    QList,
    QPopover,
    QItem,
    QItemMain,
    QItemTile,
    QItemSide,
    QRadio,
    QListHeader,
    GoogleCharts
  },

  data () {
    return {
      item: false,
      filter: {},
      codproduto: null
    }
  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      let vm = this
      let params = {
        codprodutovariacao: null,
        codestoquelocal: null
      }
      this.loading = true
      GoogleCharts.load(drawChart)


      // faz chamada api
      window.axios.get('estoque-estatistica/' + vm.codproduto, { params }).then(response => {
        vm.item = response.data
        drawChart(vm.item.estatistica.serie)
        // console.log(grafico)

        // desmarca flag de carregando
        this.loading = false
      })
    }, 500)
  },
  created () {
    this.codproduto = this.$route.params.codproduto
    this.loadData()
  }

}
</script>

<style>
</style>
