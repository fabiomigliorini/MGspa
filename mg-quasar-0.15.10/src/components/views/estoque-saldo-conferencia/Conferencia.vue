<template>
  <mg-layout drawer>

    <q-btn flat round slot="menuRight" @click="$router.push('/estoque-saldo-conferencia')">
      <q-icon name="arrow_back" />
    </q-btn>

    <template slot="title">
      {{ header.estoquelocal }} / {{ header.marca }}
    </template>

    <div slot="drawer">

      <q-list-header>Ativos</q-list-header>
      <!-- Filtra Ativos -->
      <q-item tag="label">
        <q-item-side icon="thumb_up">
        </q-item-side>
        <q-item-main>
          <q-item-tile title>Ativos</q-item-tile>
        </q-item-main>
        <q-item-side right>
          <q-radio v-model="filter.inativo" :val="1" />
        </q-item-side>
      </q-item>

      <!-- Filtra Inativos -->
      <q-item tag="label">
        <q-item-side icon="thumb_down">
        </q-item-side>
        <q-item-main>
          <q-item-tile title>Inativos</q-item-tile>
        </q-item-main>
        <q-item-side right>
          <q-radio v-model="filter.inativo" :val="2" />
        </q-item-side>
      </q-item>

      <!-- Filtra Ativos e Inativos -->
      <q-item tag="label">
        <q-item-side icon="thumbs_up_down">
        </q-item-side>
        <q-item-main>
          <q-item-tile title>Ativos e Inativos</q-item-tile>
        </q-item-main>
        <q-item-side right>
          <q-radio v-model="filter.inativo" :val="9" />
        </q-item-side>
      </q-item>

    </div>


    <div slot="content">
        <q-tabs>
          <!-- Tabs - notice slot="title" -->
          <q-tab  slot="title" name="tab-1" label="A conferir" default />
          <q-tab  slot="title" name="tab-2" label="Conferido" />
          <!-- Targets -->
          <q-tab-pane name="tab-1">
            <!-- Se tiver registros -->
            <q-list highlight no-border>
              <template v-for="produto in aconferir">
                <q-item>
                  <q-item-side :image="produto.imagem" v-if="produto.imagem" />

                  <q-item-main >
                    <q-item-tile>
                      {{ produto.produto }}
                      <q-chip tag square pointing="left" color="negative" v-if="produto.inativo">Inativo</q-chip>
                      <q-item-side v-if="produto.variacao">{{ produto.variacao }}</q-item-side>
                    </q-item-tile>
                  </q-item-main>

                  <q-item-side>
                        Saldo
                      <q-item-tile>{{ produto.saldo }}</q-item-tile>
                      <q-item-tile v-if="produto.ultimaconferencia">
                        Ultima conferência
                          {{produto.ultimaconferencia = moment(data).format('DD/MM/YYYY')}}
                      </q-item-tile>
                  </q-item-side>

                </q-item>
                <q-item-separator />
              </template>
            </q-list>
          </q-tab-pane>

          <q-tab-pane name="tab-2">
            <!-- Se tiver registros -->
            <q-list highlight no-border>
              <template v-for="produto in conferidos">
                <q-item>
                  <q-item-side :image="produto.imagem" v-if="produto.imagem" />

                  <q-item-main>
                    <q-item-tile>
                      {{ produto.produto }}
                      <q-chip tag square pointing="left" color="negative" v-if="produto.inativo">Inativo</q-chip>
                      <q-item-side v-if="produto.variacao">
                        {{ produto.variacao }}
                      </q-item-side>
                    </q-item-tile>
                  </q-item-main>

                  <q-item-side>
                      Saldo
                      <q-item-tile>{{ produto.saldo }}</q-item-tile>
                      <q-item-tile v-if="produto.ultimaconferencia">
                      Ultima conferência
                      </q-item-tile>
                      <q-item-tile>{{produto.ultimaconferencia = moment(data).format('DD/MM/YYYY')}}</q-item-tile>
                  </q-item-side>
                  <q-item-side><q-btn round color="primary" icon="history" @click="$router.push('/estoque-saldo-conferencia/HistoricoConferencia')"/></q-item-side>

                </q-item>
                <q-item-separator />
              </template>
            </q-list>
          </q-tab-pane>
        </q-tabs>

        <router-link :to="{ path: '/estoque-saldo-conferencia/create' }">
          <q-page-sticky corner="bottom-right" :offset="[32, 32]">
            <q-btn round color="primary">
              <q-icon name="add" />
            </q-btn>
          </q-page-sticky>
        </router-link>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data () {
    return {
      data: {},
      filter: {
        inativo: 1
      },
      carregado: false
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.buscaListagem(false, null)
      },
      deep: true
    }
  },
  computed: {
    conferidos: function() {
      let vm = this
      if (vm.carregado) {
        return vm.data.produtos.filter(function(produto) {
          return produto.ultimaconferencia != null
        })
      }
    },
    aconferir: function() {
      let vm = this
      if (vm.carregado) {
        return vm.data.produtos.filter(function(produto) {
          return produto.ultimaconferencia  < vm.moment().subtract(15, 'days') || null
        })
      }
    },
    header: {
      get () {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {

    loadData: function (codmarca, codestoquelocal, fiscal) {
      let vm = this
      let params = {
        codestoquelocal: vm.codestoquelocal,
        codmarca: vm.codmarca,
        fiscal: vm.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-listagem/', { params }).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },

    buscaListagem: function () {
      let vm = this
      let params = {
        codmarca: vm.header.codmarca,
        codestoquelocal: vm.header.codestoquelocal,
        fiscal: vm.header.fiscal,
        inativo: vm.filter.inativo
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', { params }).then(function (request) {
        vm.data = request.data
        vm.carregado = true
      }).catch(function (error) {
        console.log(error.response)
      })
    }

  },
  mounted () {
    this.buscaListagem()
  },
  created () {
    this.loadData(this.$route.params.codmarca)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
