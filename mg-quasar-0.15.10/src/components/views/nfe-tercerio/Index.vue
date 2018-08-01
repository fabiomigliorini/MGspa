<template>
  <mg-layout drawer back-path="/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">
      <div class="gutter-y-none">

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
            <mg-autocomplete-pessoa placeholder="Pessoa" v-model="filter.filtroPessoa" :init="filtroPessoa"/>
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
            <mg-select-natureza-operacao label="Natureza da operação" v-model="natop" />
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
                    <div class="row">
                      <q-icon name="store" color="grey"/>&nbsp
                      <small>{{nota.codfilial}}</small>
                    </div>
                  </div>

                  <div @click="buscaNFeTerceiro(nota.nfechave)" class="col-sm-10 col-md-7 col-lg-5 cursor-pointer" style="overflow: hidden">
                    <q-icon name="vpn_key" color="grey"/>&nbsp
                    <small>{{nota.nfechave}}</small>
                    <q-tooltip>
                      Detalhes da nota
                    </q-tooltip>
                  </div>

                  <div class="col-sm-6 col-md-6 col-lg-4">
                    <div class="row">
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
                    <div class="row">
                      <small class="text-faded">Emissão &nbsp</small>
                      <small>{{moment(nota.emissao).format("DD MMM YYYY")}}</small>
                    </div>

                    <div class="row">
                      <small class="text-faded">Total &nbsp</small>
                      <small>R$ {{numeral(parseFloat(nota.valortotal)).format('0,0.00')}}</small>
                    </div>
                  </div>

                </div>
              </q-item-main>

              <q-item-side right class="gutter-y-xs">
                <q-item-tile>
                  <q-btn color="primary" @click.native="modalManifestacao = true, chaveManifestacao = nota.nfechave, filialManifestacao = nota.codfilial" round dense>
                    <q-icon name="arrow_drop_down_circle" size="25px"/>
                  </q-btn>
                  <q-tooltip anchor="top left" self="bottom middle">
                    Manifestação
                  </q-tooltip>
                </q-item-tile>

                <q-item-tile v-if="nota.download">
                  <q-btn dense round color="green" icon="cloud_download"/>
                  <q-tooltip anchor="top left" self="bottom middle">
                    O XML já está baixado
                  </q-tooltip>
                </q-item-tile>
                <q-item-tile v-else="!nota.download">
                  <q-btn dense @click="downloadNFe(nota.codfilial, nota.nfechave)" round color="primary" icon="cloud_download"/>
                  <q-tooltip anchor="top left" self="bottom middle">
                    Baixar XML
                  </q-tooltip>
                </q-item-tile>
              </q-item-side>

              <q-item-separator inset/>
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
                    <mg-select-filial label="Local" v-model="filial" />
                    <template v-if="nsu">
                      <p  class="text-faded">Última NSU consulta: {{nsu.replace(/^(0+)(\d)/g,"$2")}}</p>
                    </template>
                    <p><q-btn label="progresso" @click="modalProgresso = true"/></p>
                  </q-card-main>
                </q-card>

              </div>
            </div>

            <q-list no-border highlight v-for="nota in xml.data" :key="nota.codnotafiscalterceirodfe">
              <q-item-separator/>
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

      <!-- MODAL DE MANIFESTACAO -->
      <template>
        <q-modal v-model="modalManifestacao">

          <q-card>
            <q-card-title align="center">
              Manifestacão
            </q-card-title>
            <q-card-separator />
            <q-card-main>

              <q-item>
                <q-item-main>
                  <div class=" gutter-y-sm">
                    <div class="row">
                      <q-radio v-model="codmanifestacao" val="210210" label="Ciência da operação" color="orange"/>
                    </div>

                    <div class="row">
                      <q-radio v-model="codmanifestacao" val="210200" label="operação realizada" color="green"/>
                    </div>

                    <div class="row">
                      <q-radio v-model="codmanifestacao" val="210220" label="operação desconhecida" color="red"/>
                    </div>

                    <div class="row">
                      <q-radio v-model="codmanifestacao" val="210240" label="operação não realizada" color="red"/>
                    </div>
                  </div>
                </q-item-main>
              </q-item>
              <q-item-separator/>
              <q-item v-if="codmanifestacao == 210240 || codmanifestacao == 210220">
                <q-item-main>
                  <q-field :count="15">
                    <q-input clearable min-length="15" type="textarea" inverted-light color="warning" v-model="justificativa" float-label="Justificativa"/>
                  </q-field>
                </q-item-main>
              </q-item>

            </q-card-main>
            <q-card-separator/>

            <q-card-actions align="end">
              <q-btn color="red" @click="modalManifestacao = false" icon="arrow_back" round dense />
              <q-btn color="primary" @click="enviarManifestacao()" icon="done" round dense />
            </q-card-actions>

          </q-card>
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
      page: 1,
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
      tabs: 'pendente',
      carregado: false,
      filial: null,
      nsu: null,
      xml: null,
      natop: null,
      currentStep: 'first',
      progresso: 50,
      codmanifestacao: null,
      chaveManifestacao: null,
      filialManifestacao: null,
      justificativa: null,
      modalConsultaSefaz: false,
      modalProgresso: false,
      modalManifestacao: false,
    }
  },
  watch: {
    natop: function(op){
      console.log(op)
    },
    filial: function(query){
      this.ultimaNSU(query)
    },
    filtroSituacao: function(sit){
      console.log(sit)
    },
    filtroManifestacao: function(manifest){
      console.log(manifest)
    },
    filtroChave: function(chave){
      this.page = 1
      this.buscaListagem()
    },
    filtroPessoa: function(pessoa){
      console.log(pessoa)
    },
    filtroFilial: function(filial){
      this.page = 1
      this.buscaListagem()
      // console.log(filial)
    },
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

    enviarManifestacao: function(chave, filial){
      let vm = this

      if (vm.codmanifestacao == 210240 || vm.codmanifestacao == 210220){
        if(vm.justificativa == null || vm.justificativa.length < 15){
          vm.$q.notify({
            message: 'Justificativa deve conter no mínimo 15 caracteres',
            type: 'negative',
          })
          return
        }
      }
      // Monta Parametros da API
      let params = {
        justificativa: vm.justificativa,
        manifestacao: vm.codmanifestacao,
        nfechave: vm.chaveManifestacao,
        filial: vm.filialManifestacao
      }
      vm.$axios.get('nfe-terceiro/manifestacao',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.buscaListagem()
          vm.$q.notify({
            message: 'Manifestacão enviada com sucesso',
            type: 'positive',
          })
        }
      }).catch(function(error) {
        console.log(error)
      })

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
        filial: vm.filtro.filtroFilial,
        pessoa: vm.filtro.filtroPessoa,
        chave: vm.filtro.filtroChave,
        datainicial: vm.filter.datainicial,
        datafinal: vm.filter.datafinal,
        manifestacao: vm.filtro.filtroManifestacao,
        situacao: vm.filtro.filtroSituacao
      }

      vm.$axios.get('nfe-terceiro/lista-dfe',{params}).then(function(request) {
        // Se for para concatenar, senao inicializa
        if (vm.page == 1) {
          vm.xml = request.data
          console.log(params)
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
          vm.nsu = request.data.nsu
          console.log(vm.nsu)
        }).catch(function(error) {
          console.log(error)
        })
      }
    },

    consultarSefaz: function () {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: this.filial
      }

      vm.$axios.get('nfe-terceiro/consulta-sefaz',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
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
