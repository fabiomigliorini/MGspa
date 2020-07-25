<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      <span class="gt-xs">
        Distribuição de
      </span>
      DFe's
      <span class="gt-xs">
       (Documentos Fiscais Eletrônicos)
      </span>
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">

      <q-item-label header>Chave</q-item-label>

      <!-- Filtro Chave -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.nfechave" label="Chave" >
            <template v-slot:prepend>
              <q-icon name="fas fa-barcode" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <!-- Filtro codfilial -->
      <q-item>
        <q-item-section>
          <mg-select-filial label="Filial" v-model="filter.codfilial" filtro-dfe/>
        </q-item-section>
      </q-item>

      <q-item-label header>Período</q-item-label>
      <!-- Filtro emissaode -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.datade" label="De" type="date" stack-label :max="filter.dataate">
            <template v-slot:prepend>
              <q-icon name="date_range" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <!-- Filtro emissaoate -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.dataate" label="Até" type="date" stack-label :min="filter.datade">
            <template v-slot:prepend>
              <q-icon name="date_range" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item-label header>NSU (Número Serial Único)</q-item-label>

      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.nsude" label="De" type="number" :max="filter.nsuate">
            <template v-slot:prepend>
              <q-icon name="dialpad" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.nsuate" label="Até" type="number" :min="filter.nsude">
            <template v-slot:prepend>
              <q-icon name="dialpad" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll @load="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <q-item clickable v-ripple @click="abrirDistribuicaoDfe(item)">

              <q-item-section top avatar>
                <q-avatar color="teal" text-color="white" :icon="iconeDfeTipo(item.DfeTipo.schemaxml)" />
              </q-item-section>

              <q-item-section top class="col-sm-2 gt-xs">
                <q-item-label class="text-weight-medium">
                  {{item.Filial.filial}}
                </q-item-label>
                <q-item-label caption>
                    {{ item.DfeTipo.schemaxml }}
                </q-item-label>
              </q-item-section>

              <q-item-section top>

                <!-- NotaFiscalTerceiro -->
                <template v-if="item.codnotafiscalterceiro > 0">
                  <q-item-label lines="1">
                    <span class="text-weight-medium" v-if="item.NotaFiscalTerceiro.codpessoa">
                      {{item.NotaFiscalTerceiro.Pessoa.fantasia}}
                    </span>
                    <span class="text-weight-medium" v-else>
                      {{item.NotaFiscalTerceiro.emitente}}
                    </span>
                  </q-item-label>
                  <q-item-label lines="1">
                    <span class="text-grey-8 text-weight-medium" v-if="item.NotaFiscalTerceiro.valortotal">
                      R$ {{numeral(item.NotaFiscalTerceiro.valortotal).format('0,0.00')}}
                    </span>
                    <span class="text-grey-8" v-if="item.NotaFiscalTerceiro.natop">
                      - {{item.NotaFiscalTerceiro.natop}}
                    </span>
                  </q-item-label>
                </template>

                <!-- DistribuicaoDfeEventoS -->
                <template v-if="item.coddistribuicaodfeevento > 0">
                  <q-item-label lines="1">
                    <span class="text-weight-medium">
                      {{item.DistribuicaoDfeEvento.orgao}}
                    </span>
                    <span class="text-weight-medium">
                      {{formataCnpj(item.DistribuicaoDfeEvento.cnpj)}}
                      {{formataCpf(item.DistribuicaoDfeEvento.cpf)}}
                    </span>
                  </q-item-label>
                  <q-item-label lines="1" v-if="item.DistribuicaoDfeEvento.coddistribuicaodfeevento > 0">
                    <span class="text-grey-8">
                       {{item.DistribuicaoDfeEvento.DfeEvento.dfeevento}}
                       ({{item.DistribuicaoDfeEvento.DfeEvento.tpevento}})
                    </span>
                    <span class="text-grey-8 text-weight-medium" v-if="item.NotaFiscalTerceiro.valortotal">
                      {{numeral(item.NotaFiscalTerceiro.valortotal).format('0,0.00')}}
                    </span>
                  </q-item-label>
                </template>

                <!-- chave -->
                <q-item-label caption lines="1">
                  {{formataNfeChave(item.nfechave)}}
                </q-item-label>
              </q-item-section>

              <!-- data e NSU -->
              <q-item-section top side class="gt-xs">
                <q-chip size="sm" square icon="dialpad">
                  {{ numeral(item.nsu).format('0,0') }}
                </q-chip>
                <abbr :title="moment(item.data).format('LLL')">
                  {{ moment(item.data).fromNow() }}
                </abbr>
              </q-item-section>

            </q-item>
            <q-separator inset="item" />
          </template>
        </q-infinite-scroll>
      </q-list>

      <!-- Se não tiver registros -->
      <!-- <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data> -->



      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="search" color="primary" @click="openSefazDialog()"/>
      </q-page-sticky>

      <q-dialog v-model="sefazDialog">
        <q-card class="column">
          <q-card-section>
            <div class="text-h6">Pesquisar DFe na Sefaz por mais DFe's</div>
          </q-card-section>

          <q-card-section class="col q-pt-none">
            <q-list bordered class="rounded-borders" style="min-width: 350px">
              <template v-for="filial in filiaisHabilitadas">
                <!-- <q-item-label header>{{filial.filial}}</q-item-label> -->
                <q-item v-ripple clickable @click="filial.selecionada = !filial.selecionada">
                  <q-item-section side>
                    <q-checkbox v-model="filial.selecionada" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      {{filial.filial}}
                      <small v-if="filial.nsu" class="text-grey-7">
                        ({{filial.nsu}}<template v-if="filial.nsufinal">
                          / {{filial.nsufinal}}
                        </template>)
                      </small>
                    </q-item-label>
                    <q-item-label caption>
                      <q-linear-progress
                        stripe rounded
                        size="10px"
                        :value="filial.percentual"
                        :indeterminate="filial.percentualIndeterminado"
                        />
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator />
              </template>
            </q-list>
          </q-card-section>

          <q-card-actions align="right" class="bg-white">
            <q-btn flat label="CANCELAR" color="grey" v-close-popup />
            <q-btn
              flat
              label="PESQUISAR"
              icon="search"
              color="primary"
              :disabled="filiaisSelecionadas.length == 0"
              @click="pesquisarSefazSelecionadas()"/>
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>


  </mg-layout>
