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


      <div class="row">

        <div class="col-md-6">
          <q-card class="q-ma-sm">
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

        <div class="col-md-3">
          <q-card class="q-ma-sm">
            <q-card-title>
              Recomendado
              <small class="text-grey">
                (60-90 DD)
              </small>
              <q-tooltip>
                Estoque recomendado calculado com base no volume de vendas.
              </q-tooltip>
            </q-card-title>
            <q-card-main>
              <grafico-estatistica :height="200" :estatistica="item.estatistica" :saldoquantidade="item.saldoquantidade" :vendaquantidade="item.vendaquantidade"/>
            </q-card-main>
          </q-card>
        </div>

        <div class="col-md-3">
          <q-card class="q-ma-sm">
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

      </div>

      <div class="row q-pa-sm gutter-xs">
        <div class="col-md-6">
          <q-card>
            <div style="padding-left:10px; padding-top:10px">
              <q-card-title>
                Vendas das Filiais
                <q-tooltip anchor="bottom left" self="top right">
                  Vendas dos últimos 12 meses de cada filial, comparadas com o saldo atual dos estoques.<br />
                  O gráfico em formato de anel mostra a distribuição da venda dos últimos 12 meses comparado com os estoques.<br />
                  O anel externo representa as vendas, já o interno representa os saldos atuais de estoque.
                </q-tooltip>
              </q-card-title>
            </div>
            <q-card-main>
              <grafico-vendas-ano-filiais :height="200" :locais="item.locais" :vendaquantidade="item.vendaquantidade" :saldoquantidade="item.saldoquantidade"/>
            </q-card-main>
          </q-card>
        </div>

        <div class="col-md-3">
          <q-card>
            <q-card-title>
              Distribuição do Estoque
            </q-card-title>
            <q-card-main>
              <grafico-vendas-estoque-filiais :height="200" :locais="item.locais"></grafico-vendas-estoque-filiais>
            </q-card-main>
          </q-card>
        </div>
      </div>
    </div>

    <div class="row q-pa-sm gutter-xs">
      <div class="col-md-3">
        <q-card >
          <div style="padding-left:10px; padding-top:10px">
            <strong>
              Estatísticas
              <!-- <q-tooltip anchor="bottom left" self="top right"></q-tooltip> -->
            </strong>
          </div>
          <q-card-separator />
          <q-card-main>
            <template v-if="item">
              <q-list dense no-border>
                <q-item>
                  <q-item-side >
                    Média:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.demandamedia).format('0,0.0000') }}
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Desvio:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.desviopadrao).format('0,0.0000') }}
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Servico:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.nivelservico).format('0%') }}
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Segurança:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.estoqueseguranca).format('0,0') }}
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Mínimo:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.estoqueminimo).format('0,0') }} ({{ numeral(item.estatistica.tempominimo * 30).format('0,0') }} Dias)
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Máximo:
                  </q-item-side>
                  <q-item-main align="end">
                    {{ numeral(item.estatistica.estoquemaximo).format('0,0') }} ({{ numeral(item.estatistica.tempomaximo * 30).format('0,0') }} Dias)
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side>
                    Saldo:
                  </q-item-side>
                  <q-item-main align="end" class="text-green" v-if="item.saldoquantidade > item.estatistica.estoqueminimo">
                    {{ numeral(item.saldoquantidade).format('0,0') }}
                  </q-item-main>
                  <q-item-main align="end" class="text-orange" v-else-if="item.saldoquantidade < item.estatistica.estoqueminimo && item.saldoquantidade > item.estatistica.estoqueseguranca">
                    {{ numeral(item.saldoquantidade).format('0,0') }}
                  </q-item-main>
                  <q-item-main align="end" class="text-red" v-else-if="item.saldoquantidade < item.estatistica.estoqueseguranca">
                    {{ numeral(item.saldoquantidade).format('0,0') }}
                  </q-item-main>
                </q-item>

                <q-item>
                  <q-item-side >
                    Comprar:
                  </q-item-side>
                  <q-item-main align="end" class="text-green" v-if="item.estatistica.estoquemaximo - item.saldoquantidade > 0">
                    {{ numeral(item.estatistica.estoquemaximo - item.saldoquantidade).format('0,0') }}
                  </q-item-main>
                  <q-item-main align="end" class="text-red" v-else-if="item.estatistica.estoquemaximo - item.saldoquantidade < 0">
                    {{ numeral(item.estatistica.estoquemaximo - item.saldoquantidade).format('0,0') }}
                  </q-item-main>
                </q-item>

              </q-list>
            </template>
          </q-card-main>
        </q-card>
      </div>
    </div>

    <template v-if="item && item.variacoes.length > 1">
      <div class="row q-pa-sm gutter-xs">
        <div class="col-md-12">
          <q-card>
            <div style="padding-left:10px; padding-top:10px">
              <strong>
                Variações
                <!-- <q-tooltip anchor="bottom left" self="top right"></q-tooltip> -->
              </strong>
            </div>
            <q-card-separator />
            <q-card-main>
              <variacoes :variacoes="item.variacoes"></variacoes>
            </q-card-main>
          </q-card>
        </div>
      </div>
    </template>

    <!-- <div class="col-md-3">
    <q-card>
    <q-card-title>
    Volta às aulas
    <span slot="subtitle"></span>
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
        console.log(vm.item)
        console.log(vm.item.estatistica.estoqueminimo)
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
