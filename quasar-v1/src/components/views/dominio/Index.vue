<template>
  <mg-layout drawer  back-path="/">

    <template slot="title" back-path="/">
      Exportacao Domínio - Fechamento Mensal
    </template>

    <div slot="content">
      <div class="row q-ma-md">

        <div class="q-pa-md col-6">
          <q-card class="col-6" flat bordered >
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Mês de Exportação: {{moment(mes).format('MMMM YYYY')}}</div>
            </q-card-section>
            <center>
              <q-date
              mask="YYYY-MM-DD"
              v-model="mes"
              minimal
              />
            </center>
          </q-card>
        </div>

        <div class="q-pa-md col-6" v-for="empresa in empresas">
          <q-card class="col-6" flat bordered >
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">#{{empresa.codempresa}} - {{ empresa.empresa }}</div>
              <!-- <div class="ellipsis"> -->
                <!-- {{ filial.stonecode }} -->
              <!-- </div> -->
            </q-card-section>
            <q-list>
              <q-item v-for="filial in empresa.filiais" :key="filial.codfilial">
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white">
                    {{filial.filial.charAt(0)}}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="ellipsis">
                    {{filial.filial}}
                  </q-item-label>
                  <q-item-label caption>
                    #{{filial.codfilial}} | {{ filial.empresadominio }}
                  </q-item-label>
                </q-item-section>
                <q-item-section top side>
                  <div class="text-grey-8 q-gutter-xs">
                    <q-btn icon="fa fa-shipping-fast" flat dense round @click="gerarArquivoNFeEntrada(filial.codfilial)">
                      <q-tooltip>Gerar arquivo compactado com os XMLs das NFe-s de Entrada emitidas por nós, como transferências e devoluções!</q-tooltip>
                    </q-btn>
                    <q-btn icon="fa fa-receipt" flat dense round @click="gerarArquivoNFeSaida(filial.codfilial, 65)">
                      <q-tooltip>Gerar arquivo compactado com os XMLs das NFCe-s de Saidas!</q-tooltip>
                    </q-btn>
                    <q-btn icon="fa fa-file-invoice" flat dense round @click="gerarArquivoNFeSaida(filial.codfilial, 55)">
                      <q-tooltip>Gerar arquivo compactado com os XMLs das NFe-s de Saidas!</q-tooltip>
                    </q-btn>
                    <q-btn icon="fa fa-people-carry" flat dense round @click="gerarArquivoPessoa(filial.codfilial)">
                      <q-tooltip>Gerar arquivo com cadastro de Fornecedores!</q-tooltip>
                    </q-btn>
                    <q-btn icon="fa fa-cube" flat dense round @click="gerarArquivoProduto(filial.codfilial)">
                      <q-tooltip>Gerar arquivo com cadastro de Produtos!</q-tooltip>
                    </q-btn>
                    <q-btn icon="far fa-file-alt" flat dense round @click="gerarArquivoEntrada(filial.codfilial)">
                      <q-tooltip>Gerar arquivo com Notas Fiscais de Entrada!</q-tooltip>
                    </q-btn>
                    <q-btn icon="fa fa-cubes" flat dense round @click="gerarArquivoEstoque(filial.codfilial)">
                      <q-tooltip>Gerar arquivo com saldos de Estoque (Anual)!</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <q-dialog v-model="dialogPos" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Novo POS</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input outlined v-model="pos.apelido" autofocus label="Apelido" />
          <q-card-section class="q-pt-none">
          </q-card-section>
            <q-input outlined v-model="pos.serialnumber"  label="Serial" />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" color="primary" v-close-popup @click="salvarPos()"/>
          </q-card-actions>
        </q-card>
      </q-dialog>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import { debounce } from 'quasar'
export default {
  name: 'stone-connect-index',
  components: {
    MgLayout,
  },
  data () {
    return {
      empresas: [],
      mes: null,
      dialogPos: false,
      pos: {
        codstonefilial: null,
        serialnumber: null,
        apelido: null
      },
    }
  },
  watch: {
  },
  methods: {

    loadEmpresas: debounce(function () {
      var vm = this;
      vm.$axios.get('dominio/empresas').then(response => {
        vm.empresas = response.data
      })
    }, 500),

    gerarArquivoNFeEntrada: debounce(function (codfilial) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        mes: this.mes
      };
      vm.$axios.post('dominio/nfe-entrada', params).then(response => {
        var type = 'warning';
        if (response.data.registrosNaoLocalizados == 0) {
          type = 'positive';
        }
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registrosCompactados  + ' registros! ' + response.data.registrosNaoLocalizados  + ' não localizados!',
          type: type,
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    gerarArquivoNFeSaida: debounce(function (codfilial, modelo) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        modelo: modelo,
        mes: this.mes
      };
      vm.$axios.post('dominio/nfe-saida', params).then(response => {
        var type = 'warning';
        if (response.data.registrosNaoLocalizados == 0) {
          type = 'positive';
        }
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registrosCompactados  + ' registros! ' + response.data.registrosNaoLocalizados  + ' não localizados!',
          type: type,
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    gerarArquivoPessoa: debounce(function (codfilial) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        mes: this.mes
      };
      vm.$axios.post('dominio/pessoa', params).then(response => {
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros  + ' registros!',
          type: 'positive',
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    gerarArquivoProduto: debounce(function (codfilial) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        mes: this.mes
      };
      vm.$axios.post('dominio/produto', params).then(response => {
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros  + ' registros!',
          type: 'positive',
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    gerarArquivoEntrada: debounce(function (codfilial) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        mes: this.mes
      };
      vm.$axios.post('dominio/entrada', params).then(response => {
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros  + ' registros!',
          type: 'positive',
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

    gerarArquivoEstoque: debounce(function (codfilial) {
      var vm = this;
      var params = {
        codfilial: codfilial,
        mes: this.mes
      };
      vm.$axios.post('dominio/estoque', params).then(response => {
        vm.$q.notify({
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros  + ' registros!',
          type: 'positive',
        });
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Falha ao gerar arquivo!',
          type: 'negative',
        });
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        });
      });
    }, 500),

  },
  created () {
    this.loadEmpresas();
    this.mes = this.moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD');
  }
}
</script>

<style scoped>
</style>
