<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Remessa e Retorno de Boletos
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">

      <!--Retornos para Processar-->
      <div class="row q-col-gutter-md" v-if="!loading">

        <div class="col-xs-6 col-sm-4 col-md-3 col-lg-2" v-for="portador in retornoPendente">
          <q-card>
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">{{portador.portador}}</div>
            </q-card-section>

            <q-list dense>
              <q-item clickable v-for="arquivo in portador.retornos">
                <q-item-section>
                  <q-checkbox v-model="retornoProcessar" :val="portador.codportador + '-' + arquivo" :label="arquivo" />
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

      <div class="row q-col-gutter-md" v-if="!loading">
        <q-card>
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Últimos Retornos Processados</div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <q-table
              dense
              flat
              :data="data"
              :columns="columns"
              row-key="name"
              @click="abrirRetorno"
            />
          </q-card-section>
        </q-card>
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
      loading: true,
      processando: false,
      retornoProcessar: [],
      retornoPendente: [],

      columns: [
        {
          name: 'name',
          required: true,
          label: 'Dessert (100g serving)',
          align: 'left',
          field: row => row.name,
          format: val => `${val}`,
          sortable: true
        },
        { name: 'calories', align: 'center', label: 'Calories', field: 'calories', sortable: true },
        { name: 'fat', label: 'Fat (g)', field: 'fat', sortable: true },
        { name: 'carbs', label: 'Carbs (g)', field: 'carbs' },
        { name: 'protein', label: 'Protein (g)', field: 'protein' },
        { name: 'sodium', label: 'Sodium (mg)', field: 'sodium' },
        { name: 'calcium', label: 'Calcium (%)', field: 'calcium', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'iron', label: 'Iron (%)', field: 'iron', sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) }
      ],
      data: [
        {
          name: 'Frozen Yogurt',
          calories: 159,
          fat: 6.0,
          carbs: 24,
          protein: 4.0,
          sodium: 87,
          calcium: '14%',
          iron: '1%'
        },
        {
          name: 'Ice cream sandwich',
          calories: 237,
          fat: 9.0,
          carbs: 37,
          protein: 4.3,
          sodium: 129,
          calcium: '8%',
          iron: '1%'
        },
        {
          name: 'Eclair',
          calories: 262,
          fat: 16.0,
          carbs: 23,
          protein: 6.0,
          sodium: 337,
          calcium: '6%',
          iron: '7%'
        },
        {
          name: 'Cupcake',
          calories: 305,
          fat: 3.7,
          carbs: 67,
          protein: 4.3,
          sodium: 413,
          calcium: '3%',
          iron: '8%'
        },
        {
          name: 'Gingerbread',
          calories: 356,
          fat: 16.0,
          carbs: 49,
          protein: 3.9,
          sodium: 327,
          calcium: '7%',
          iron: '16%'
        },
        {
          name: 'Jelly bean',
          calories: 375,
          fat: 0.0,
          carbs: 94,
          protein: 0.0,
          sodium: 50,
          calcium: '0%',
          iron: '0%'
        },
        {
          name: 'Lollipop',
          calories: 392,
          fat: 0.2,
          carbs: 98,
          protein: 0,
          sodium: 38,
          calcium: '0%',
          iron: '2%'
        },
        {
          name: 'Honeycomb',
          calories: 408,
          fat: 3.2,
          carbs: 87,
          protein: 6.5,
          sodium: 562,
          calcium: '0%',
          iron: '45%'
        },
        {
          name: 'Donut',
          calories: 452,
          fat: 25.0,
          carbs: 51,
          protein: 4.9,
          sodium: 326,
          calcium: '2%',
          iron: '22%'
        },
        {
          name: 'KitKat',
          calories: 518,
          fat: 26.0,
          carbs: 65,
          protein: 7,
          sodium: 54,
          calcium: '12%',
          iron: '6%'
        }
      ]
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {
    // carrega registros da api
    loadRetornoProcessar: debounce(function () {
      // inicializa variaveis
      let vm = this;
      let params = vm.filter;
      this.loading = true;
      // faz chamada api
      vm.retornoProcessar = [];
      vm.$axios.get('boleto/retorno-pendente', { params }).then(response => {
        vm.retornoPendente = response.data;
        vm.retornoPendente.forEach((portador) => {
          portador.retornos.forEach((arquivo) => {
            vm.retornoProcessar = vm.retornoProcessar.concat([portador.codportador + '-' + arquivo]);
          });
        });
        this.loading = false;
      })
    }, 500),

    processar: throttle(function(codportador) {
      this.retornoProcessar.forEach((itemProcessar) => {
        if (itemProcessar.startsWith(codportador + '-')) {
          let vm = this;
          let arquivo = itemProcessar.replace(codportador + '-', '');
          vm.$axios.post('boleto/processar-retorno', {
            codportador: codportador,
            'arquivo': arquivo
          }).then(response => {
            vm.retornoProcessar = vm.retornoProcessar.filter(function(itemFiltrar){
              return itemFiltrar != itemProcessar;
            });
            let pendente = vm.retornoPendente.find( portador => portador.codportador === codportador );
            pendente.retornos = pendente.retornos.filter(function(itemFiltrar) {
              return itemFiltrar != arquivo;
            });
            vm.retornoPendente = vm.retornoPendente.filter(function(itemFiltrar){
              return itemFiltrar.retornos.length > 0;
            });
            console.log(pendente);
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
          });
        }
      });
    }, 500),

    abrirRetorno: debounce(function(evt, row) {
      console.log(evt);
    }, 500),
  },
  created () {
    this.loadRetornoProcessar()
  }
}
</script>

<style>
</style>
