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
        <q-item tag="label" v-for="variacao in item.variacoes" :key="variacao.codprodutovariacao">
          <q-item-main>
            <q-item-tile title>{{ variacao.variacao }}</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
          </q-item-side>
        </q-item>
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codprodutovariacao" val="" />
          </q-item-side>
        </q-item>
        <q-list-header>Local de Estoque</q-list-header>
        <q-item tag="label" v-for="local in item.locais" :key="local.codprodutovariacao">
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
        <div class="col-md-6">
          <q-card>
            <q-card-title>
              Venda Mensal
              <span slot="subtitle">Quantidade vendida mês à mês, comparada com o saldo atual do estoque.</span>
            </q-card-title>
            <q-card-main>
              <grafico-vendas-geral :meses="meses" :vendas="item.vendas" :saldoquantidade="item.saldoquantidade"></grafico-vendas-geral>
            </q-card-main>
            <q-card-actions>
              <span slot="subtitle">
                <q-btn @click="meses=null" :color="(meses == null)?'primary':''" flat>Desde Início</q-btn>
                <q-btn @click="meses=36" :color="(meses == 36)?'primary':''" flat>3 Anos</q-btn>
                <q-btn @click="meses=12" :color="(meses == 12)?'primary':''" flat>1 Ano</q-btn>
                <q-btn @click="meses=6" :color="(meses == 6)?'primary':''" flat>6 meses</q-btn>
              </span>
            </q-card-actions>
          </q-card>
        </div>
        <div class="col-md-4">
          <q-card>
            <q-card-separator />
            <q-card-title>
              Volta às aulas
              <span slot="subtitle">Consideradas somente vendas de Janeiro à Março de cada ano.</span>
            </q-card-title>
            <q-card-main>
              <grafico-volta-aulas :vendas="item.vendas_volta_aulas" :saldoquantidade="item.saldoquantidade"></grafico-volta-aulas>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-2">
          <q-card>
            <q-card-title>
              Vendas ano
            </q-card-title>
            <q-card-separator />
            <q-card-main>
              ...
            </q-card-main>
          </q-card>
        </div>
      </div>

      <q-card>
        <q-card-title>
          Estatísticas
        </q-card-title>
        <q-card-separator />
        <q-card-main v-if="item">
          <p>Demanda média: {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}</p>
          <p>Desvio padrao: {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}</p>
          <p>Nível de servico: {{ numeral(item.estatistica.nivelservico).format('0%') }}</p>
          <p>Estoque de segurança: {{ numeral(item.estatistica.estoqueseguranca).format('0,0') }}</p>
          <p>Estoque mínimo: {{ numeral(item.estatistica.estoqueminimo).format('0,0') }} ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} Dias)</p>
          <p>Estoque máximo: {{ numeral(item.estatistica.estoquemaximo).format('0,0') }} ({{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)</p>
        </q-card-main>
      </q-card>

    </div>

    <div slot="footer">
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'

import GraficoVendasGeral from './grafico-vendas-geral'
import GraficoVoltaAulas from './grafico-volta-aulas'

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
    GraficoVendasGeral,
    GraficoVoltaAulas
  },

  data () {
    return {
      item: false,
      filter: {
        codprodutovariacao: '',
        codestoquelocal: ''
      },
      meses: null,
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
.periodo-ativo {
  color: #AAA;
  /* font-weight: bold; */
}
</style>
