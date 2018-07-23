<template>
  <mg-layout drawer back-path="/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">

      <q-list-header>Filtros</q-list-header>

      <!-- Filtra por filial -->
      <q-item dense>
        <q-item-side icon="store"/>
        <q-item-main>
          <mg-select-filial label="Local" v-model="filter.filtroFilial" />
        </q-item-main>
      </q-item>

      <!-- Filtra por pessoa -->
      <q-item dense>
        <q-item-side icon="account_circle"/>
        <q-item-main>
          <q-field >
            <q-input float-label="Fornecedor" v-model="filter.filtroPessoa" />
          </q-field>
        </q-item-main>
      </q-item>

      <!-- Buscar por chave -->
      <q-item dense>
        <q-item-side icon="vpn_key"/>
        <q-item-main>
          <q-field >
            <q-input float-label="Chave" v-model="filter.filtroChave" />
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
          <q-btn class="full-width" outline color="primary" @click="modalConsultaSefaz = true" label="Consultar Sefaz" />
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

          <q-list multiline highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">

            <q-item>
              <q-item-main>
                <div class="row">

                  <div class="col-sm-2 col-md-1 col-lg-1">
                    <div class="row">
                      <small class="text-faded">Filial</small>
                    </div>
                    <div class="row">
                      <small>{{nota.codfilial}}</small>
                    </div>
                  </div>

                  <div @click="buscaNFeTerceiro(nota.nfechave)" class="col-sm-10 col-md-7 col-lg-5 cursor-pointer" style="overflow: hidden">
                    <q-icon name="vpn_key"/>
                    <small>{{nota.nfechave}}</small>
                    <q-tooltip>
                      Detalhes da nota
                    </q-tooltip>
                  </div>

                  <div class="col-sm-6 col-md-6 col-lg-4">
                    <div class="row">
                      <small>{{nota.emitente.substr(0,30)}}</small>
                    </div>
                    <div class="row">
                      <small class="text-faded">
                        {{nota.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5")}} | {{nota.ie}}
                      </small>
                    </div>
                  </div>

                  <div class="col-sm-3 col-md-3 col-lg-1">
                    <div class="row">
                      <small class="text-faded">Total</small>
                    </div>
                    <div class="row">
                      <small>R$ {{numeral(parseFloat(nota.valortotal)).format('0,0.00')}}</small>
                    </div>
                  </div>

                  <div class="col-sm-3 col-md-3 col-lg-1">
                    <div class="row">
                      <small class="text-faded">Emissão</small>
                    </div>
                    <div class="row">
                      <small>{{moment(nota.emissao).format("DD MMM YYYY")}}</small>
                    </div>
                  </div>

                </div>
              </q-item-main>

              <q-item-side right>
                <q-btn dense @click="downloadNFe(nota.codfilial, nota.nfechave)" round color="primary" icon="cloud_download"/>
                <q-tooltip>
                  Baixar XML
                </q-tooltip>
              </q-item-side>

            </q-item>

            <q-item-separator />
          </q-list>

        </q-infinite-scroll>
      </template>

      <!-- modal de consulta sefaz -->
      <template v-if="carregado">
        <q-modal v-model="modalConsultaSefaz" maximized>
          <q-page padding>

            <div class="row q-pa-sm">
              <div class="col-xs-12 col-sm-6 col-md-5 col-lg-3">

                <q-card>
                  <q-card-title>Consulta Sefaz</q-card-title>
                  <q-card-separator />
                  <q-card-main>
                    <mg-select-filial label="Local" v-model="data.consultaSefaz" />
                    <p class="text-faded">Última NSU consultada:</p>
                    <p>
                      <q-btn label="progresso" @click="modalProgresso = true"/>
                    </p>
                  </q-card-main>
                </q-card>

              </div>
            </div>

            <q-list no-border highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
              <q-item>
                <q-item-main>
                  <div class="row">

                    <div class="col-sm-12 col-md-6 col-lg-4" style="overflow: hidden">
                      <q-icon name="vpn_key"/>
                      <small>{{nota.nfechave}}</small>
                    </div>

                    <div class="col-sm-12 col-md-6 col-lg-4">
                      <small>{{nota.emitente}}</small>
                    </div>

                    <div class="col-sm-3 col-md-4 col-lg-2">
                      <small>Natureza da operação</small>
                    </div>

                    <div class="col-sm-2 col-md-4 col-lg-1">
                      <small>R$ {{numeral(parseFloat(nota.valortotal)).format('0,0.00')}}</small>
                    </div>

                    <div class="col-sm-2 col-md-4 col-lg-1">
                      <small>{{moment(nota.emissao).format("DD MMM YYYY")}}</small>
                    </div>

                  </div>
                </q-item-main>
              </q-item>
            </q-list>
          </q-page>

          <q-page-sticky position="bottom-right" :offset="[25, 80]">
            <q-btn round color="red" icon="arrow_back" @click="modalConsultaSefaz = false" />
          </q-page-sticky>

          <q-page-sticky position="bottom-right" :offset="[25, 25]">
            <q-btn round color="primary" icon="done" @click="consultarSefaz()" />
          </q-page-sticky>
        </q-modal>
      </template>

      <!-- Modal de progresso da consulta -->
      <template>
        <q-modal v-model="modalProgresso" maximized>
          <q-page padding >
            <center>
              <div style="max-width:50vw">
                <q-card>
                  <q-card-title>
                    Consultando Sefaz <span class="text-green">25</span> / <strong>50</strong>
                  </q-card-title>
                  <q-card-separator />
                  <q-card-main>
                    <q-progress :percentage="progresso" stripe animate style="height: 45px" />
                  </q-card-main>
                </q-card>
                <q-btn color="primary" @click="modalProgresso = false" label="Close"/>
              </div>
            </center>
          </q-page>
        </q-modal>
      </template>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgSelectFilial from '../../utils/select/MgSelectFilial'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro-lista-dfe',
  components: {
    MgLayout,
    MgSelectFilial,
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
        filtroFilial: null,
        filtroPessoa: null,
        filtroChave: null,
        datainicial: null,
        datafinal: null,
      },
      data: {},
      carregado: false,
      modalConsultaSefaz: false,
      modalProgresso: false,
      progresso: 75,
      consultaSefaz: null
    }
  },
  watch: {
    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.buscaListagem(false, null)
        this.page = 1
      },
      deep: true,
    }
  },
  methods: {

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.buscaListagem(true, done)
    },

    buscaListagem: function(concat, done) {

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

    buscaNFeTerceiro: function (chave) {
      this.$router.push('nfe-terceiro/detalhes-nfe/' + chave)
    },

    ultimaNSU: function (filial) {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: filial
      }
      console.log(params)
    //   vm.$axios.get('nfe-terceiro/consulta-sefaz',{params}).then(function(request){
    //     if (request.data !== true) {
    //       vm.$q.notify({
    //         message: request.data,
    //         type: 'negative',
    //       })
    //       return
    //     }else{
    //       vm.$q.notify({
    //         message: 'Consulta concluída',
    //         type: 'positive',
    //       })
    //     }
    //   }).catch(function(error) {
    //     console.log(error)
    //   })
    // },
    },

    consultarSefaz: function () {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: this.data.consultaSefaz
      }

      vm.$axios.get('nfe-terceiro/consulta-sefaz',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.$q.notify({
            message: 'Consulta concluída',
            type: 'positive',
          })
        }
      }).catch(function(error) {
        console.log(error)
      })
    },

    downloadNFe: function(filial, chave) {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: filial,
        chave: chave
      }
      vm.$axios.get('nfe-terceiro/download-nfe',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.$q.notify({
            message: 'Download concluído',
            type: 'positive',
          })
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
