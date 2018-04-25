<template>
  <mg-layout drawer>

    <q-btn flat round slot="menuRight" @click="$router.push('/estoque-saldo-conferencia')">
      <q-icon name="arrow_back" />
    </q-btn>

    <template slot="title">
      {{data.local.estoquelocal }} / {{ data.marca.marca }}
    </template>

    <div slot="drawer">

      <!-- Filtra Ativos -->
      <q-list-header>Filtros</q-list-header>
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

      <q-item-separator />

      <!-- Filtra por data de corte -->
      <q-list-header>Data de Corte Conferência</q-list-header>
      <q-item tag="label">
        <q-item-main>
          <q-datetime v-model="filter.dataCorte" type="datetime" format="DD MMM YYYY HH:mm:SS"/>
        </q-item-main>
      </q-item>

    </div>


    <div slot="content">
        <q-tabs>
          <!-- Tabs - notice slot="title" -->
          <q-tab  slot="title" name="tab-1" label="A conferir" default />
          <q-tab  slot="title" name="tab-2" label="Conferido" />
          <!-- Targets -->

            <!-- Se tiver registros, traz os produtos a conferir -->
            <q-tab-pane name="tab-1">
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
                      </q-item-tile>
                      <q-item-tile v-if="produto.ultimaconferencia">{{ moment(produto.ultimaconferencia).format('DD/MMM/YY')}}</q-item-tile>
                    </q-item-side>
                    <q-item-side>
                      <q-btn round color="primary" icon="history"
                      @click="$router.push('/estoque-saldo-conferencia/historicoproduto/'
                      + produto.codprodutovariacao + '/' + data.local.codestoquelocal)"/>
                    </q-item-side>

                  </q-item>
                  <q-item-separator />
                </template>
            </q-list>
          </q-tab-pane>

          <!-- Se tiver registros, traz os produtos conferidos -->
          <q-tab-pane name="tab-2">
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
                      <q-item-tile v-if="produto.ultimaconferencia">
                        {{ moment(produto.ultimaconferencia).format('DD/MMM/YY')}}
                      </q-item-tile>
                  </q-item-side>
                  <q-item-side>
                    <q-btn round color="primary" icon="history"
                    @click="$router.push('/estoque-saldo-conferencia/historicoproduto/'
                    + produto.codprodutovariacao + '/' + produto.local.codestoquelocal)"/>
                  </q-item-side>

                </q-item>
                <q-item-separator />
              </template>
            </q-list>
          </q-tab-pane>
        </q-tabs>

        <router-link :to="{ path: '/estoque-saldo-conferencia/conferencia' }">
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
  name: 'estoque-saldo-conferencia-listagem',
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
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 1,
        dataCorte: null
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
          return (produto.ultimaconferencia != null) &&
            (!vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
        })
      }
    },
    aconferir: function() {
      let vm = this
      if (vm.carregado) {
        return vm.data.produtos.filter(function(produto) {
          return (produto.ultimaconferencia == null) ||
            (vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
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

    // verHistorico: function () {
    //   let params = [
    //     this.produto.codproduto
    //   ]
    //   this.$router.push('/estoque-saldo-conferencia/historicoconferencia/'
    //     + this.produto.codproduto
    //   )
    // },

    buscaListagem: function () {
      let vm = this
      let params = {
        codestoquelocal: vm.filter.codestoquelocal,
        codmarca: vm.filter.codmarca,
        fiscal: vm.filter.fiscal,
        inativo: vm.filter.inativo
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', { params }).then(function (request) {
        vm.data = request.data
        console.log(vm.data.produtos[0].ultimaconferencia)
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
    this.filter.codestoquelocal = this.$route.params.codestoquelocal
    this.filter.codmarca = this.$route.params.codmarca
    this.filter.fiscal = this.$route.params.fiscal
    this.filter.data = new Date(parseInt(this.$route.params.data))
    this.filter.dataCorte = this.moment().subtract(15, 'days')
    console.log(this.filter.data)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
