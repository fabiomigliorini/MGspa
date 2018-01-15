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
              <a @click="periodoVendas(null)" v-bind:class="{ 'periodo-ativo': (periodo == null)}">Desde Início</a> /
              <a @click="periodoVendas(36)" v-bind:class="{ 'periodo-ativo': (periodo == 36)}">3 anos</a> /
              <a @click="periodoVendas(12)" v-bind:class="{ 'periodo-ativo': (periodo == 12)}">1 ano</a> /
              <a @click="periodoVendas(6)" v-bind:class="{ 'periodo-ativo': (periodo == 6) }">6 meses</a>
            </q-card-title>
            <q-card-separator />
            <q-card-main>
              <mg-grafico-estoque-estatistica
                :chart-data="data"
                :options="options"
                :height="150"
                :periodo="periodo"
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
      periodo: null,
      data: {
        labels: [],
        datasets: [
          {
            label: 'Vendas',
            // backgroundColor: '#fff',
            data: null,
            type: 'line'
          },
          {
            label: 'Estoque',
            backgroundColor: '#ff0',
            data: [null, null, null, null, null, null, null, null, null, null, null, 1000],
            type: 'bar'
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

    periodoVendas: function (periodo) {

      let vm = this
      vm.periodo = periodo

      let periodoInicial = null

      periodoInicial = vm.moment()
      if (periodo != null) {
          periodo.add(periodo * -1, 'months')
      }
      console.log(vm.periodo)
      console.log(periodoInicial)
      return

      switch (vm.periodo) {
        case 1:
          periodoInicial = new Date(
            periodoFinal.getFullYear() -1,
            periodoFinal.getMonth(),
            periodoFinal.getDate())
          break
        case 3:
          periodoInicial = new Date(
            periodoFinal.getFullYear() -3,
            periodoFinal.getMonth(),
            periodoFinal.getDate())
          break
        case 0.5:
          periodoInicial = new Date(
            periodoFinal.getFullYear(),
            periodoFinal.getMonth() -6,
            periodoFinal.getDate())
          break
      }

      let meses = []
      let vendaquantidade = []
      let saldoquantidade = []

      vm.item.vendas.forEach(function (a) {

        let mes = vm.moment(a.mes)

        // Se mes anterior cai fora
        if (mes <= periodoInicial) {
          return false
        }

        mes = mes.endOfMonth()

        meses.push(mes)
        vendaquantidade.push(value.vendaquantidade)
        saldoquantidade.push(null)

      })

      console.log(vendaquantidade)

      estoque[2] = vm.item.saldoquantidade


      vm.data.datasets[0].data = vendaquantidade
      vm.data.datasets[1].data = saldoquantidade
      vm.data.labels = meses
    },
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      let vm = this
      let params = vm.filter
      this.loading = true

      // faz chamada api
      window.axios.get('estoque-estatistica/' + vm.codproduto, { params }).then(response => {
        vm.item = response.data
        vm.periodoVendas(vm.periodo)
        // desmarca flag de carregando
        this.loading = false
      })
    }, 500)
  },
  created () {
    this.codproduto = this.$route.params.codproduto
    this.loadData()
    console.log(this)
  }

}
</script>

<style>
.periodo-ativo {
  color: #444;
  font-weight: bold;
}
</style>
