<template>
  <mg-layout drawer back-path="/estoque-saldo-conferencia/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">

      <q-list-header>Filtros</q-list-header>

      <!-- Filtra por filial -->
      <q-item dense>
        <q-item-side icon="store"/>
        <q-item-main>
          <mg-select-estoque-local label="Local" v-model="data.codestoquelocal" ></mg-select-estoque-local>
        </q-item-main>
      </q-item>

      <!-- Filtra por pessoa -->
      <q-item dense>
        <q-item-side icon="account_circle"/>
        <q-item-main>
          <q-field >
            <q-input float-label="Fornecedor" v-model="filter.filtro" />
          </q-field>
        </q-item-main>
      </q-item>

      <!-- Buscar por chave -->
      <q-item dense>
        <q-item-side icon="vpn_key"/>
        <q-item-main>
          <q-field >
            <q-input float-label="Chave" v-model="filter.filtro" />
          </q-field>
        </q-item-main>
      </q-item>

      <q-item-separator />

      <!-- Filtra por data de corte -->
      <q-list-header>Filtrar por data</q-list-header>
      <q-item tag="label" dense>
        <q-item-main>
          <q-input stack-label="De" type="date" v-model="filter.datainicial" align="center" clearable />
          <q-input stack-label="Até" type="date" v-model="filter.datafinal" align="center" clearable />
        </q-item-main>
      </q-item>

      <q-item-separator />

      <!-- filtar por manifestacao -->
      <q-item tag="label" dense>
        <q-item-main>

          <!-- filtar por manifestacao -->
          <q-btn-dropdown class="full-width" outline color="primary" label="Manifestação">
            <q-list link>
              <q-item>
                <q-item-side icon="lens"/>
                <q-item-main>
                  <q-item-tile label>Sem manifestação</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="orange"/>
                <q-item-main>
                  <q-item-tile label>Ciência da operação</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="green"/>
                <q-item-main>
                  <q-item-tile label>Operação realizada</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="red"/>
                <q-item-main>
                  <q-item-tile label>Operação desconhecida</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="red"/>
                <q-item-main>
                  <q-item-tile label>Operação não realizada</q-item-tile>
                </q-item-main>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-item-main>
      </q-item>

      <q-item>
        <q-item-main>
          <!-- filtar por situacao -->
          <q-btn-dropdown class="full-width" color="primary" label="Situação" outline>
            <q-list link>
              <q-item>
                <q-item-side icon="lens" color="green"/>
                <q-item-main>
                  <q-item-tile label>Autorizada</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="red"/>
                <q-item-main>
                  <q-item-tile label>Cancelada</q-item-tile>
                </q-item-main>
              </q-item>
              <q-item>
                <q-item-side icon="lens" color="red"/>
                <q-item-main>
                  <q-item-tile label>Denegada</q-item-tile>
                </q-item-main>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-item-main>
      </q-item>
      </q-item-main>
      </q-item>

      <q-item>
        <q-item-main>
          <q-btn class="full-width" outline color="primary" label="Consultar Sefaz" />
        </q-item-main>
      </q-item>
    </div>

    <template slot="tabHeader">
      <q-tabs v-model="filter.tabs">
        <q-tab slot="title" name="pendentes"  label="Pendentes" default/>
        <q-tab slot="title" name="importadas"  label="Importadas"/>
        <q-tab slot="title" name="ignoradas"  label="Ignoradas"/>
      </q-tabs>
    </template>

    <div slot="content">

      <template v-if="carregado">
        <!-- Infinite scroll -->
        <q-infinite-scroll :handler="loadMore" ref="infiniteScroll">

          <q-list highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
            <q-item >

              <q-item-side>
                <q-item-tile v-if="nota.codfilial">
                  {{nota.codfilial}}
                </q-item-tile>
                <q-item-tile>
                  {{nota.codnotafiscalterceirodfe}}
                </q-item-tile>
              </q-item-side>

              <q-item-main>
                <q-item-tile>
                  <small>{{nota.nfechave}}</small>
                </q-item-tile>
              </q-item-main>

              <q-item-main>
                <q-item-tile>
                  {{nota.emitente.substr(0, 25)}}
                </q-item-tile>
                <q-item-tile sublabel>
                  {{nota.cnpj}} | {{nota.ie}}
                </q-item-tile>
              </q-item-main>

              <q-item-main>
                <q-item-tile>
                  R${{nota.valortotal}}
                </q-item-tile>
                <q-item-tile sublabel>
                  {{nota.tipo}}
                </q-item-tile>
              </q-item-main>

              <q-item-side right>
                <q-item-tile>Emitida: {{moment(nota.emissao).format("DD MMM YYYY")}}</q-item-tile>
              </q-item-side>

            </q-item>
            <q-item-separator />
          </q-list>

        </q-infinite-scroll>
      </template>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      page: 1,
      filter: {
        tabs: 'pendente',
        filtro: null,
        datainicial: null,
        datafinal:null,
      },
      data: {},
      carregado: false,
    }
  },
  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.buscaListagem(false, null)
      },
      deep: true
    }

  },
  methods: {

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.buscaListagem(true, done)
    },

    buscaListagem: function(concat, done) {
      console.log(this.data.codestoquelocal)

      // inicializa variaveis
      let vm = this

      // se for primeira pagina, marca como dados nao carregados ainda
      if (this.page == 1) {
        vm.carregado = false
      }

      // Monta Parametros da API
      let params = {
        page: vm.page
      }

      vm.$axios.get('nfe-terceiro/lista-dfe',{params}).then(function(request) {

        // Se for para concatenar, senao inicializa
        if (vm.page == 1) {
          vm.xml = request.data
        }
        else {
          vm.xml.data = vm.xml.data.concat(request.data.data)
        }
        vm.carregado = true

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (request.data.data.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // Executa done do scroll infinito
        if (done) {
          done()
        }

      }).catch(function(error) {
        console.log(error)
      })
    },

  },
  created() {
    this.buscaListagem();
  }
}
</script>
