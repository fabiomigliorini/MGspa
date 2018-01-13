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
            <q-radio v-model="filter.codvariacao" :val="variacao.codvariacao" />
          </q-item-side>
        </q-item>
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codvariacao" val="" />
          </q-item-side>
        </q-item>
        <q-list-header>Local de Estoque</q-list-header>
        <q-item tag="label" v-for="local in item.locais" :key="local.codvariacao">
          <q-item-main>
            <q-item-tile title>{{ local.estoquelocal }}</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codestoquelocal" :val="local.codestoquelocal" />
          </q-item-side>
        </q-item>
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codestoquelocal" val="" />
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
              <mg-grafico-estoque-estatistica
                :data="data"
                :options="options"
                height="150"
              >
              </mg-grafico-estoque-estatistica>
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
              <p>Demanda média: {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}</p>
              <p>Desvio padrao: {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}</p>
              <p>Nível de servico: {{ numeral(item.estatistica.nivelservico).format('0%') }}</p>
              <p>Estoque de segurança: {{ numeral(item.estatistica.estoqueseguranca).format('0,0') }}</p>
              <p>Estoque mínimo: {{ numeral(item.estatistica.estoqueminimo).format('0,0') }} ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} Dias)</p>
              <p>Estoque máximo: {{ numeral(item.estatistica.estoquemaximo).format('0,0') }} ({{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)</p>
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
import MgLayout from '../../layouts/MgLayout'
import MgGraficoEstoqueEstatistica from '../../utils/grafico/MgGraficoEstoqueEstatistica'
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
    MgGraficoEstoqueEstatistica
  },

  data () {
    return {
      item: false,
      filter: {
        codvariacao: '',
        codestoquelocal: ''
      },
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            // backgroundColor: '#fff',
            data: null
          }
        ]
      },
      options: {
        scales: {
          xAxes: [
            {
              type: 'time',
              time: {
                displayFormats: {
                  'miliseconds': 'MMM/YYYY',
                  'second': 'MMM/YYYY',
                  'minute': 'MMM/YYYY',
                  'hour': 'MMM/YYYY',
                  'day': 'MMM/YYYY',
                  'week': 'MMM/YYYY',
                  'month': 'MMM/YYYY',
                  'quarter': 'MMM/YYYY',
                  'year': 'YYYY'
                },
                'tooltipFormat': 'MMMM/YYYY'
              }
            }
          ]
        }
      },
      codproduto: null
    }
  },
  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.loadData()
      },
      deep: true
    }

  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      let vm = this
      let params = vm.filter
      this.loading = true

      // faz chamada api
      window.axios.get('estoque-estatistica/' + vm.codproduto, { params }).then(response => {
        vm.item = response.data

        let meses = []
        let quantidades = []
        vm.item.vendas.forEach(function (value) {
          meses.push(new Date(value.mes))
          quantidades.push(value.quantidade)
        })

        vm.data.datasets[0].data = quantidades
        vm.data.labels = meses

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
