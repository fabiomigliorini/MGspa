<template>
  <mg-layout drawer back-path="/">
    <template slot="title">
      Pedidos
    </template>

    <template slot="tabHeader">

      <q-tabs v-model="filter.indstatus">
        <q-tab slot="title" name="10" label="Pendentes" />
        <q-tab slot="title" name="20" label="Atendidos" />
        <q-tab slot="title" name="30" label="Cancelados" />
      </q-tabs>

    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer" width="200" style="width: 200px;">
      <q-list no-border>

        <!-- Local -->
        <q-item tag="label">
          <q-field icon="place" class="col-12">
            <q-item-main>
              <mg-select-estoque-local
              label="local"
              v-model="filter.codestoquelocal"
              required />
            </q-item-main>
          </q-field>
        </q-item>

        <!-- Origem -->
        <q-item tag="label">
          <q-item-main>
            <q-field icon="place" class="col-12">
              <mg-select-estoque-local
                label="Origem"
                v-model="filter.codestoquelocalorigem"
                required />
            </q-field>
          </q-item-main>
        </q-item>

        <!-- Grupo Economico -->
        <q-item tag="label">
          <q-item-main>
            <q-field icon="business" class="col-12">
              <q-input
                float-label="Grupo Economico"
                type = "number"
                label="local"
                v-model="filter.codgrupoeconomico"
                required/>
              </q-field>
          </q-item-main>
        </q-item>

        <!-- Tipo -->
        <q-item tag="label">
          <q-item-main>
            <q-field icon="business_center" class="col-12">
              <q-option-group
                type="checkbox"
                v-model="filter.indtipo"
                :options="[
                  { label: 'Compra', value: '10' },
                  { label: 'Transferência', value: '20' },
                  { label: 'Venda', value: '30' }
                ]"
                />
            </q-field>
          </q-item-main>
        </q-item>

        <!-- Destino -->
        <!-- <q-item tag="label">
          <q-item-main>
            <mg-select-estoque-local
            label="Destino"
            v-model="filter.codestoquelocaldestino"
            required>
          </mg-select-estoque-local>
          </q-item-main>
        </q-item> -->

      </q-list>
    </template>

    <div slot="content" >

      <div v-if="pedidos.length > 0">

      <q-infinite-scroll :handler="buscaListagem" ref="infiniteScroll">

      <q-list highlight inset-separator>
        <q-item multiline v-for="pedido in pedidos" :key="pedido.codpedido">
          <q-item-main>
            <q-item-tile label lines="1">
              {{ pedido.tipo }}
              {{ pedido.estoquelocalorigem }}
              {{ pedido.estoquelocal }}
              {{ pedido.grupoeconomico }}
            </q-item-tile>
            <q-item-tile sublabel lines="1">
              {{ numeral(pedido.itens).format('0,0') }} Itens
              <template v-if="pedido.observacoes">
                <br /> {{ pedido.observacoes }}
              </template>
              <!-- {{ pedido.variacao }} -->
            </q-item-tile>
          </q-item-main>

          <q-item-side right>
            <q-tooltip>
              Criado
              {{ moment(pedido.criacao).format('LLLL') }}
              por {{ pedido.usuariocriacao }}.
            </q-tooltip>
            <q-item-tile stamp>
              {{ moment(pedido.criacao).fromNow() }}
              <!-- {{ numeral(parseFloat(pedido.saldoquantidade)).format('0,0') }} -->
              <!-- {{ pedido.um }} -->
            </q-item-tile>


            <q-item-tile icon="watch_later" color="primary" />
          </q-item-side>

        </q-item>

      </q-list>
      </q-infinite-scroll>
      </div>

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
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal
  },
  data () {
    return {
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
      }

      // busca registros
      let vm = this
      vm.$axios.get('pedido', { params }).then(function(request){

        // concatena no array de pedidos os registros retornados pela api
        vm.pedidos = vm.pedidos.concat(request.data)

        // se foi chamado pelo scroll infinito
        if (done) {

          // se nao veio mais registros, interrompe o scroll infinito
          if (request.data.length === 0) {
            vm.$q.notify({
              message: 'Fim!',
              type: 'warning',
            })
            vm.$refs.infiniteScroll.stop()

          // se veio mais registros, continua scroll infinito
          } else {
            vm.$q.notify({
              message: 'Mais ' + request.data.length + ' registros carregados!',
              color: 'primary',
              icon: 'add',
            })
            vm.$refs.infiniteScroll.resume()
          }
          done()
        }

        if (index > 0) {
        }

      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao carregar Pedidos!',
          type: 'negative',
        })
        console.log(error)
      })
    },

  },
  created () {
    this.buscaListagem(0, null)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
