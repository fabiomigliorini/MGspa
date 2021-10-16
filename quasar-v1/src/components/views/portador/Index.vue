<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/" :left-drawer="true">
    <!-- Título da Página -->
    <template slot="title">
      {{portador.portador}}
      - {{mes}}/{{ano}}
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">
      <!--
      <div class="row q-pa-md q-gutter-md">
        <q-btn icon="cloud_upload" class="col-12" label="Importar OFX" color="primary" @click="dialogImportarOfx = !dialogImportarOfx" />
      </div>
      -->
      <!-- Ordena por Vendas -->
      <q-item dense v-for="p in portadores" clickable v-ripple @click="codportador = p.codportador" :key="p.codportador">
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

      <!-- Arquivos OFX -->
      <q-dialog v-model="dialogImportarOfx" position="bottom">
        <q-uploader
        :url="urlUploadOfx"
        field-name="arquivos[]"
        accept=".ofx"
        label="Importar Arquivos OFX"
        @finish="finalImportacaoOfx"
        @uploaded="ofxImportado"
        @failed="ofxFalha"
        multiple
        flat
        />
      </q-dialog>

      <!-- Anos -->
      <q-tabs v-model="ano" dense >
        <q-tab v-for="i in anos" :name="i" :label="i" :key="i"/>
      </q-tabs>

      <!-- Meses -->
      <q-tabs v-model="mes" dense >
        <q-tab v-for="i in meses" :name="i.mes" :label="i.descricao" :key="i.mes">
          <!-- <q-badge small rounded color="red" floating></q-badge> -->
        </q-tab>
      </q-tabs>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <!-- <q-btn fab icon="add" color="primary" :to="{ path: '/marca/create' }"/> -->
        <q-btn fab icon="cloud_upload" color="primary" @click="dialogImportarOfx = !dialogImportarOfx" />
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
      mes: null,
      ano: null,
      anos: [
      ],
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
      dialogImportarOfx: false,
      falhaImportacaoOfx: false,
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
    dialogImportarOfx() {
      this.falhaImportacaoOfx = false;
    },
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    },

  },

  computed: {
    urlUploadOfx: function () {
      return process.env.API_URL + 'portador/importar-ofx';
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
      this.$router.replace({ path: '/portador/' + this.codportador + '/' + this.ano + '/' + this.mes}).catch(err => {});
    },

    parsePortador: function() {
      const portador = this.portadores.find(item => item.codportador == this.codportador);
      if (portador != undefined) {
        this.portador = portador;
      } else if (this.portadores.length > 0) {
        this.portador = this.portadores[0];
        this.codportador = this.portador.codportador;
      }
      this.atualizarUrl();
    },

    finalImportacaoOfx: function() {
      if (!this.falhaImportacaoOfx) {
        this.dialogImportarOfx = false;
      }
      this.loadPortadores();
    },

    ofxImportado: function(info) {
      const vm = this;
      const resp = JSON.parse(info.xhr.response);
      Object.keys(resp).forEach(arquivo => {
        var mensagem =
          'Importados ' +
          resp[arquivo].registros +
          ' registros no portador "' +
          resp[arquivo].portador +
          '" com ' +
          resp[arquivo].falhas +
          ' falhas!'
          ;
        vm.$q.notify({
          message: mensagem,
          color: 'positive',
          // position: 'top',
        })
      });
    },

    ofxFalha: function(info) {
      this.falhaImportacaoOfx = true;
      const vm = this;
      info.files.forEach((arquivo, i) => {
        var mensagem =
          'Falha ao importar o arquivo "' +
          arquivo.name +
          '"!'
          ;
        vm.$q.notify({
          message: mensagem,
          color: 'negative',
          // position: 'top',
        })
      });
    },

  },

  // na criacao, busca filtro do Vuex
  created () {

    const anoCorrente = new Date().getFullYear();
    const anoInicial = 2015;
    this.anos = Array(anoCorrente - anoInicial + 1)
      .fill(anoInicial)
      .map((ano, index) => ano + index);

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
