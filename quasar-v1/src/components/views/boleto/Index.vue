<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Remessa e Retorno de Boletos
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">

      <!-- Retorno Com Falhas -->
      <div class="row q-col-gutter-md" v-if="!loadingFalha && retornoFalha.length > 0">
        <div class="col-xs-12">
          <q-card>
            <q-card-section class="bg-negative text-white">
              <div class="text-h6">
                Retornos Com Falha de Processamento
                <q-btn
                  :loading="loadingReprocessar"
                  flat
                  round
                  @click="reprocessar()"
                  icon="cached"
                />
              </div>
            </q-card-section>
            <div class="q-pa-md">
              <q-table
                flat
                dense
                :data="retornoFalha"
                :columns="columnsFalha"
                :pagination.sync="paginationFalha"
                @row-click="abrirRetorno"
              />
            </div>
          </q-card>
          <div class="col-xs-12">
            <br />
          </div>
        </div>
      </div>


      <!--Retornos Pendentes -->
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
      <br />

      <!-- Arquivos Processados -->
      <div class="row q-col-gutter-md" v-if="!loadingProcessado">
        <div class="col-xs-12">
          <q-card>
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Retornos Processados</div>
            </q-card-section>
            <div class="q-pa-md">
              <q-table
                flat
                dense
                :data="retornoProcessado"
                :columns="columnsProcessado"
                :pagination.sync="paginationProcessado"
                @row-click="abrirRetorno"
              />
            </div>
          </q-card>
        </div>
      </div>

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
      loadingFalha: true,
      loadingPendente: true,
      loadingProcessado: true,
      loadingReprocessar: false,
      processando: false,
      retornoPendente: [],
      retornoPendenteSelecionado: [],
      retornoProcessado: [],
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
      this.loadingFalha = true;
      vm.$axios.get('boleto/retorno-falha').then(response => {
        vm.retornoFalha = response.data;
        this.loadingFalha = false;
      })
    }, 500),

    loadRetornoPendente: debounce(function () {
      // inicializa variaveis
      let vm = this;
      this.loadingPendente = true;
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
      })
    }, 500),

    loadRetornoProcessado: debounce(function () {
      // inicializa variaveis
      let vm = this;
      this.loadingProcessado = true;
      // faz chamada api
      vm.retornoProcessado = [];
      vm.$axios.get('boleto/retorno-processado').then(response => {
        vm.retornoProcessado = response.data;
        this.loadingProcessado = false;
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
            setTimeout(function(){ vm.loadRetornoProcessado(); }, 1500);
          });
        }
      });
    }, 500),

    abrirRetorno: debounce(function(evt, row) {
      // this.$router.push('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      let route = this.$router.resolve('/boleto/retorno/' + row.codportador + '/' + row.arquivo + '/' + row.dataretorno);
      window.open(route.href, '_blank');
    }, 500),
  },
  created () {
    this.loadRetornoFalha()
    this.loadRetornoPendente()
    this.loadRetornoProcessado()
  }
}
</script>

<style>
</style>
