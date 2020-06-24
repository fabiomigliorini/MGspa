<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Remessa e Retorno de Boletos
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">

      <!-- retornos -->
      <template>
        <div>
          <q-card>
            <q-toolbar class="bg-primary text-white shadow-2">
              <div class="text-h6 text-uppercase">
                Retornos
              </div>
              <q-space />
              <q-tabs v-model="tabRetorno" inline-label>
                <q-tab name="falha" icon="error"  label="Com Falha" v-if="retornoFalha.length > 0" />
                <q-tab name="pendente" icon="note_add" label="Para Processar" v-if="retornoPendente.length > 0" />
                <q-tab name="processado" icon="history" label="Processados" v-if="retornoProcessado.length > 0" />
              </q-tabs>
            </q-toolbar>

            <q-separator />

            <!-- Falha de Processamento -->
            <q-tab-panels v-model="tabRetorno" animated v-if="retornoFalha.length > 0">
              <q-tab-panel name="falha">
                <q-btn
                  :loading="loadingReprocessarRetorno"
                  flat
                  label="Reprocessar Registros"
                  @click="reprocessarRetorno()"
                  icon="cached"
                />
                <q-table
                  flat
                  dense
                  :data="retornoFalha"
                  :columns="columnsRetornoFalha"
                  :pagination.sync="paginationFalha"
                  @row-click="abreRetorno"
                />
              </q-tab-panel>

              <!--Retornos Pendentes -->
              <q-tab-panel name="pendente" v-if="retornoPendente.length > 0">
                <div class="row q-col-gutter-md" v-if="!loadingRetornoPendente">
                  <div class="col-xs-6 col-sm-4 col-md-3 col-lg-2" v-for="portador in retornoPendente" :key="portador.codportador">
                    <q-card>
                      <q-card-section class="bg-primary text-white">
                        <div class="text-h6">{{portador.portador}}</div>
                      </q-card-section>
                      <q-list dense>
                        <q-item clickable v-for="arquivo in portador.retornos" :key="arquivo">
                          <q-item-section>
                            <q-checkbox v-model="retornoPendenteSelecionado" :val="portador.codportador + '-' + arquivo" :label="arquivo" />
                          </q-item-section>
                        </q-item>
                      </q-list>
                      <q-separator />
                      <q-card-actions align="right">
                        <q-btn flat @click="processarRetorno(portador.codportador)">Processar</q-btn>
                      </q-card-actions>
                    </q-card>
                  </div>
                </div>
              </q-tab-panel>

              <!-- Arquivos Processados -->
              <q-tab-panel name="processado" v-if="retornoProcessado.length > 0">
                <!-- <pre>
{{retornoProcessado}}
                </pre> -->
                <q-table
                  flat
                  dense
                  :data="retornoProcessado"
                  :columns="columnsRetornoProcessado"
                  :pagination.sync="paginationProcessado"
                  @row-click="abreRetorno"
                />
              </q-tab-panel>
            </q-tab-panels>
          </q-card>
        </div>
      </template>

      <br />

      <!-- remessas -->
      <template>
        <div>
          <q-card>
            <q-toolbar class="bg-primary text-white shadow-2">
              <div class="text-h6 text-uppercase">
                Remessas
              </div>
              <q-space />
              <q-tabs v-model="tabRemessa" inline-label>
                <q-tab name="pendente" icon="note_add" label="Pendentes" v-if="remessaPendente.length > 0" />
                <q-tab name="geradas" icon="history" label="Geradas" v-if="remessaGerada.length > 0" />
              </q-tabs>
            </q-toolbar>

            <q-separator />

            <!-- Para Gerar -->
            <q-tab-panels v-model="tabRemessa" animated >
              <q-tab-panel name="pendente" v-if="remessaPendente.length > 0">

                <div class="row q-col-gutter-md">
                  <div class="col-xs-12" v-for="portador in remessaPendente" :key="portador.codportador">
                    <q-card>
                      <q-card-section class="bg-primary text-white">
                        <div class="text-h6">{{portador.portador}}</div>
                      </q-card-section>
                      <q-list>
                        <q-item v-for="arquivo in portador.remessas" :key="arquivo">
                          <q-item-section avatar>
                            <q-btn icon="archive" flat @click="arquivarRemessa(portador.codportador, arquivo)">
                              <q-tooltip>Arquivar</q-tooltip>
                            </q-btn>
                          </q-item-section>
                          <q-item-section>
                            {{arquivo}}
                          </q-item-section>
                        </q-item>
                      </q-list>
                      <q-separator />
                      <q-card-section v-if="portador.titulos.length > 0">
                        <q-table
                          flat
                          dense
                          selection="multiple"
                          :columns="columnsRemessaPendente"
                          :data="portador.titulos"
                          :selected.sync="titulosSelecionados[portador.codportador]"
                          row-key="codtitulo"
                        />
                      </q-card-section>
                      <q-card-actions align="right" v-if="portador.titulos.length > 0">
                        <q-btn flat @click="gerarRemessa(portador.codportador)">Gerar Remessa</q-btn>
                      </q-card-actions>
                    </q-card>
                  </div>
                </div>

                <pre>

