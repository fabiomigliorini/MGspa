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
                  :loading="loadingReprocessar"
                  flat
                  label="Reprocessar Registros"
                  @click="reprocessar()"
                  icon="cached"
                />
                <q-table
                  flat
                  dense
                  :data="retornoFalha"
                  :columns="columnsFalha"
                  :pagination.sync="paginationFalha"
                  @row-click="abrirRetorno"
                />
              </q-tab-panel>

              <!--Retornos Pendentes -->
              <q-tab-panel name="pendente" v-if="retornoPendente.length > 0">
                <div class="row q-col-gutter-md" v-if="!loadingPendente">
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
                        <q-btn flat @click="processar(portador.codportador)">Processar</q-btn>
                      </q-card-actions>
                    </q-card>
                  </div>
                </div>
              </q-tab-panel>

              <!-- Arquivos Processados -->
              <q-tab-panel name="processado" v-if="retornoProcessado.length > 0">
                <q-table
                  flat
                  dense
                  :data="retornoProcessado"
                  :columns="columnsProcessado"
                  :pagination.sync="paginationProcessado"
                  @row-click="abrirRetorno"
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
                <q-tab name="gerar" icon="note_add" label="Para Gerar" v-if="remessaGerar.length > 0" />
                <q-tab name="geradas" icon="history" label="Geradas" v-if="remessaGerada.length > 0" />
              </q-tabs>
            </q-toolbar>

            <q-separator />

            <!-- Para Gerar -->
            <q-tab-panels v-model="tabRemessa" animated v-if="remessaGerar.length > 0">
              <q-tab-panel name="gerar">
                <pre>

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
      tabRemessa: 'gerar',
      loadingFalha: true,
      loadingPendente: true,
      loadingProcessado: true,
      loadingReprocessar: false,
      processando: false,
      retornoFalha: [],
      retornoPendente: [],
      retornoPendenteSelecionado: [],
      retornoProcessado: [],
      remessaGerar: [1, 2, 3],
      remessaGerada: [1, 2, 3],
      paginationFalha: {
        rowsPerPage: 18 // current rows per page being displayed
      },
      paginationProcessado: {
        rowsPerPage: 18 // current rows per page being displayed
      },
      columnsFalha: [
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
      columnsProcessado: [
        { name: 'dataretorno', align: 'center', label: 'Data', field: 'dataretorno', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'arquivo', align: 'left', label: 'Arquivo', field: 'arquivo', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'portador', align: 'left', label: 'Portador', field: 'portador', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'registros', align: 'right', label: 'Registros', field: 'registros', sortable: true },
        { name: 'sucesso', align: 'right', label: 'Sucesso', field: 'sucesso', sortable: true },
        { name: 'falha', align: 'right', label: 'Falha', field: 'falha', sortable: true },
        { name: 'pagamento', align: 'right', label: 'Pagamento', field: 'pagamento', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'valor', align: 'right', label: 'Valor', field: 'valor', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
      ],
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

    loadRetornoFalha: debounce(function () {
      let vm = this;
      // this.loadingFalha = true;
      vm.$axios.get('boleto/retorno-falha').then(response => {
        vm.retornoFalha = response.data;
        this.loadingFalha = false;
        vm.decideTabRetorno();
      })
    }, 500),

    loadRetornoPendente: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // this.loadingPendente = true;
      // faz chamada api
      vm.retornoPendenteSelecionado = [];
      vm.$axios.get('boleto/retorno-pendente').then(response => {
        vm.retornoPendente = response.data;
        vm.retornoPendente.forEach((portador) => {
          portador.retornos.forEach((arquivo) => {
            vm.retornoPendenteSelecionado = vm.retornoPendenteSelecionado.concat([portador.codportador + '-' + arquivo]);
          });
        });
        this.loadingPendente = false;
        vm.decideTabRetorno();
      })
    }, 500),

    loadRetornoProcessado: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // this.loadingProcessado = true;
      // faz chamada api
      vm.retornoProcessado = [];
      vm.$axios.get('boleto/retorno-processado').then(response => {
        vm.retornoProcessado = response.data;
        this.loadingProcessado = false;
        vm.decideTabRetorno();
      })
    }, 500),

    reprocessar: throttle(function(codportador) {
      let vm = this;
      this.loadingReprocessar = true;
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
      this.loadingReprocessar = false;
      setTimeout(function(){ vm.loadRetornoFalha(); }, 500); });
    }, 500),

    processar: throttle(function(codportador) {
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

    abrirRetorno: debounce(function(evt, row) {
      // this.$router.push('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      let route = this.$router.resolve('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      window.open(route.href, '_blank');
    }, 500),
  },
  created () {
    this.loadRetornoFalha();
    this.loadRetornoPendente();
    this.loadRetornoProcessado();
  }
}
</script>

<style>
</style>
