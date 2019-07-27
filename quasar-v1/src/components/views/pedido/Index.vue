<template>
  <mg-layout drawer back-path="/">
    <template slot="title">
      Pedidos
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">
      <q-list>

        <!-- Local -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="place"/>
          </q-item-section>
          <q-item-section>
            <mg-select-estoque-local label="local" v-model="filter.codestoquelocal"/>
          </q-item-section>
        </q-item>

        <!-- Origem -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="place"/>
          </q-item-section>
          <q-item-section>
            <mg-select-estoque-local label="Origem" v-model="filter.codestoquelocalorigem"/>
          </q-item-section>
        </q-item>

        <!-- Grupo Economico -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="business"/>
          </q-item-section>
          <q-item-section>
            <q-input label="Grupo Economico" type = "number" v-model="filter.codgrupoeconomico"/>
          </q-item-section>
        </q-item>

        <!-- Tipo -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="business_center"/>
          </q-item-section>
          <q-item-section>
              <q-option-group type="checkbox"
                              v-model="filter.indtipo"
                              :options="[ { label: 'Compra', value: '10' },
                                          { label: 'Transferência', value: '20' },
                                          { label: 'Venda', value: '30' }
                                         ]"
              />
          </q-item-section>
        </q-item>

      </q-list>
    </template>

    <div slot="content" >

      <q-tabs v-model="filter.indstatus" inline-label class="bg-primary text-white shadow-2">
        <q-tab name="10" label="Pendentes" />
        <q-tab name="20" label="Atendidos" />
        <q-tab name="30" label="Cancelados" />
      </q-tabs>

      <q-infinite-scroll @load="loadData" ref="infiniteScroll">

        <q-list separator v-if="data.length > 0">

          <q-item v-for="row in data" :key="row.codpedido" >
            <q-item-section>
              <q-item-label>
                <router-link :to="'/pedido/' + row.codpedido" style="text-decoration: none; color: blue">
                  <!-- <a :href="'/pedido/'+row.codpedido"> -->
                  {{ row.tipo }}
                  <template v-if="row.estoquelocalorigem">
                    de
                    {{ row.estoquelocalorigem }}
                    para
                  </template>
                  {{ row.estoquelocal }}
                  <template v-if="row.grupoeconomico">
                    de {{ row.grupoeconomico }}
                  </template>
                  <!-- </a> -->
                </router-link>
              </q-item-label>
              <q-item-label caption>
                {{ numeral(row.itens).format('0,0') }} Itens
                <template v-if="row.observacoes">
                  <br /> {{ row.observacoes }}
                </template>
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-tooltip>
                Criado
                {{ moment(row.criacao).format('LLLL') }}
                por {{ row.usuariocriacao }}.
              </q-tooltip>
              <q-item-label stamp>
                {{ moment(row.criacao).fromNow() }}
              </q-item-label>

              <q-item-label>
                <q-icon name="watch_later" color="primary"/>
              </q-item-label>
            </q-item-section>

          </q-item>

        </q-list>
      </q-infinite-scroll>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn round color="primary" @click="create()" icon="add" />
      </q-page-sticky>
    </div>
  </mg-layout>
</template>
<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
export default {
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal
  },
  data () {
    return {
      data: [],
      filter: {
        indstatus: "10",
        indtipo: [],
        codestoquelocal: null,
        codestoquelocalorigem: null,
        codgrupoeconomico: null
      }
    }
  },
  watch: {
    // observa filter, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.loadData(0, null)
      },
      deep: true
    }
  },
  methods: {
    create: function () {
      this.$router.push('/pedido/create')
    },

    view: function (id) {
      this.$router.push('/pedido/' + id)
    },
    loadData: function (index, done) {
      // se primeiro registro scroll infinito
      // limpa array de data
      if (index == 0) {
        this.data = []
      }
      // monta parametros de busca
      let params = {
        indstatus: this.filter.indstatus,
        indtipo: this.filter.indtipo,
        codestoquelocal: this.filter.codestoquelocal,
        codestoquelocalorigem: this.filter.codestoquelocalorigem,
        codgrupoeconomico: this.filter.codgrupoeconomico,
        page: (index + 1)
      };

      // busca registros
      let vm = this;
      vm.$axios.get('pedido', { params }).then(function(request){

        // concatena no array de data os registros retornados pela api
        vm.data = vm.data.concat(request.data);

        // se foi chamado pelo scroll infinito
        if (done) {

          // se nao veio mais registros, interrompe o scroll infinito
          if (request.data.length === 0) {
            vm.$q.notify({
              message: 'Fim!',
              color: 'warning',
            });
            vm.$refs.infiniteScroll.stop()

          // se veio mais registros, continua scroll infinito
          } else {
            vm.$q.notify({
              message: 'Mais ' + request.data.length + ' registros carregados!',
              color: 'primary',
              icon: 'add',
            });
            vm.$refs.infiniteScroll.resume()
          }
          done()
        }

        if (index > 0) {
        }

      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao carregar Pedidos!',
          color: 'negative',
        });
        console.log(error)
      })
    },

  },
  created () {
    this.loadData(0, null)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