{{titulosSelecionados}}

                  SELECT tblPortador.portador, remessa, Count(codtitulo), tblTitulo.remessa, tblPortador.codportador
                  FROM tblPortador INNER JOIN tblTitulo ON tblPortador.codportador = tblTitulo.codportador
                  WHERE (((tblTitulo.saldo)>0))
                  GROUP BY tblPortador.portador, remessa, tblTitulo.remessa, tblPortador.codportador, tblTitulo.boleto
                  HAVING (((tblTitulo.remessa) Is Not Null)) OR (((tblTitulo.boleto)=true))
                  ORDER BY Max(tblTitulo.emissao) DESC , tblPortador.codportador;

                </pre>
              </q-tab-panel>

              <!--Geradas -->
              <q-tab-panel name="geradas" v-if="remessaGerada.length > 0">
                Geradas
              </q-tab-panel>
            </q-tab-panels>
          </q-card>
        </div>
      </template>


    </div>
  </mg-layout>
</template>

<script>
import { debounce } from 'quasar'
import { throttle } from 'quasar'
import { Notify } from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
export default {
  components: {
    debounce,
    MgLayout,
  },
  data () {
    return {
      tabRetorno: '',
      tabRemessa: 'pendente',
      loadingRetornoFalha: true,
      loadingRetornoPendente: true,
      loadingRetornoProcessado: true,
      loadingReprocessarRetorno: false,
      loadingRemessaPendente: true,
      processando: false,
      retornoFalha: [],
      retornoPendente: [],
      retornoPendenteSelecionado: [],
      retornoProcessado: [],
      remessaPendente: [],
      remessaGerada: [1, 2, 3],

      titulosSelecionados: [],

      paginationFalha: {
        rowsPerPage: 18 // current rows per page being displayed
      },
      paginationProcessado: {
        rowsPerPage: 18 // current rows per page being displayed
      },
      columnsRetornoFalha: [
        { name: 'codboletoretorno', align: 'right', label: '#', field: 'codboletoretorno', sortable: true, headerClasses: 'bg-negative text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'linha', align: 'right', label: 'Linha', field: 'linha', sortable: true, headerClasses: 'bg-negative text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'nossonumero', align: 'right', label: 'NossoNumero', field: 'nossonumero', sortable: true, headerClasses: 'bg-negative text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'numero', align: 'right', label: 'Número', field: 'numero', sortable: true, headerClasses: 'bg-negative text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'pagamento', align: 'right', label: 'Pagamento', field: 'pagamento'},
        { name: 'jurosatraso', align: 'right', label: 'Atraso', field: 'jurosatraso'},
        { name: 'jurosmora', align: 'right', label: 'Mora', field: 'jurosmora'},
        { name: 'abatimento', align: 'right', label: 'Abatimento', field: 'abatimento'},
        { name: 'desconto', align: 'right', label: 'Desconto', field: 'desconto'},
        { name: 'protesto', align: 'right', label: 'Protesto', field: 'protesto'},
        { name: 'valor', align: 'right', label: 'Valor', field: 'valor'},

        { name: 'ocorrencia', align: 'left', label: 'Ocorrencia', field: 'ocorrencia', sortable: true},
        { name: 'motivo', align: 'left', label: 'Motivo', field: 'motivo', sortable: true},
        { name: 'despesas', align: 'right', label: 'Despesas', field: 'despesas'},
        { name: 'outrasdespesas', align: 'right', label: 'Outras', field: 'outrasdespesas'},

        { name: 'codbancocobrador', align: 'right', label: 'Banco', field: 'codbancocobrador'},
        { name: 'agenciacobradora', align: 'right', label: 'Agencia', field: 'agenciacobradora'},
      ],
      columnsRetornoProcessado: [
        { name: 'dataretorno', align: 'center', label: 'Data', field: 'dataretorno', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis', format: (val, row) => this.formataData(val, row)},
        { name: 'arquivo', align: 'left', label: 'Arquivo', field: 'arquivo', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'portador', align: 'left', label: 'Portador', field: 'portador', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'registros', align: 'right', label: 'Registros', field: 'registros', sortable: true },
        { name: 'sucesso', align: 'right', label: 'Sucesso', field: 'sucesso', sortable: true },
        { name: 'falha', align: 'right', label: 'Falha', field: 'falha', sortable: true },
        { name: 'pagamento', align: 'right', label: 'Pagamento', field: 'pagamento', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'valor', align: 'right', label: 'Valor', field: 'valor', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
      ],
      columnsRemessaPendente: [
        { name: 'fantasia', align: 'left', label: 'Nome Fantasia', field: 'fantasia', sortable: true },
        { name: 'numero', align: 'left', label: 'Número', field: 'numero', sortable: true },
        { name: 'nossonumero', align: 'right', label: 'Nosso Número', field: 'nossonumero', sortable: true },
        { name: 'emissao', align: 'right', label: 'Emissão', field: 'emissao', sortable: true, format: (val, row) => this.formataData(val, row) },
        { name: 'vencimento', align: 'right', label: 'Vencimento', field: 'vencimento', sortable: true, format: (val, row) => this.formataData(val, row) },
        { name: 'debito', align: 'right', label: 'Débito', field: 'debito', sortable: true, format: (val, row) => this.formataValor(val, row), sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'saldo', align: 'right', label: 'Saldo', field: 'saldo', sortable: true, format: (val, row) => this.formataValor(val, row), sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
      ],
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

    formataData: function(val, row) {
      return this.moment(val).format('DD/MM/YYYY');
    },

    formataValor: function(val, row) {
      return this.numeral(parseFloat(val)).format('0,0.00');
    },

    loadRetornoFalha: debounce(function () {
      let vm = this;
      // this.loadingRetornoFalha = true;
      vm.$axios.get('boleto/retorno-falha').then(response => {
        vm.retornoFalha = response.data;
        this.loadingRetornoFalha = false;
        vm.decideTabRetorno();
      })
    }, 500),

    loadRetornoPendente: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // this.loadingRetornoPendente = true;
      // faz chamada api
      vm.retornoPendenteSelecionado = [];
      vm.$axios.get('boleto/retorno-pendente').then(response => {
        vm.retornoPendente = response.data;
        vm.retornoPendente.forEach((portador) => {
          portador.retornos.forEach((arquivo) => {
            vm.retornoPendenteSelecionado = vm.retornoPendenteSelecionado.concat([portador.codportador + '-' + arquivo]);
          });
        });
        this.loadingRetornoPendente = false;
        vm.decideTabRetorno();
      })
    }, 500),

    loadRetornoProcessado: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // this.loadingRetornoProcessado = true;
      // faz chamada api
      vm.retornoProcessado = [];
      vm.$axios.get('boleto/retorno-processado').then(response => {
        vm.retornoProcessado = response.data;
        this.loadingRetornoProcessado = false;
        vm.decideTabRetorno();
      })
    }, 500),

    reprocessarRetorno: throttle(function(codportador) {
      let vm = this;
      this.loadingReprocessarRetorno = true;
      vm.$axios.post('boleto/reprocessar-retorno', {
    }).then(response => {
      let color = 'positive';
      let mensagem = response.data.sucesso + ' registros reprocessados ';
      if (response.data.falha > 0) {
        color = 'negative';
        mensagem = response.data.falha + ' registros com falha e ' + mensagem;
      }
      this.$q.notify({
        color: color,
        message: mensagem
      });
      this.loadingReprocessarRetorno = false;
      setTimeout(function(){ vm.loadRetornoFalha(); }, 500); });
    }, 500),

    processarRetorno: throttle(function(codportador) {
      this.retornoPendenteSelecionado.forEach((itemSelecionado) => {
        if (itemSelecionado.startsWith(codportador + '-')) {
          let vm = this;
          let arquivo = itemSelecionado.replace(codportador + '-', '');
          vm.$axios.post('boleto/processar-retorno', {
            codportador: codportador,
            'arquivo': arquivo
          }).then(response => {
            vm.retornoPendente = vm.retornoPendente.filter(function(itemFiltrar){
              return itemFiltrar != itemSelecionado;
            });
            let pendente = vm.retornoPendente.find( portador => portador.codportador === codportador );
            pendente.retornos = pendente.retornos.filter(function(itemFiltrar) {
              return itemFiltrar != arquivo;
            });
            vm.retornoPendente = vm.retornoPendente.filter(function(itemFiltrar){
              return itemFiltrar.retornos.length > 0;
            });
            let color = 'positive';
            let mensagem = response.data.sucesso + ' registros processados no arquivo ' + response.data.arquivo;
            if (response.data.falha > 0) {
              color = 'negative';
              mensagem = response.data.falha + ' registros com falha e ' + mensagem;
            }
            this.$q.notify({
              color: color,
              message: mensagem
            });
            setTimeout(function(){
              vm.loadRetornoProcessado();
              vm.loadRetornoFalha();
            }, 1500);
          });
        }
      });
    }, 500),

    decideTabRetorno: function () {
      if (this.retornoFalha.length > 0) {
        this.tabRetorno = 'falha';
        return;
      }
      if (this.retornoPendente.length > 0) {
        this.tabRetorno = 'pendente';
        return;
      }
      this.tabRetorno = 'processado';
    },

    abreRetorno: debounce(function(evt, row) {
      // this.$router.push('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      let route = this.$router.resolve('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      window.open(route.href, '_blank');
    }, 500),

    loadRemessaPendente: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // this.loadingRemessaPendente = true;
      // faz chamada api
      vm.$axios.get('boleto/remessa-pendente').then(response => {
        vm.remessaPendente = response.data;
        vm.titulosSelecionados = [];
        vm.remessaPendente.forEach((portador) => {
          vm.titulosSelecionados[portador.codportador] = portador.titulos;
          console.log(portador.codportador);
        });
        this.loadingRemessaPendente = false;
        // vm.decideTabRemessa();
      })
    }, 500),

  },
  created () {
    this.loadRetornoFalha();
    this.loadRetornoPendente();
    this.loadRetornoProcessado();
    this.loadRemessaPendente();
  }
}
</script>

<style>
</style>
