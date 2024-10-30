<template>
  <mg-layout drawer back-path="/">

    <template slot="title" back-path="/">
      Exportacao Domínio - Fechamento Mensal
    </template>

    <div slot="content">
      <div class="row q-ma-md">
        <div class="col-3">
          <q-card class="col-6" flat bordered>
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Exportando {{ moment(mes).format('MMM/YYYY') }}</div>
            </q-card-section>
            <center>
              <q-date flat mask="YYYY-MM-DD" v-model="mes" minimal />
            </center>
          </q-card>
        </div>
        <div class="col-9">
          <div class="row">
            <div class="q-px-md q-mb-md col-12" v-for="empresa in empresas">
              <q-card class="col-6" flat bordered>
                <q-card-section class="bg-primary text-white">
                  <div class="text-h6">#{{ empresa.codempresa }} - {{ empresa.empresa }}</div>
                  <!-- <div class="ellipsis"> -->
                  <!-- {{ filial.stonecode }} -->
                  <!-- </div> -->
                </q-card-section>
                <q-tabs v-model="tab" dense class="text-grey" active-color="primary" indicator-color="primary"
                  align="justify" narrow-indicator>
                  <q-tab name="export" label="Exportação" />
                  <q-tab name="acum" label="Acumuladores" />
                </q-tabs>
                <q-separator />
                <q-tab-panels v-model="tab" animated>
                  <q-tab-panel name="export">
                    <q-list>
                      <q-item v-for="filial in empresa.filiais" :key="filial.codfilial">
                        <q-item-section avatar>
                          <q-avatar color="primary" text-color="white">
                            {{ filial.filial.charAt(0) }}
                          </q-avatar>
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="ellipsis">
                            {{ filial.filial }}
                          </q-item-label>
                          <q-item-label caption>
                            #{{ filial.codfilial }} | {{ filial.empresadominio }}
                          </q-item-label>
                        </q-item-section>
                        <q-item-section top side>
                          <div class="text-grey-8 q-gutter-xs">
                            <q-btn icon="fa fa-shipping-fast" flat dense round
                              @click="gerarArquivoNFeEntrada(filial.codfilial)">
                              <q-tooltip>Gerar arquivo compactado com os XMLs das NFe-s de Entrada emitidas por nós,
                                como
                                transferências e devoluções!</q-tooltip>
                            </q-btn>
                            <q-btn icon="fa fa-receipt" flat dense round
                              @click="gerarArquivoNFeSaida(filial.codfilial, 65)">
                              <q-tooltip>Gerar arquivo compactado com os XMLs das NFCe-s de Saidas!</q-tooltip>
                            </q-btn>
                            <q-btn icon="fa fa-file-invoice" flat dense round
                              @click="gerarArquivoNFeSaida(filial.codfilial, 55)">
                              <q-tooltip>Gerar arquivo compactado com os XMLs das NFe-s de Saidas!</q-tooltip>
                            </q-btn>
                            <q-btn icon="fa fa-people-carry" flat dense round
                              @click="gerarArquivoPessoa(filial.codfilial)">
                              <q-tooltip>Gerar arquivo com cadastro de Fornecedores!</q-tooltip>
                            </q-btn>
                            <q-btn icon="fa fa-cube" flat dense round @click="gerarArquivoProduto(filial.codfilial)">
                              <q-tooltip>Gerar arquivo com cadastro de Produtos!</q-tooltip>
                            </q-btn>
                            <q-btn icon="far fa-file-alt" flat dense round
                              @click="gerarArquivoEntrada(filial.codfilial)">
                              <q-tooltip>Gerar arquivo com Notas Fiscais de Entrada!</q-tooltip>
                            </q-btn>
                            <q-btn icon="fa fa-cubes" flat dense round @click="gerarArquivoEstoque(filial.codfilial)">
                              <q-tooltip>Gerar arquivo com saldos de Estoque (Anual)!</q-tooltip>
                            </q-btn>
                          </div>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-tab-panel>

                  <q-tab-panel name="acum" class="q-pa-none">
                    <q-splitter v-model="splitterModel" style="height: 60vh">

                      <template v-slot:before>
                        <q-tabs v-model="tabFilial" vertical active-color="primary" class="text-grey">
                          <q-tab :name="filial.codfilial" icon="store" :label="filial.filial"
                            v-for="filial in empresa.filiais" :key="filial.codfilial" />
                        </q-tabs>
                      </template>

                      <template v-slot:after>
                        <q-tab-panels v-model="tabFilial" animated swipeable vertical transition-prev="jump-up"
                          transition-next="jump-up">
                          <q-tab-panel :name="filial.codfilial" v-for="filial in empresa.filiais"
                            :key="filial.codfilial" class="q-px-none">
                            <q-btn  flat icon="add" color="primary" class="col-6 q-ml-md" label="Nova Combinação de CFOP e CST"
                              @click="novoDominioAcumulador(filial.codfilial)" />
                            <q-list  separator>
                              <q-item v-for="ac in sortArrayAcumuladores(filial.acumuladores)">
                                <q-item-section side>
                                  <q-chip square color="primary" text-color="white">
                                    {{ ac.codcfop }}
                                  </q-chip>
                                </q-item-section>
                                <q-item-section>
                                  <q-item-label caption>
                                    {{ ac.cfop }}
                                  </q-item-label>
                                  <q-item-label caption v-if="ac.historico">
                                    {{ ac.historico }}
                                  </q-item-label>
                                </q-item-section>
                                <q-item-section style="max-width: 50px" class="q-mr-sm">
                                  <q-chip square color="primary" text-color="white">
                                    {{ String(ac.icmscst).padStart(2, '0') }}
                                  </q-chip>
                                </q-item-section>
                                <q-item-section>
                                  <q-item-label>
                                      Acumuladores
                                      <b>{{ ac.acumuladoravista }}</b>
                                      /
                                      <b>{{ ac.acumuladorprazo }}</b>
                                  </q-item-label >
                                  <q-item-label caption v-if="ac.movimentacaofisica">
                                    Movimentação Física
                                  </q-item-label>
                                  <q-item-label caption v-if="ac.movimentacaocontabil">
                                    Movimentação Contábil
                                </q-item-label >

                                </q-item-section>
                                <q-item-section side>
                                  <q-btn-group dense flat>

                                  <q-btn dense flat icon="edit" color="primary" class="col-6"
                                      @click="editarDominioAcumulador(ac)" />
                                    <q-btn dense flat round icon="delete" color="negative" class="col-6"
                                      @click="excluirDominioAcumulador(ac)" />
                                      </q-btn-group>
                                </q-item-section>
                              </q-item>
                            </q-list>

                          </q-tab-panel>

                        </q-tab-panels>
                      </template>

                    </q-splitter>
                  </q-tab-panel>
                </q-tab-panels>
              </q-card>
            </div>
          </div>
        </div>

      </div>

      <q-dialog v-model="dialogAcumulador" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Acumulador</div>
          </q-card-section>

          <q-form @submit="salvarAcumulador">
            <q-card-section class="q-pt-none">
              <div class="row q-col-gutter-md">
                <q-input class="col-6" outlined v-model="acumulador.codcfop" autofocus label="CFOP" mask="####"
                  :rules="[val => !!val || 'Obrigatório', val => String(val).length == 4 || 'CFOP deve conter 4 dígitos!']"
                  :disable="(acumulador.coddominioacumulador > 0)" />
                <q-input class="col-6" outlined v-model="acumulador.icmscst" label="CST" mask="##"
                  :rules="[val => val >= 0 || 'Obrigatório', val => String(val).length <= 2 || 'CST deve conter no máximo 2 dígitos!']"
                  :disable="(acumulador.coddominioacumulador > 0)" />
                <q-input class="col-6" outlined v-model="acumulador.acumuladoravista" label="Acumulador à Vista"
                  :rules="[val => val > 0 || 'Obrigatório']" mask="#######"
                  :autofocus="(acumulador.coddominioacumulador > 0)" />
                <q-input class="col-6" outlined v-model="acumulador.acumuladorprazo" label="Acumulador à Prazo"
                  :rules="[val => val > 0 || 'Obrigatório']" mask="#######" />
                <q-input class="col-12" outlined v-model="acumulador.historico" label="Histórico" counter maxlength="100"/>
                <q-checkbox class="col-6" v-model="acumulador.movimentacaofisica" label="Movimentação Física"
                  color="primary" />
                <q-checkbox class="col-6" v-model="acumulador.movimentacaocontabil" label="Movimentação Contábil"
                  color="primary" />
              </div>
            </q-card-section>

            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="negative" v-close-popup />
              <q-btn flat label="Salvar" color="primary" type="submit" />
            </q-card-actions>
          </q-form>
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
  data() {
    return {
      empresas: [],
      mes: null,
      dialogAcumulador: false,
      tab: 'export',
      splitterModel: 15,
      tabFilial: 101,
      acumulador: {
        coddominioacumulador: null,
        codfilial: null,
        codcfop: null,
        icmscst: null,
        acumuladoravista: null,
        acumuladorprazo: null,
        historico: null,
        movimentacaofisica: true,
        movimentacaocontabil: true,
      },
    }
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registrosCompactados + ' registros! ' + response.data.registrosNaoLocalizados + ' não localizados!',
          type: type,
        });
      }).catch(function (error) {
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registrosCompactados + ' registros! ' + response.data.registrosNaoLocalizados + ' não localizados!',
          type: type,
        });
      }).catch(function (error) {
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros + ' registros!',
          type: 'positive',
        });
      }).catch(function (error) {
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros + ' registros!',
          type: 'positive',
        });
      }).catch(function (error) {
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros + ' registros!',
          type: 'positive',
        });
      }).catch(function (error) {
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
          message: 'Arquivo ' + response.data.arquivo + ' criado com ' + response.data.registros + ' registros!',
          type: 'positive',
        });
      }).catch(function (error) {
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

    novoDominioAcumulador: function (codfilial) {
      this.acumulador = {
        coddominioacumulador: null,
        codfilial: codfilial,
        codcfop: null,
        icmscst: null,
        acumuladoravista: null,
        acumuladorprazo: null,
        historico: null,
        movimentacaofisica: true,
        movimentacaocontabil: true,
      };
      this.dialogAcumulador = true;
    },

    editarDominioAcumulador: function (row) {
      this.acumulador = { ...row };
      this.dialogAcumulador = true;
    },

    parseAcumulador: function (acum) {
      var filial = null;
      this.empresas.every((e) => {
        filial = e.filiais.find((f) => {
          return f.codfilial == acum.codfilial
        });
        if (filial) {
          return false;
        }
        return true;
      });
      const i = filial.acumuladores.findIndex((a) => {
        if (a.codcfop != acum.codcfop) {
          return false;
        }
        if (a.icmscst != acum.icmscst) {
          return false;
        }
        return true;
      });
      if (i != -1) {
        filial.acumuladores[i] = acum;
        console.log('alterou');
      } else {
        filial.acumuladores.push(acum);
        console.log('adicionou');
      }
    },

    removerAcumulador: function (acum) {
      var filial = null;
      this.empresas.every((e) => {
        filial = e.filiais.find((f) => {
          return f.codfilial == acum.codfilial
        });
        if (filial) {
          return false;
        }
        return true;
      });
      const i = filial.acumuladores.findIndex((a) => {
        if (a.codcfop != acum.codcfop) {
          return false;
        }
        if (a.icmscst != acum.icmscst) {
          return false;
        }
        return true;
      });
      if (i != -1) {
        filial.acumuladores.splice(i, 1);
      }
    },

    salvarAcumulador: function (evt) {
      if (evt) {
        evt.preventDefault();
      }
      this.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza?',
        cancel: true,
        persistent: false
      }).onOk(() => {
        var vm = this;
        vm.$axios.post('dominio/acumulador', this.acumulador).then(response => {
          this.parseAcumulador(response.data.data);
          vm.$q.notify({
            message: 'Acumulador Salvo!',
            type: 'positive',
          });
          this.dialogAcumulador = false;

        }).catch(function (error) {
          console.log(error);
          console.log(error.message);
          vm.$q.notify({
            message: 'Falha ao salvar acumulador!',
            type: 'negative',
          });
          vm.$q.notify({
            message: error.response.data.message,
            type: 'negative',
          });
        });
      })
    },

    excluirDominioAcumulador: function (acumulador) {
      this.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza?',
        cancel: true,
        persistent: false
      }).onOk(() => {
        var vm = this;
        vm.$axios.delete('dominio/acumulador/' + acumulador.coddominioacumulador).then(response => {
          vm.$q.notify({
            message: 'Acumulador Excluído!',
            type: 'positive',
          });
          this.removerAcumulador(acumulador);

        }).catch(function (error) {
          vm.$q.notify({
            message: 'Falha ao excluir acumulador!',
            type: 'negative',
          });
          vm.$q.notify({
            message: error.response.data.message,
            type: 'negative',
          });
        });
      })
    },

    sortArrayAcumuladores(acs) {
      const data = [...acs]
      return data.sort((x, y) => {
        if (x['codcfop'] > y['codcfop']) {
          return 1;
        }
        if (x['codcfop'] < y['codcfop']) {
          return -1;
        }
        if (parseFloat(x['icmscst']) > parseFloat(y['icmscst'])) {
          return 1;
        }
        if (parseFloat(x['icmscst']) < parseFloat(y['icmscst'])) {
          return -1;
        }
        return 0;
      })
    },

  },

  created() {
    this.loadEmpresas();
    this.mes = this.moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD');
  }
}
</script>
