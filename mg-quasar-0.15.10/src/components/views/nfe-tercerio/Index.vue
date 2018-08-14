<template>
  <mg-layout drawer back-path="/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">
      <div class="gutter-y-none">

        <!-- Filtra por filial -->
        <q-item dense>
          <q-item-side icon="location_on"/>
          <q-item-main>
            <mg-select-filial label="Local" v-model="filter.filtroFilial" />
          </q-item-main>
        </q-item>

        <!-- Filtra por pessoa -->
        <q-item dense>
          <q-item-side icon="account_circle"/>
          <q-item-main>
            <mg-autocomplete-pessoa placeholder="Pessoa" v-model="filter.filtroPessoa" :init="filter.filtroPessoa"/>
          </q-item-main>
        </q-item>

        <!-- Buscar por chave -->
        <q-item dense>
          <q-item-side icon="vpn_key"/>
          <q-item-main>
            <q-field >
              <q-input clearable float-label="Chave" v-model="filter.filtroChave" />
            </q-field>
          </q-item-main>
        </q-item>

        <!-- Filtra por natureza-operacao -->
        <q-item dense>
          <q-item-side icon="arrow_drop_down_circle"/>
          <q-item-main>
            <mg-select-natureza-operacao label="Natureza da operação" v-model="filter.filtroNatOp" />
          </q-item-main>
        </q-item>

        <!-- Filtra por situacao -->
        <q-item dense>
          <q-item-side icon="arrow_drop_down_circle"/>
          <q-item-main>
            <q-select radio v-model="filter.filtroSituacao" :options="selectSituacao" float-label="Situação" clearable/>
          </q-item-main>
        </q-item>

        <!-- Filtra por manifestacao -->
        <q-item dense>
          <q-item-side icon="arrow_drop_down_circle"/>
          <q-item-main>
            <q-select radio v-model="filter.filtroManifestacao" :options="selectManifestacao" float-label="Manifestacão" clearable/>
          </q-item-main>
        </q-item>

      </div>
      <q-item-separator />

      <!-- Filtra por data de corte -->
      <q-item tag="label" dense>
        <q-item-main>
          <q-input stack-label="De" type="date" v-model="filter.datainicial" align="center" clearable />
          <q-input stack-label="Até" type="date" v-model="filter.datafinal" align="center" clearable />
        </q-item-main>
      </q-item>

      <q-item-separator />

        <div class="row q-pa-sm">
          <q-btn class="full-width" outline color="primary" @click="modalConsultaSefaz = true" label="Consultar Sefaz" />
        </div>

      <!-- fim do drawer -->
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

          <q-list no-border multiline highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe" class="gutter-y-none q-pa-none">
            <q-item-separator/>
            <q-item>
              <q-item-main>
                <div class="row">

                  <div class="col-sm-2 col-md-1 col-lg-1">
                    <div class="row items-center">
                      <q-icon name="location_on" color="grey"/>&nbsp
                      <small>{{nota.filial}}</small>
                    </div>
                  </div>

                  <div  class="col-sm-10 col-md-7 col-lg-5 cursor-pointer" style="overflow: hidden">
                    <q-btn @click="buscaNFeTerceiro(nota.nfechave)"
                      :color="(nota.indmanifestacao == 210210)?'orange'
                      :(nota.indmanifestacao == 210200)?'green'
                      :(nota.indmanifestacao == 210220 || nota.indmanifestacao == 210240)?'red':'primary'">

                      <q-icon name="vpn_key"/>&nbsp
                      <small>{{nota.nfechave}}</small>
                    </q-btn>&nbsp
                    <q-tooltip>
                      Detalhes da nota
                    </q-tooltip>
                    <q-chip square dense v-if="nota.csitnfe == 1" color="green">Autorizada</q-chip>
                    <q-chip square dense v-if="nota.csitnfe == 3" color="red">Cancelada</q-chip>
                    <q-chip square dense v-if="nota.csitnfe == 2" color="red">Denegada</q-chip>
                  </div>

                  <div class="col-sm-6 col-md-6 col-lg-4">
                    <div class="row items-center">
                      <q-icon name="account_circle" color="grey"/>&nbsp
                      <small>{{nota.emitente.substr(0,30)}}</small>
                    </div>
                    <div class="row">
                      <small class="text-faded">
                        {{nota.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5")}} | {{nota.ie}}
                      </small>
                    </div>
                  </div>

                  <div class="col-sm-6 col-md-6 col-lg-2 gutter-y-xs">
                    <div class="row items-center">
                      <q-icon name="date_range" color="grey"/>&nbsp
                      <small>{{moment(nota.emissao).format("DD MMM YYYY HH:mm:ss")}}</small>
                    </div>

                    <div class="row items-center">
                      <q-icon name="attach_money" color="grey"/>&nbsp
                      <small>R$ {{numeral(parseFloat(nota.valortotal)).format('0,0.00')}}</small>
                    </div>
                  </div>

                </div>
              </q-item-main>
            </q-item>

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
                  <q-card-title align="center">Consulta Sefaz</q-card-title>
                  <q-card-separator />
                  <q-card-main>
                    <mg-select-filial label="Local" v-model="SelectFilial" />
                    <template v-if="nsu">
                      <p  class="text-faded">Última NSU consulta: {{nsu.replace(/^(0+)(\d)/g,"$2")}}</p>
                    </template>
                    <p><q-btn label="progresso" @click="modalProgresso = true"/></p>
                  </q-card-main>
                </q-card>

              </div>
            </div>

            <!--
            <q-list no-border highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
              <q-item-separator/>
              <q-item class="q-py-none">
                <q-item-main>
                  <div class="row">

                    <div class="col-sm-3 col-md-4 col-lg-1 items-center">
                      <q-icon name="location_on" color="grey"/>&nbsp
                      <small>{{nota.filial}}</small>
                    </div>

                    <div class="col-sm-12 col-md-6 col-lg-4 items-center" style="overflow: hidden">
                      <q-icon name="vpn_key" color="grey"/>&nbsp
                      <small>{{nota.nfechave}}</small>
                    </div>

                    <div class="col-sm-12 col-md-6 col-lg-4 items-center">
                      <q-icon name="account_circle" color="grey"/>&nbsp
                      <small>{{nota.emitente}}</small>
                    </div>

                    <div class="col-sm-2 col-md-4 col-lg-1 items-center">
                      <q-icon name="swap_horizontal_circle" color="grey"/>&nbsp
                      <template v-if="nota.tipo == 1">Saída</template>
                      <template v-if="nota.tipo == 0">Entrada</template>
                    </div>

                    <div class="col-sm-2 col-md-4 col-lg-2 items-center">
                      <div class="row items-center">
                        <q-icon name="date_range" color="grey"/>&nbsp
                        <small>{{moment(nota.emissao).format("DD MMM YYYY HH:MM:ss")}}</small>
                      </div>
                      <div class="row items-center">
                        <q-icon name="attach_money" color="grey"/>&nbsp
                        <small>R$ {{numeral(parseFloat(nota.valortotal)).format('0,0.00')}}</small>
                      </div>
                    </div>


                  </div>
                </q-item-main>
              </q-item>
            </q-list>
            -->
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
                    Consultando Sefaz <strong> <span class="text-green">25</span> / 50</strong>
                  </q-card-title>
                  <q-card-separator />
                  <q-card-main>
                    <q-progress :percentage="progresso" stripe animate style="height: 45px" />
                  </q-card-main>
                </q-card>
              </div>
            </center>
            <q-page-sticky position="bottom-right" :offset="[25, 80]">
              <q-btn round color="red" icon="arrow_back" @click="modalProgresso = false" />
            </q-page-sticky>
          </q-page>
        </q-modal>
      </template>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgSelectFilial from '../../utils/select/MgSelectFilial'
import MgSelectNaturezaOperacao from '../../utils/select/MgSelectNaturezaOperacao'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'

export default {
  name: 'nfe-terceiro-lista-dfe',
  components: {
    MgLayout,
    MgSelectFilial,
    MgSelectNaturezaOperacao,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompletePessoa
  },
  data() {
    return {
      selectSituacao: [
        {
          label: 'Autorizada',
          value: '1',
          color: 'green'
        },
        {
          label: 'Cancelada',
          value: '3',
          color: 'red'
        },
        {
          label: 'Danegada',
          value: '2',
          color: 'red'
        }
      ],
      selectManifestacao: [
        {
          label: 'Sem manifestação',
          value: '0',
        },
        {
          label: 'Ciência da operação',
          value: '210210',
          color: 'orange'
        },
        {
          label: 'Opereção realizada',
          value: '210200',
          color: 'green'
        },
        {
          label: 'Operação não realizada',
          value: '210240',
          color: 'red'
        },
        {
          label: 'Operação desconhecida',
          value: '210220',
          color: 'red'
        }
      ],
      filter: {
        datainicial: null,
        datafinal: null,
        filtroSituacao: null,
        filtroManifestacao: null,
        filtroChave: null,
        filtroPessoa: null,
        filtroFilial: null,
      },
      data: {},
      page: 1,
      tabs: 'pendente',
      carregado: false,
      SelectFilial: null,
      nsu: null,
      natop: null,
      currentStep: 'first',
      progresso: 50,
      modalConsultaSefaz: false,
      modalProgresso: false,
      pessoa: null,
      xml: {
        data: []
      }
    }
  },
  watch: {
    SelectFilial: function(filial){
      if (filial){
        this.ultimaNSU(filial)
      }
    },

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.buscaListagem(false, null)
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
        page: vm.page,
        filial: vm.filter.filtroFilial,
        pessoa: vm.filter.filtroPessoa,
        chave: vm.filter.filtroChave,
        datainicial: vm.filter.datainicial,
        datafinal: vm.filter.datafinal,
        manifestacao: vm.filter.filtroManifestacao,
        situacao: vm.filter.filtroSituacao,
        natop: vm.filter.filtroNatOp
      }

      vm.$axios.get('nfe-terceiro/lista-notas',{params}).then(function(request) {
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
      if(this.filial !== null){
        vm.$axios.get('nfe-terceiro/ultima-nsu',{params}).then(function(request){
          vm.nsu = request.data
        }).catch(function(error) {
          console.log(error)
        })
      }
    },

    consultarSefaz: function () {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: this.SelectFilial
      }

      vm.$axios.get('nfe-terceiro/consulta-sefaz',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.ultimaNSU()
          vm.buscaListagem()
          vm.$q.notify({
            message: 'Consulta concluída',
            type: 'positive',
          })
        }
      }).catch(function(error) {
        console.log(error)
      })
    },

  },
  created() {
    this.buscaListagem(false, null)
  }
}
</script>
