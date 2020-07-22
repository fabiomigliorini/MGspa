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
          <mg-select-filial label="Filial" v-model="filter.codfilial"/>
          <q-input outlined v-model="filter.codfilial" label="De" type="numeric" />
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

      <pre>{{filter}}</pre>

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
            <q-item clickable v-ripple>

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
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="search" color="primary"/>
      </q-page-sticky>

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
      loading: true
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
      this.loading = true

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

        // desmarca flag de carregando
        this.loading = false

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
  }

}
</script>

<style>
</style>
