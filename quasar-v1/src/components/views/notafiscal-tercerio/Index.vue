<template>
  <mg-layout drawer back-path="/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">
      <div>

        <!-- Filtra por filial -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="location_on"/>
          </q-item-section>
          <q-item-section>
            <mg-select-filial label="Local" v-model="filter.filtroFilial" />
          </q-item-section>
        </q-item>

        <!-- Filtra por pessoa -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="account_circle"/>
          </q-item-section>
          <q-item-section>
            <mg-autocomplete-pessoa label="Pessoa" v-model="filter.filtroPessoa" :init="filter.filtroPessoa"/>
          </q-item-section>
        </q-item>

        <!-- Buscar por chave -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="vpn_key"/>
          </q-item-section>
          <q-item-section>
            <q-input clearable label="Chave" v-model="filter.filtroChave" />
          </q-item-section>
        </q-item>

        <!-- Filtra por natureza-operacao -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="arrow_drop_down_circle"/>
          </q-item-section>
          <q-item-section>
            <mg-select-natureza-operacao label="Natureza da operação" v-model="filter.filtroNatOp" />
          </q-item-section>
        </q-item>

        <!-- Filtra por situacao -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="arrow_drop_down_circle"/>
          </q-item-section>
          <q-item-section>
            <q-select radio v-model="filter.filtroSituacao" :options="selectSituacao" label="Situação" clearable/>
          </q-item-section>
        </q-item>

        <!-- Filtra por manifestacao -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="arrow_drop_down_circle"/>
          </q-item-section>
          <q-item-section>
            <q-select radio v-model="filter.filtroManifestacao" :options="selectManifestacao" label="Manifestacão" clearable/>
          </q-item-section>
        </q-item>

        <!-- Filtra por data de corte -->
        <q-item dense>
          <q-item-section>
            <q-input label="Data Inicial" v-model="filter.datainicial" mask="##/##/####" :rules="['filter.datainicial']">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy ref="qDateProxy" transition-show="scale" transition-hide="scale">
                    <q-date mask="DD-MM-YYYY" v-model="filter.datainicial" @input="() => $refs.qDateProxy.hide()" minimal />
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
          </q-item-section>
        </q-item>

        <q-item dense>
          <q-item-section>
            <q-input label="Data Final" v-model="filter.datafinal" mask="##/##/####" :rules="['filter.datafinal']">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy ref="qDateProxy" transition-show="scale" transition-hide="scale">
                    <q-date mask="DD-MM-YYYY" v-model="filter.datafinal" @input="() => $refs.qDateProxy.hide()" minimal />
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
          </q-item-section>
        </q-item>
      </div>

      <div class="row q-pa-sm">
        <q-btn class="full-width" outline color="primary" @click="modalConsultaSefaz = true" label="Consultar Sefaz" />
      </div>
    </div>

    <div slot="content" v-if="carregado">
      <q-tabs indicator-color="green" v-model="tabs" inline-label class="bg-primary text-white shadow-2">
        <q-tab name="pendentes" label="Pendentes" default/>
        <q-tab name="importadas" label="Importadas" />
        <q-tab name="ignoradas" label="Ignoradas" />
      </q-tabs>


      <!-- Infinite scroll -->
      <q-infinite-scroll @load="loadMore" ref="infiniteScroll">

        <q-list>
          <q-item v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
            <q-item-section>
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
                      :(nota.indmanifestacao == 210220 || nota.indmanifestacao == 210240)?'red':'primary'"
                  >

                    <q-icon name="vpn_key"/>&nbsp
                    <small>{{nota.nfechave}}</small>
                  </q-btn>&nbsp
                  <q-tooltip>
                    Detalhes da nota
                  </q-tooltip>
                  <q-chip square dense v-if="nota.indsituacao == 3
                      || nota.indsituacao == 101 || nota.indsituacao == 151"color="red">
                    Cancelada
                  </q-chip>
                  <q-chip square dense v-if="nota.indsituacao == 2 || nota.indsituacao == 110" color="red">
                    Denegada
                  </q-chip>
                </div>

                <div class="col-sm-6 col-md-6 col-lg-4">
                  <div class="row items-center">
                    <q-icon name="account_circle" color="grey"/>&nbsp
                    <small>{{nota.emitente.substr(0,30)}}</small>
                  </div>
                  <div class="row">
                    <small class="text-faded">
                      {{ numeral(nota.cnpj).format('00000000000000').replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5")}} | {{nota.ie}}
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
            </q-item-section>
          </q-item>

        </q-list>

      </q-infinite-scroll>

      <!-- modal de consulta sefaz -->
      <q-dialog v-if="carregado" v-model="modalConsultaSefaz" persistent maximized transition-show="slide-up" transition-hide="slide-down">
        <q-layout view="Lhh lpR fff" container class="bg-white">
          <q-header class="bg-primary">
            <q-toolbar>
              <q-toolbar-title>Consultar Sefaz</q-toolbar-title>
            </q-toolbar>
          </q-header>

          <q-page-container>
            <q-page padding>
              <div class="row q-pa-sm justify-center">
                <div class="col-xs-12 col-sm-6 col-md-5 col-lg-3">

                  <q-card>
                    <q-card-section>
                      <mg-select-filial label="Local" v-model="SelectFilial"/>
                      <template v-if="nsu">
                        <p  class="text-faded">Última NSU consulta: {{nsu.replace(/^(0+)(\d)/g,"$2")}}</p>
                      </template>
                    </q-card-section>
                  </q-card>

                </div>
              </div>

              <q-list separator v-if="xml.data">
                <q-item class="q-py-none" v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
                  <q-item-section>
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
                  </q-item-section>
                </q-item>
              </q-list>
            </q-page>
          </q-page-container>

          <q-page-sticky position="bottom-right" :offset="[25, 80]">
            <q-btn round color="red" icon="arrow_back" @click="modalConsultaSefaz = false" v-close-popup/>
          </q-page-sticky>
          <q-page-sticky position="bottom-right" :offset="[25, 25]">
            <q-btn round color="primary" icon="done" @click="consultarSefaz()" />
          </q-page-sticky>
        </q-layout>
      </q-dialog>

      <!-- Modal de progresso da consulta -->
      <q-dialog v-model="modalProgresso" maximized>
        <q-layout>
          <q-page-container>
            <q-page padding >

              <div class="row justify-center">
                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>
                    <q-card-section>
                      Consultando Sefaz <strong> <span class="text-green">25</span> / 50</strong>
                    </q-card-section>
                    <q-card-separator/>
                    <q-card-section>
                      <q-linear-progress style="height: 20px"  dark stripe rounded :value="progresso" class="q-mt-md" />
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-page>
          </q-page-container>
          <q-page-sticky position="bottom-right" :offset="[25, 80]">
            <q-btn round color="red" icon="arrow_back" @click="modalProgresso = false" v-close-popup/>
          </q-page-sticky>
        </q-layout>
      </q-dialog>

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
      tabs: 'pendentes',
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
        this.page = 1;
        this.buscaListagem(false, null)
      },
      deep: true,
    }
  },
  methods: {

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++;
      this.buscaListagem(true, done)
    },

    buscaListagem: function(concat, done) {

      // inicializa variaveis
      let vm = this;

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
      };

      vm.$axios.get('nfe-terceiro/lista-notas',{params}).then(function(request) {
        // Se for para concatenar, senao inicializa
        if (vm.page == 1) {
          vm.xml = request.data
        }
        else {
          vm.xml.data = vm.xml.data.concat(request.data.data)
        }
        vm.carregado = true;

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
      this.$router.push('notafiscal-terceiro/detalhes-nfe/' + chave)
    },

    ultimaNSU: function (filial) {
      let vm = this;
      // Monta Parametros da API
      let params = {
        filial: filial
      };
      if(this.filial !== null){
        vm.$axios.get('nfe-terceiro/ultima-nsu',{params}).then(function(request){
          vm.nsu = request.data
        }).catch(function(error) {
          console.log(error)
        })
      }
    },

    consultarSefaz: function () {
      let vm = this;
      // Monta Parametros da API
      let params = {
        filial: this.SelectFilial
      };

      vm.$axios.get('nfe-terceiro/consulta-sefaz',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          });
          return
        }else{
          vm.ultimaNSU();
          vm.buscaListagem();
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
