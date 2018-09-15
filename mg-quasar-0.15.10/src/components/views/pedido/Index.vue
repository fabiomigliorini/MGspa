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

      </q-list>
    </template>

    <div slot="content" >

      <div v-if="data.length > 0">

      <q-infinite-scroll :handler="loadData" ref="infiniteScroll">

      <q-list highlight separator>

        <q-item multiline v-for="row in data" :key="row.codpedido" >
          <q-item-main>
            <q-item-tile label lines="1" >
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
            </q-item-tile>
            <q-item-tile sublabel lines="1">
              {{ numeral(row.itens).format('0,0') }} Itens
              <template v-if="row.observacoes">
                <br /> {{ row.observacoes }}
              </template>
            </q-item-tile>
          </q-item-main>

          <q-item-side right>
            <q-tooltip>
              Criado
              {{ moment(row.criacao).format('LLLL') }}
              por {{ row.usuariocriacao }}.
            </q-tooltip>
            <q-item-tile stamp>
              {{ moment(row.criacao).fromNow() }}
            </q-item-tile>


            <q-item-tile icon="watch_later" color="primary" />
          </q-item-side>

        </q-item>

      </q-list>
      </q-infinite-scroll>
      </div>
      <br />
      <br />
      <br />
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
      }

      // busca registros
      let vm = this
      vm.$axios.get('pedido', { params }).then(function(request){

        // concatena no array de data os registros retornados pela api
        vm.data = vm.data.concat(request.data)

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
    this.loadData(0, null)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
