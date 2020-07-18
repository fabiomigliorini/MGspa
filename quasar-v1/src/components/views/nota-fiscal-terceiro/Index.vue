<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Nota Fiscal terceiro
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

      <!-- Filtro codfillial -->
      <q-item>
        <q-item-section>
          <mg-select-filial label="Filial" v-model="filter.codfilial"/>
        </q-item-section>
      </q-item>

      <!-- Filtro codpessoa -->
      <q-item>
        <q-item-section>
          <mg-autocomplete-pessoa label="Pessoa" v-model="filter.codpessoa"/>
        </q-item-section>
      </q-item>

      <q-item-label header>Emissão</q-item-label>
      <!-- Filtro emissaode -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.emissaode" label="Emitida De" type="date" stack-label :max="filter.emissaoate">
            <template v-slot:prepend>
              <q-icon name="date_range" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <!-- Filtro emissaoate -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.emissaoate" label="Emitida Até" type="date" stack-label :min="filter.emissaode">
            <template v-slot:prepend>
              <q-icon name="date_range" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item-label header>Situação</q-item-label>

      <!-- Filtro indsituacao -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.indsituacao" label="Situação" >
            <template v-slot:prepend>
              <q-icon name="tag" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <!-- Filtro indmanifestacao -->
      <q-item>
        <q-item-section>
          <mg-select-ind-manifestacao v-model="filter.indmanifestacao" label="Manifestação" />
        </q-item-section>
      </q-item>

<pre>{{filter}}</pre>


    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <q-tabs v-model="filter.status" dense class="bg-primary text-white" indicator-color="positive">
        <q-tab name="novas" icon="drafts" label="Novas" default/>
        <q-tab name="revisadas" icon="check" label="Revisadas"/>
        <q-tab name="arquivadas" icon="fas fa-archive" label="Arquivadas"/>
        <q-tab name="todas" icon="all_inbox" label="Todas"/>
      </q-tabs>


      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll @load="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <q-item :to="'/marca/' + item.codmarca" >

              <!-- Imagem -->
              <q-item-section avatar>
                <q-avatar square>
                  <img :src="item.imagem.url" v-if="item.imagem">
                  <img src="/statics/no-image-4-4.svg" v-else/>
                </q-avatar>
              </q-item-section>

              </q-item-section>

              <!-- Coluna 1 -->
              <q-item-section >
                <q-item-label>
                  {{ item.marca }}
                  <q-chip tag square pointing="left" color="negative" v-if="item.inativo">Inativo</q-chip>
                </q-item-label>
                <q-item-label caption>
                  #{{ numeral(item.codmarca).format('00000000') }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-xs">
                <q-item-label class="row" caption>
                  <div class="col-6">
                    <template v-if="item.itensabaixominimo > 0">
                      {{ numeral(item.itensabaixominimo).format('0,0') }} <q-icon name="arrow_downward" />
                    </template>
                    <template v-if="item.itensacimamaximo > 0">
                      <q-icon name="arrow_upward" /> {{ numeral(item.itensacimamaximo).format('0,0') }}
                    </template>
                  </div>
                  <div class="col-6 text-center">
                    <template v-if="item.dataultimacompra" class="text-grey">
                      <q-icon name="add_shopping_cart" />
                      {{ moment(item.dataultimacompra).fromNow() }}
                    </template>
                  </div>
                </q-item-label>

                <q-item-label caption >
                  <q-icon name="date_range" />
                  {{ item.estoqueminimodias }} à
                  {{ item.estoquemaximodias }} Dias
                </q-item-label>
              </q-item-section>

              <!-- Direita (Estrelas) -->
              <q-item-section avatar>
                <q-item-label v-if="!item.abcignorar">
                  <q-rating readonly v-model="item.abccategoria" :max="3" size="1.7rem" />
                </q-item-label>
                <q-item-label caption>
                  {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%
                  <template v-if="item.abcposicao">
                    ({{ numeral(item.abcposicao).format('0,0') }}&deg;)
                  </template>
                </q-item-label>
              </q-item-section>

            </q-item>
            <q-separator />

          </template>
        </q-infinite-scroll>
      </q-list>

      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="add" color="primary" :to="{ path: '/marca/create' }"/>
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

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {
      // salva no Vuex filtro da marca
      this.$store.commit('filtroMarca/updateFiltroMarca', this.filter)

      // inicializa variaveis
      var vm = this
      var params = this.filter
      params.page = this.page
      console.log(this.page)
      this.loading = true

      // faz chamada api
      vm.$axios.get('marca', {
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
    this.filter = this.$store.state.filtroNotaFiscalTerceiro
  }

}
</script>

<style>
</style>