</template>

<script>

import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectFilial from '../../utils/select/MgSelectFilial'
import MgSelectIndManifestacao from '../../utils/select/MgSelectIndManifestacao'
import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import { debounce } from 'quasar'

export default {

  components: {
    MgLayout,
    MgAutocompletePessoa,
    MgSelectFilial,
    MgSelectIndManifestacao,
    MgNoData
  },

  data () {
    return {
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      xml: null,
      sefazDialog: false,
      filiaisHabilitadas: [],

      progress2: 0.5,

    }
  },

  computed: {
    filiaisSelecionadas: function () {
      return this.filiaisHabilitadas.filter(function (filial) {
        return filial.selecionada
      });
    }
  },

  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    }

  },

  methods: {

    pesquisarSefazSelecionadas: function () {
      let vm = this;
      this.filiaisSelecionadas.forEach(function (filial) {
        // if (filial.codfilial != 201) {
        //   return;
        // }
        vm.pesquisarSefazFilial(filial);
      })
    },

    pesquisarSefazFilial: function (filial) {

      // inicializa variaveis
      var vm = this

      // Mostra Percentual indeterminado
      if (!filial.nsufinal) {
        filial.percentualIndeterminado = true;
      }

      // monta URL pesquisa
      var url = 'nfe-php/dist-dfe/' + filial.codfilial;
      if (filial.nsu) {
        url = url + '/' + filial.nsu;
      }

      // faz chamada api
      vm.$axios.post(url).then(response => {
        if (!filial.nsuinicial) {
          filial.nsuinicial = filial.nsu;
        }
        filial.nsufinal = parseInt(response.data.maxNSU);
        filial.nsu = parseInt(response.data.ultNSU);
        let pesquisados = filial.nsu - filial.nsuinicial;
        let total = filial.nsufinal - filial.nsuinicial;
        filial.percentualIndeterminado = false;
        if (total == 0) {
          filial.percentual = 1;
        } else {
          filial.percentual = pesquisados / total;
        }
        if (filial.percentual != 1 && vm.sefazDialog) {
          vm.pesquisarSefazFilial (filial);
        }
      })

    },

    openSefazDialog: function () {
      this.sefazDialog = true;
      if (this.filiaisHabilitadas.length == 0) {
        this.loadFiliaisHabilitadas();
      }
    },

    // carrega registros da api
    loadFiliaisHabilitadas: debounce(function () {

      // inicializa variaveis
      var vm = this

      // faz chamada api
      vm.$axios.get('dfe/filiais-habilitadas').then(response => {
        vm.filiaisHabilitadas = response.data.map(function (filial){
          filial.selecionada = true;
          filial.percentual = 0;
          filial.percentualIndeterminado = false;
          filial.nsuinicial = null;
          filial.nsufinal = null;
          return filial
        })
      })
    }, 500),

    abrirDistribuicaoDfe (item) {
      this.carregarXml(item.coddistribuicaodfe)
    },

    // carrega xml Distribuicao Dfe
    carregarXml: debounce(function (coddistribuicaodfe) {
      // inicializa variaveis
      var vm = this
      // faz chamada api
      vm.$axios.get('dfe/distribuicao/' + coddistribuicaodfe + '/xml').then(response => {
        this.xml = response.data;
        let blob = new Blob([response.data], {type: 'text/xml'});
        let url = URL.createObjectURL(blob);
        window.open(url);
        URL.revokeObjectURL(url, '_self');
      })
    }, 500),

    formataCpf(cpf) {
      if (cpf == null) {
        return cpf;
      }
      cpf = String(cpf).padStart(11, '0').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
      return cpf;
    },

    formataCnpj(cnpj) {
      if (cnpj == null) {
        return cnpj;
      }
      cnpj = String(cnpj).padStart(14, '0').replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
      return cnpj;
    },

    formataNfeChave(nfechave) {
      if (nfechave == null) {
        return nfechave;
      }
      nfechave = String(nfechave)
        .padStart(44, '0')
        .replace(/(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})(\d{4})/, "$1 $2 $3 $4 $5 $6 $7 $8 $9 $10 $11");
      return nfechave;
    },

    iconeDfeTipo (schemaxml) {
      switch (schemaxml) {
        case 'procNFe_v4.00.xsd':
          return 'fas fa-file-code';
          break;
        case 'procEventoNFe_v1.00.xsd':
          return 'comment';
          break;
        case 'resEvento_v1.01.xsd':
          return 'comment';
          break;
        case 'resNFe_v1.01.xsd':
          return 'add';
          break;
        default:
          return 'info';
          break;
      }
    },

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {

      // salva no Vuex filtro da marca
      this.$store.commit('filtroDfeDistribuicao/updateFiltroDfeDistribuicao', this.filter)

      // inicializa variaveis
      var vm = this
      var params = this.filter
      params.page = this.page

      // faz chamada api
      vm.$axios.get('dfe/distribuicao', {
        params
      }).then(response => {
        // Se for para concatenar, senao inicializa
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (response.data.data.length === 0) {
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
      })
    }, 500)

  },

  // na criacao, busca filtro do Vuex
  created () {
    this.filter = this.$store.state.filtroDfeDistribuicao
    this.loadFiliaisHabilitadas();

  }

}
</script>

<style>
</style>
