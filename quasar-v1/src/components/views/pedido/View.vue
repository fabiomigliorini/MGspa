<template>
  <mg-layout drawer back-path="/pedido">
    <template slot="title">
      Detalhes do Pedido #{{ numeral(id).format("00000000") }}
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
            <q-icon  name="business"/>
          </q-item-section>
          <q-item-section>
            <q-input label="Grupo Econômico" type="number" v-model="filter.codgrupoeconomico"/>
          </q-item-section>
        </q-item>

        <!-- Tipo -->
        <q-item dense>
          <q-item-section>
            <q-icon name="business_center"/>
          </q-item-section>
          <q-item-section>
            <q-option-group type="checkbox" v-model="filter.indtipo"
                            :options="[ { label: 'Compra', value: '10' },
                                        { label: 'Transferência', value: '20' },
                                        { label: 'Venda', value: '30' }
                                      ]"
            />
          </q-item-section>
        </q-item>

       <!--Destino-->
      <!-- <q-item dense>
        <q-item-section>
          <mg-select-estoque-local label="Destino" v-model="filter.codestoquelocaldestino"/>
        </q-item-section>
      </q-item>-->

      </q-list>
    </template>

    <div slot="content" >
      <q-infinite-scroll :handler="buscaListagem" ref="infiniteScroll" v-if="pedidos.length > 0">
        <q-list separator>

          <q-item v-for="pedido in pedidos" :key="pedido.codpedido" @click.native="abrirPedido(pedido.codpedido)">
            <q-item-section>
              <q-item-label>
                {{ pedido.tipo }}
                {{ pedido.estoquelocalorigem }}
                {{ pedido.estoquelocal }}
                {{ pedido.grupoeconomico }}
              </q-item-label>

              <q-item-label caption>
                {{ numeral(pedido.itens).format('0,0') }} Itens
                <template v-if="pedido.observacoes">
                  <br /> {{ pedido.observacoes }}
                </template>
                <!-- {{ pedido.variacao }} -->
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-tooltip>
                Criado {{ moment(pedido.criacao).format('LLLL') }} por {{ pedido.usuariocriacao }}.
              </q-tooltip>
              <q-item-label>
                {{ moment(pedido.criacao).fromNow() }}
                <!-- {{ numeral(parseFloat(pedido.saldoquantidade)).format('0,0') }} -->
                <!-- {{ pedido.um }} -->
              </q-item-label>
              <q-item-label>
                <q-icon name="watch_later" color="primary"/>
              </q-item-label>
            </q-item-section>

          </q-item>
        </q-list>
      </q-infinite-scroll>
      <!-- <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn round color="primary" @click="confirmarRequisicoes()" icon="done" />
      </q-page-sticky> -->
    </div>
  </mg-layout>
</template>
<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
export default {
  name: 'pedido-view',
  components: {
    MgLayout,
    MgSelectEstoqueLocal
  },
  data () {
    return {
      id: null,
      pedidos: [],
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
        this.buscaListagem(0, null)
      },
      deep: true
    }
  },

  methods: {

    buscaListagem: function (index, done) {

      // se primeiro registro scroll infinito
      // limpa array de pedidos
      if (index == 0) {
        this.pedidos = []
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

        // concatena no array de pedidos os registros retornados pela api
        vm.pedidos = vm.pedidos.concat(request.data);

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
  mounted() {
    this.id = this.$route.params.id;
    console.log(this.$route.params)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
