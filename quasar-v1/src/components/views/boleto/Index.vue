<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Remessa e Retorno de Boletos
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">

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
                :columns="columns"
                @row-click="abrirRetorno"
                :pagination.sync="pagination"
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
      loadingPendente: true,
      loadingProcessado: true,
      processando: false,
      retornoPendente: [],
      retornoPendenteSelecionado: [],
      retornoProcessado: [],
      pagination: {
        rowsPerPage: 18 // current rows per page being displayed
      },
      columns: [
        { name: 'dataretorno', align: 'center', label: 'Data', field: 'dataretorno', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'arquivo', align: 'left', label: 'Arquivo', field: 'arquivo', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'portador', align: 'left', label: 'Portador', field: 'portador', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis'  },
        { name: 'registros', align: 'right', label: 'Registros', field: 'registros', sortable: true },
        { name: 'sucesso', align: 'right', label: 'Sucesso', field: 'sucesso', sortable: true },
        { name: 'falha', align: 'right', label: 'Falha', field: 'falha', sortable: true },
        { name: 'pagamento', align: 'right', label: 'Pagamento', field: 'pagamento', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'valor', align: 'right', label: 'Valor', field: 'valor', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        // {
        //   name: 'name',
        //   required: true,
        //   label: 'Dessert (100g serving)',
        //   align: 'left',
        //   field: row => row.name,
        //   format: val => `${val}`,
        //   sortable: true
        // },
        // { name: 'calories', align: 'center', label: 'Calories', field: 'calories', sortable: true },
        // { name: 'calcium', label: 'Calcium (%)', field: 'calcium', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        // { name: 'iron', label: 'Iron (%)', field: 'iron', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) }
      ],
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

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
    this.loadRetornoPendente()
    this.loadRetornoProcessado()
  }
}
</script>

<style>
</style>
