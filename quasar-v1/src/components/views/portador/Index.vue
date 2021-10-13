<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/" :left-drawer="true">
    <!-- Título da Página -->
    <template slot="title">
      {{portador.portador}}
      -
      {{mes}}/{{ano}}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">
      <!-- Ordena por Vendas -->
      <q-item dense v-for="p in portadores" clickable v-ripple @click="codportador = p.codportador">
        <q-item-section side>
          <q-radio v-model="codportador" :val="p.codportador" />
        </q-item-section>
        <q-item-section>
          {{p.portador}}
        </q-item-section>
        <q-item-section side v-if="(p.extratoconciliar + p.movimentoconciliar)>-0">
          <q-badge rounded color="red" :label="p.movimentoconciliar + p.extratoconciliar" />
        </q-item-section>
      </q-item>


    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">
      <q-tabs v-model="ano" dense >
        <q-tab v-for="i in [2021, 2022]" :name="i" :label="i" />
      </q-tabs>
      <q-tabs v-model="mes" dense >
        <q-tab v-for="i in meses" :name="i.mes" :label="i.descricao">
          <q-badge small rounded color="red" floating></q-badge>
        </q-tab>
      </q-tabs>


      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll @load="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <q-item :to="'/marca/' + item.codmarca" >

              <!-- Imagem -->
              <q-item-section avatar>
                <q-avatar square>
                  <img :src="item.imagem.url" v-if="item.imagem">
                  <img src="/statics/no-image-4-4.svg" v-else/>
                </q-avatar>
              </q-item-section>

              </q-item-section>

              <!-- Coluna 1 -->
              <q-item-section >
                <q-item-label>
                  {{ item.marca }}
                  <q-chip tag square pointing="left" color="negative" v-if="item.inativo">Inativo</q-chip>
                </q-item-label>
                <q-item-label caption>
                  #{{ numeral(item.codmarca).format('00000000') }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-xs">
                <q-item-label class="row" caption>
                  <div class="col-6">
                    <template v-if="item.itensabaixominimo > 0">
                      {{ numeral(item.itensabaixominimo).format('0,0') }} <q-icon name="arrow_downward" />
                    </template>
                    <template v-if="item.itensacimamaximo > 0">
                      <q-icon name="arrow_upward" /> {{ numeral(item.itensacimamaximo).format('0,0') }}
                    </template>
                  </div>
                  <div class="col-6 text-center">
                    <template v-if="item.dataultimacompra" class="text-grey">
                      <q-icon name="add_shopping_cart" />
                      {{ moment(item.dataultimacompra).fromNow() }}
                    </template>
                  </div>
                </q-item-label>

                <q-item-label caption >
                  <q-icon name="date_range" />
                  {{ item.estoqueminimodias }} à
                  {{ item.estoquemaximodias }} Dias
                </q-item-label>
              </q-item-section>

              <!-- Direita (Estrelas) -->
              <q-item-section avatar>
                <q-item-label v-if="!item.abcignorar">
                  <q-rating readonly v-model="item.abccategoria" :max="3" size="1.7rem" />
                </q-item-label>
                <q-item-label caption>
                  {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%
                  <template v-if="item.abcposicao">
                    ({{ numeral(item.abcposicao).format('0,0') }}&deg;)
                  </template>
                </q-item-label>
              </q-item-section>

            </q-item>
            <q-separator />

          </template>
        </q-infinite-scroll>
      </q-list>

      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="add" color="primary" :to="{ path: '/marca/create' }"/>
      </q-page-sticky>

    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import { debounce } from 'quasar'
import _ from 'lodash';

export default {

  components: {
    MgLayout,
    MgNoData
  },

  data () {
    return {
      codportador: null,
      portadores: [],
      portador: {
        codportador: null,
        portador: 'Portador',
      },
      meses: [
        {
          descricao: 'Jan/2021',
          mes: 1,
          ano: 2021,
        },
        {
          descricao: 'Fev/2021',
          mes: 2,
          ano: 2021,
        }
      ],
      mes: null,
      ano: null,
      meses: [
        {mes:1, descricao:'Jan'},
        {mes:2, descricao:'Fev'},
        {mes:3, descricao:'Mar'},
        {mes:4, descricao:'Abr'},
        {mes:5, descricao:'Mai'},
        {mes:6, descricao:'Jun'},
        {mes:7, descricao:'Jul'},
        {mes:8, descricao:'Ago'},
        {mes:9, descricao:'Set'},
        {mes:10, descricao:'Out'},
        {mes:11, descricao:'Nov'},
        {mes:12, descricao:'Dez'},
      ],
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      loading: true
    }
  },

  watch: {

    // observa filtro, sempre que alterado chama a api
    codportador: function() {
      this.parsePortador();
    },
    ano: function() {
      this.atualizarUrl();
    },
    mes: function() {
      this.atualizarUrl();
    },
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    },

  },

  methods: {

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadPortadores: debounce(function (concat, done) {
      var vm = this
      this.loading = true
      vm.$axios.get('portador').then(response => {
        vm.portadores = response.data.data;
        vm.parsePortador();
      })
    }, 500),

    atualizarUrl: function() {
      this.$router.replace({ path: '/portador/' + this.codportador + '/' + this.ano + '/' + this.mes});
    },

    parsePortador: function() {
      const portador = this.portadores.find(item => item.codportador == this.codportador);
      if (portador != undefined) {
        this.portador = portador;
      } else {
        this.portador = this.portadores[0];
        this.codportador = this.portador.codportador;
      }
      this.atualizarUrl();
    },

    trocarMes: function (m) {
      // this.mes = m.mes;
      // this.ano = m.ano;
      console.log(m);
    }

  },

  // na criacao, busca filtro do Vuex
  created () {

    // codportador
    var param = this.$route.params.codportador;
    if (param != null) {
      this.codportador = parseInt(param);
    }

    // ano
    param = this.$route.params.ano;
    var d = this.moment();
    if (param == null) {
      this.ano = parseInt(d.format('Y'));
    } else {
      this.ano = parseInt(param)
    }

    // mes
    param = this.$route.params.mes;
    if (param == null) {
      this.mes = parseInt(d.format('M'));
    } else {
      this.mes = parseInt(param)
    }

    this.loadPortadores();
  }

}
</script>

<style>
</style>
