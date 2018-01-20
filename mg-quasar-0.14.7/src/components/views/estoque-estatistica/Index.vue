<template>
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      #{{ numeral(item.codproduto).format('00000000') }} {{ item.produto }}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer" width="200" style="width: 200px;">
      <q-list no-border>
        <template v-if="item && item.variacoes.length > 1">
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
        </template>
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
      <div class="row" style="margin-bottom:20px">
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Saldo
            </q-card-title>
            <q-card-main>
              <q-icon color="faded" class="pull-right" name="hourglass_empty" size="80px"/>
              <big>
                <strong>29,2</strong>
                Dias
              </big>
              <br /><br />
              <p class="text-faded row">
                <span class="col-md-6">342 UN</span>
                <span class="col-md-6">
                    <q-icon name="arrow_upward" /> 347
                    <q-icon name="arrow_downward" /> 693
                </span>
              </p>
              <p class="text-faded row">
                <span class="col-md-6">
                  476,79 R$
                </span>
                <span class="col-md-6">
                  R$ 1,394118 / UN
                </span>
              </p>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Giro Anual
            </q-card-title>
            <q-card-main>
              <q-icon color="faded" class="pull-right" name="cached" size="80px"/>
              <big>
                <strong>6.648</strong>
                UN
              </big>
              <br /><br />
              <p class="text-faded">
                617 <b>Bimestre</b><br />
              </p>
              <p class="text-faded">
                2.158 <b>Semestre</b>
              </p>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Faturamento Anual
            </q-card-title>
            <q-card-main>
              <q-icon color="faded" class="pull-right" name="attach_money" size="80px"/>
              <big>
                R$
                <strong>16.832,12</strong>
              </big>
              <br /><br />
              <p class="text-faded">
                1.622,11 <b>Bimestre</b><br />
              </p>
              <p class="text-faded">
                5.480,17 <b>Semestre</b>
              </p>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Movimentação
            </q-card-title>
            <q-card-main>
              <q-icon color="faded" class="pull-right" name="swap_horiz" size="80px"/>
              <p class="text-faded">
                Entrou <br />
                <strong>há 1 mês</strong>
              </p>
              <p class="text-faded">
                Conferido <br />
                <strong>há 4 meses</strong>
              </p>
            </q-card-main>
            <!-- <q-card-actions>
              <q-btn flat color="primary">
                <q-icon name="add" />
              </q-btn>
            </q-card-actions> -->
          </q-card>
        </div>

      </div>

      <q-card>
        <q-card-title>
          Venda Mensal
          <span slot="subtitle">
            Quantidade vendida mês à mês, comparada com o saldo atual do estoque.
            O gráfico de área mostra as vendas entre Janeiro e Março de cada ano, comparadas com o saldo atual do estoque.
          </span>
        </q-card-title>
        <q-card-separator />
        <q-card-main class="row">
          <div class="col-md-6">
            <grafico-vendas-geral :height=350 :meses="meses" :vendas="item.vendas" :saldoquantidade="item.saldoquantidade"></grafico-vendas-geral>
          </div>
          <div class="col-md-3">
            <grafico-volta-aulas :height="350" :vendas="item.vendas_volta_aulas" :saldoquantidade="item.saldoquantidade"></grafico-volta-aulas>
          </div>
          <div class="col-md-3">
            <template v-if="item">
              <h5>Estatísticas</h5>
              <p>Demanda média: {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}</p>
              <p>Desvio padrao: {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}</p>
              <p>Nível de servico: {{ numeral(item.estatistica.nivelservico).format('0%') }}</p>
              <p>Estoque de segurança: {{ numeral(item.estatistica.estoqueseguranca).format('0,0') }}</p>
              <p>Estoque mínimo: {{ numeral(item.estatistica.estoqueminimo).format('0,0') }} ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} Dias)</p>
              <p>Estoque máximo: {{ numeral(item.estatistica.estoquemaximo).format('0,0') }} ({{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)</p>
              <p>Estoque quantidade: {{ numeral(item.saldoquantidade).format('0,0') }}</p>
              <p>Sugestão de compra: {{ numeral(item.estatistica.estoquemaximo - item.saldoquantidade).format('0,0') }}</p>
            </template>
          </div>
        </q-card-main>
        <q-card-actions>
          <span slot="subtitle">
            <q-btn @click="meses=null" :color="(meses == null)?'primary':''" flat>Desde Início</q-btn>
            <q-btn @click="meses=36" :color="(meses == 36)?'primary':''" flat>3 Anos</q-btn>
            <q-btn @click="meses=24" :color="(meses == 24)?'primary':''" flat>2 Anos</q-btn>
            <q-btn @click="meses=12" :color="(meses == 12)?'primary':''" flat>1 Ano</q-btn>
            <q-btn @click="meses=6" :color="(meses == 6)?'primary':''" flat>6 meses</q-btn>
          </span>
        </q-card-actions>
      </q-card>

        <!-- <div class="col-md-3">
          <q-card>
            <q-card-title>
              Volta às aulas
              <span slot="subtitle">Vendas entre Janeiro e Março de cada ano, comparadas com o saldo atual do estoque.</span>
            </q-card-title>
            <q-card-separator />
            <q-card-main>
            </q-card-main>
          </q-card>
        </div>
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Estatísticas
            </q-card-title>
            <q-card-separator />
          </q-card>
        </div>
      </div> -->

      <q-card>
        <q-card-title>
          Filiais
          <span slot="subtitle">
            Vendas dos últimos 12 meses de cada filial, comparadas com o saldo atual dos estoques.
            O gráfico em formato de anel mostra a distribuição da venda dos últimos 12 meses comparado com os estoques.
            O anel externo representa as vendas, já o interno representa os saldos atuais de estoque.
          </span>
        </q-card-title>
        <q-card-separator />
        <q-card-main class="row">
          <div  class="col-md-8">
            <grafico-vendas-ano-filiais :height="350" :locais="item.locais" :vendaquantidade="item.vendaquantidade" :saldoquantidade="item.saldoquantidade"></grafico-vendas-ano-filiais>
          </div>
          <div  class="col-md-4">
            <grafico-vendas-estoque-filiais :height="350" :locais="item.locais"></grafico-vendas-estoque-filiais>
          </div>
        </q-card-main>
      </q-card>

      <template v-if="item && item.variacoes.length > 1">
        <variacoes :height="350" :variacoes="item.variacoes"></variacoes>
      </template>
    </div>

    <div slot="footer">
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'

import GraficoVendasGeral from './grafico-vendas-geral'
import GraficoVoltaAulas from './grafico-volta-aulas'
import GraficoVendasAno from './grafico-vendas-ano'
import GraficoVendasAnoFiliais from './grafico-vendas-ano-filiais'
import GraficoVendasEstoqueFiliais from './grafico-vendas-estoque-filiais'
import Variacoes from './variacoes'

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
    GraficoVoltaAulas,
    GraficoVendasAno,
    GraficoVendasAnoFiliais,
    GraficoVendasEstoqueFiliais,
    Variacoes
  },

  data () {
    return {
      item: false,
      filter: {
        codprodutovariacao: '',
        codestoquelocal: ''
      },
      meses: 12,
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
}
p {
  margin-bottom: 0.5rem;
}
.q-card {
  height: 100%;
  margin-bottom: 0;
}
</style>
