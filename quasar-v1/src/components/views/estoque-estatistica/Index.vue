<template>
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      #{{ numeral(item.codproduto).format('00000000') }} {{ item.produto }}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">

      <q-list>

        <!-- FILTRO DE LOCAIS DE ESTOQUE -->
        <q-item-label class="text-subtitle1 text-grey-7 q-pa-sm">
          Local de Estoque
        </q-item-label>

        <!-- LOCAIS -->
        <q-item dense v-for="local in item.locais" :key="local.codprodutovariacao">
          <q-item-section>
            <q-item-label>{{ local.estoquelocal }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-radio v-model="filter.codestoquelocal" :val="local.codestoquelocal" />
          </q-item-section>
        </q-item>

        <!-- TODOS LOCAIS -->
        <q-item dense>
          <q-item-section>
            <q-item-label>Todos</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-radio v-model="filter.codestoquelocal" val="" />
          </q-item-section>
        </q-item>
        <q-separator/>

        <!-- FILTRO DE VARIACOES -->
        <template v-if="item && item.variacoes.length > 1">

          <!-- VARIACOES ATIVAS -->
          <template v-if="variacoesAtivas.length > 0">
            <q-item-label class="text-subtitle1 text-grey-7 q-pa-sm">Variações Ativas</q-item-label>
            <q-item dense v-for="variacao in variacoesAtivas" :key="variacao.codprodutovariacao">
              <q-item-section>
                <q-item-label>{{ variacao.variacao }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-section>
            </q-item>
            <q-separator/>
          </template>

          <!-- VARIACOES DESCONTINUADAS -->
          <template v-if="variacoesDescontinuadas.length > 0">
            <q-item-label class="text-subtitle1 text-grey-7">Variações Descontinuadas</q-item-label>
            <q-item dense v-for="variacao in variacoesDescontinuadas" :key="variacao.codprodutovariacao">
              <q-item-section>
                <q-item-label>{{ variacao.variacao }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-section>
            </q-item>
            <q-separator/>
          </template>

          <!-- VARIACOES INATIVAS -->
          <template v-if="variacoesInativas.length > 0">
            <q-item-label class="text-grey-7 text-subtitle1">Variações Inativas</q-item-label>
            <q-item dense v-for="variacao in variacoesInativas" :key="variacao.codprodutovariacao">
              <q-item-section>
                <q-item-label>{{ variacao.variacao }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-radio v-model="filter.codprodutovariacao" :val="variacao.codprodutovariacao" />
              </q-item-section>
            </q-item>
            <q-separator/>
          </template>

          <!-- TODAS VARIACOES -->
          <q-item dense>
            <q-item-section>
              <q-item-label>Todos</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-radio v-model="filter.codprodutovariacao" val="" />
            </q-item-section>
          </q-item>
        </template>
      </q-list>
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">
      <div class="row q-col-gutter-sm">

        <!--GRAFICO VENDA MENSAL-->
        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
          <q-card class="my-card full-height">
            <q-card-section class="text-subtitle1">
              Venda Mensal
              <q-tooltip>
                Quantidade vendida mês à mês,
                comparada com o saldo do estoque.
              </q-tooltip>
            </q-card-section>
            <q-card-section>
              <grafico-vendas-geral :height="148" :meses="meses" :vendas="item.vendas" :saldoquantidade="item.saldoquantidade"/>
            </q-card-section >
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
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
          <q-card class="my-card full-height">

            <q-card-section>
              <span class="text-subtitle1">Recomendado</span>
              <small class="text-grey" v-if="item">
                ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} -
                {{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)
              </small>
              <q-tooltip>
                Estoque recomendado calculado com base no volume de vendas.
                <div class="row" v-if="item">
                  <div class="col-5">
                    <q-list >
                      <q-item dense>
                        <q-item-section>
                          Média: {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}
                        </q-item-section>
                      </q-item>

                      <q-item dense>
                        <q-item-section>
                          Desvio: {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}
                        </q-item-section>
                      </q-item>

                      <q-item dense>
                        <q-item-section>
                          Servico: {{ numeral(item.estatistica.nivelservico).format('0%') }}
                        </q-item-section>
                      </q-item>

                      <q-item dense>
                        <q-item-section class="text-green" v-if="item.saldoquantidade > item.estatistica.estoqueminimo">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-section>
                        <q-item-section class="text-orange" v-else-if="item.saldoquantidade < item.estatistica.estoqueminimo && item.saldoquantidade > item.estatistica.estoqueseguranca">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-section>
                        <q-item-section class="text-red" v-else-if="item.saldoquantidade < item.estatistica.estoqueseguranca">
                          Saldo: {{ numeral(item.saldoquantidade).format('0,0') }}
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </div>
                </div>
              </q-tooltip>
            </q-card-section>

            <q-card-section>
              <grafico-estatistica :height="150" :estatistica="item.estatistica" :saldoquantidade="item.saldoquantidade" :vendaquantidade="item.vendaquantidade"/>
            </q-card-section>

            <q-card-section v-if="item" class="text-center">
              <span class="text-green" v-if="item.estatistica.estoquemaximo - item.saldoquantidade > 0">
                Comprar <b>{{ numeral(item.estatistica.estoquemaximo - item.saldoquantidade).format('0,0') }}</b> unidade(s)
              </span>
              <span class="text-red" v-else-if="item.estatistica.estoquemaximo - item.saldoquantidade < 0">
                <b>{{ numeral(item.saldoquantidade - item.estatistica.estoquemaximo).format('0,0') }}</b> unidade(s) excedente(s)
              </span>
              &nbsp
            </q-card-section>

          </q-card>
        </div>

        <!-- Grafico votas as aulas -->
        <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
          <q-card class="my-card full-height">

            <q-card-section class="text-subtitle1">
              Venda Volta às Aulas
              <q-tooltip>
                Vendas entre Janeiro e Março de cada ano,
                comparadas com o saldo atual do estoque.
              </q-tooltip>
            </q-card-section>

            <q-card-section>
              <grafico-volta-aulas :height="200"  :vendas="item.vendas_volta_aulas" :saldoquantidade="item.saldoquantidade"></grafico-volta-aulas>
            </q-card-section>

          </q-card>
        </div>
      </div>

      <!-- Grafico vendas das Filiais -->
      <div class="row q-col-gutter-sm q-pt-sm">
        <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
          <q-card class="my-card full-height">

            <q-card-section class="text-subtitle1">
              Vendas das Filiais
              <q-tooltip>
                Vendas dos últimos 12 meses de cada filial, comparadas com o saldo atual dos estoques.
              </q-tooltip>
            </q-card-section>

            <q-card-section>
              <grafico-vendas-ano-filiais :height="200" :locais="item.locais" :vendaquantidade="item.vendaquantidade" :saldoquantidade="item.saldoquantidade"/>
            </q-card-section>

          </q-card>
        </div>

        <!-- Grafico da distribuicao do estoque -->
        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
          <q-card class="my-card full-height">

            <q-card-section class="text-subtitle1">
              Distribuição das Filiais
              <q-tooltip>
                Distribuição da venda dos últimos 12 meses comparado com os estoques.<br />
                O anel externo representa as vendas, já o interno representa os saldos atuais de estoque.
              </q-tooltip>
            </q-card-section>

            <q-card-section>
              <grafico-vendas-estoque-filiais :height="200" :locais="item.locais"></grafico-vendas-estoque-filiais>
            </q-card-section>

          </q-card>
        </div>
      </div>

      <div class="row" v-if="item && item.variacoes.length > 1">
        <div class="col-12">
          <variacoes :variacoes="item.variacoes"/>
        </div>
      </div>

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
      let vm = this;
      let params = vm.filter;
      this.loading = true;

      // faz chamada api
      vm.$axios.get('estoque-estatistica/' + vm.codproduto, { params }).then(response => {
        vm.item = response.data;
        this.loading = false
      })
    }, 500)
  },
  created () {
    this.codproduto = this.$route.params.codproduto;
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
