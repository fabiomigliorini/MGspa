<template>
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      #{{ numeral(item.codproduto).format('00000000') }} {{ item.produto }}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer" width="200" style="width: 200px;">
      <q-list no-border>

        <!-- FILTRO DE LOCAIS DE ESTOQUE -->
        <q-list-header>Local de Estoque</q-list-header>

        <!-- LOCAIS -->
        <q-item tag="label" v-for="local in item.locais" :key="local.codprodutovariacao">
          <q-item-main>
            <q-item-tile title>{{ local.estoquelocal }}</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codestoquelocal" :val="local.codestoquelocal" />
          </q-item-side>
        </q-item>

        <!-- TODOS LOCAIS -->
        <q-item tag="label">
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.codestoquelocal" val="" />
          </q-item-side>
        </q-item>

        <!-- FILTRO DE VARIACOES -->
        <template v-if="item && item.variacoes.length > 1">

          <!-- VARIACOES ATIVAS -->
          <template v-if="variacoesAtivas.length > 0">
            <q-list-header>Variações Ativas</q-list-header>
            <q-item tag="label" v-for="variacao in variacoesAtivas" :key="variacao.codprodutovariacao">
              <q-item-main>
                <q-item-tile title>{{ variacao.variacao }}</q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-side>
            </q-item>
          </template>

          <!-- VARIACOES DESCONTINUADAS -->
          <template v-if="variacoesDescontinuadas.length > 0">
            <q-list-header>Variações Descontinuadas</q-list-header>
            <q-item tag="label" v-for="variacao in variacoesDescontinuadas" :key="variacao.codprodutovariacao">
              <q-item-main>
                <q-item-tile title>{{ variacao.variacao }}</q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-side>
            </q-item>
          </template>

          <!-- VARIACOES INATIVAS -->
          <template v-if="variacoesInativas.length > 0">
            <q-list-header>Variações Inativas</q-list-header>
            <q-item tag="label" v-for="variacao in variacoesInativas" :key="variacao.codprodutovariacao">
              <q-item-main>
                <q-item-tile title>{{ variacao.variacao }}</q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-side>
            </q-item>
          </template>

          <!-- TODAS VARIACOES -->
          <q-item tag="label">
            <q-item-main>
              <q-item-tile title>Todos</q-item-tile>
            </q-item-main>
            <q-item-side right>
              <q-radio v-model="filter.codprodutovariacao" val="" />
            </q-item-side>
          </q-item>
        </template>
      </q-list>
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">


      <div class="row q-pa-sm gutter-xs">

        <div class="col-md-6">
          <q-card>
            <q-card-title>
              Venda Mensal
              <q-tooltip>
                Quantidade vendida mês à mês,
                comparada com o saldo do estoque.
              </q-tooltip>
            </q-card-title>
            <q-card-main>
              <grafico-vendas-geral :height="148" :meses="meses" :vendas="item.vendas" :saldoquantidade="item.saldoquantidade"/>
            </q-card-main >
            <q-card-actions>
              <q-btn @click.native="meses=null" :color="(meses == null)?'primary':''" flat>Desde Início</q-btn>
              <q-btn @click.native="meses=36" :color="(meses == 36)?'primary':''" flat>3 Anos</q-btn>
              <q-btn @click.native="meses=24" :color="(meses == 24)?'primary':''" flat>2 Anos</q-btn>
              <q-btn @click.native="meses=12" :color="(meses == 12)?'primary':''" flat>1 Ano</q-btn>
              <q-btn @click.native="meses=6" :color="(meses == 6)?'primary':''" flat>6 meses</q-btn>
            </q-card-actions>
          </q-card>
        </div>

        <!-- grafico recomendado -->
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Recomendado
              <small class="text-grey" v-if="item">
                ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} -
                {{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)
              </small>
              <q-tooltip>
                Estoque recomendado calculado com base no volume de vendas.
                <div class="row" v-if="item">
                  <div class="col-md-5">
                    <q-list dense no-border>
                      <q-item>
                        <q-item-main >
                          Média: {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}
                        </q-item-main>
                      </q-item>

                      <q-item>
                        <q-item-main >
                          Desvio: {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}
                        </q-item-main>
                      </q-item>

                      <q-item>
                        <q-item-main >
                          Servico: {{ numeral(item.estatistica.nivelservico).format('0%') }}
                        </q-item-main>
                      </q-item>

                      <q-item>
                        <q-item-main class="text-green" v-if="item.saldoquantidade > item.estatistica.estoqueminimo">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-main>
                        <q-item-main class="text-orange" v-else-if="item.saldoquantidade < item.estatistica.estoqueminimo && item.saldoquantidade > item.estatistica.estoqueseguranca">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-main>
                        <q-item-main class="text-red" v-else-if="item.saldoquantidade < item.estatistica.estoqueseguranca">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-main>
                      </q-item>
                    </q-list>
                  </div>
                </div>
              </q-tooltip>
            </q-card-title>

            <q-card-main>
              <grafico-estatistica :height="150" :estatistica="item.estatistica" :saldoquantidade="item.saldoquantidade" :vendaquantidade="item.vendaquantidade"/>
            </q-card-main>

            <q-card-main v-if="item" class="text-center">
              <span class="text-green" v-if="item.estatistica.estoquemaximo - item.saldoquantidade > 0">
                Comprar <b>{{ numeral(item.estatistica.estoquemaximo - item.saldoquantidade).format('0,0') }}</b> unidade(s)
              </span>
              <span class="text-red" v-else-if="item.estatistica.estoquemaximo - item.saldoquantidade < 0">
                <b>{{ numeral(item.saldoquantidade - item.estatistica.estoquemaximo).format('0,0') }}</b> unidade(s) excedente(s)
              </span>
              &nbsp
            </q-card-main>

          </q-card>
        </div>

        <!-- Grafico votas as aulas -->
        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Venda Volta às Aulas
              <q-tooltip>
                Vendas entre Janeiro e Março de cada ano,
                comparadas com o saldo atual do estoque.
              </q-tooltip>
            </q-card-title>
            <q-card-main>
              <grafico-volta-aulas :height="200"  :vendas="item.vendas_volta_aulas" :saldoquantidade="item.saldoquantidade"></grafico-volta-aulas>
            </q-card-main>
          </q-card>
        </div>

        <!-- fim da primeira row -->
      </div>

      <!-- Grafico vendas das Filiais -->
      <div class="row q-pa-sm gutter-xs">
        <div class="col-md-8">
          <q-card>
            <q-card-title>
              Vendas das Filiais
              <q-tooltip>
                Vendas dos últimos 12 meses de cada filial, comparadas com o saldo atual dos estoques.
              </q-tooltip>
            </q-card-title>
            <q-card-main>
              <grafico-vendas-ano-filiais :height="200" :locais="item.locais" :vendaquantidade="item.vendaquantidade" :saldoquantidade="item.saldoquantidade"/>
            </q-card-main>
          </q-card>
        </div>

        <!-- Grafico da distribuicao do estoque -->
        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Distribuição das Filiais
              <q-tooltip>
                Distribuição da venda dos últimos 12 meses comparado com os estoques.<br />
                O anel externo representa as vendas, já o interno representa os saldos atuais de estoque.
              </q-tooltip>
            </q-card-title>
            <q-card-main>
              <grafico-vendas-estoque-filiais :height="200" :locais="item.locais"></grafico-vendas-estoque-filiais>
            </q-card-main>
          </q-card>
        </div>
      </div>

      <template v-if="item && item.variacoes.length > 1">
        <variacoes :variacoes="item.variacoes"/>
      </template>

      <!-- termina o div slot -->
    </div>
  </mg-layout>
</template>

<script>

import { debounce } from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
import GraficoVendasGeral from './grafico-vendas-geral'
import GraficoVoltaAulas from './grafico-volta-aulas'
import GraficoVendasAno from './grafico-vendas-ano'
import GraficoVendasAnoFiliais from './grafico-vendas-ano-filiais'
import GraficoVendasEstoqueFiliais from './grafico-vendas-estoque-filiais'
import GraficoEstatistica from './grafico-estatistica'
import Variacoes from './variacoes'

export default {

  components: {
    debounce,
    MgLayout,
    GraficoVendasGeral,
    GraficoVoltaAulas,
    GraficoVendasAno,
    GraficoVendasAnoFiliais,
    GraficoVendasEstoqueFiliais,
    GraficoEstatistica,
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

  computed: {
    variacoesAtivas: function() {
      return this.item.variacoes.filter(function(variacao) {
        return (variacao.inativo == null) && (variacao.descontinuado == null)
      })
    },
    variacoesDescontinuadas: function() {
      return this.item.variacoes.filter(function(variacao) {
        return (variacao.inativo == null) && (variacao.descontinuado != null)
      })
    },
    variacoesInativas: function() {
      return this.item.variacoes.filter(function(variacao) {
        return (variacao.inativo != null)
      })
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
      vm.$axios.get('estoque-estatistica/' + vm.codproduto, { params }).then(response => {
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
</style>
