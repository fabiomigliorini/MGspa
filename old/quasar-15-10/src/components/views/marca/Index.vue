<template>
  <mg-layout drawer back-path="/">
    <!-- Título da Página -->
    <template slot="title">
      Marcas
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">
      <!-- <q-scroll-area class="fit"> -->
      <q-list no-border>
      <!-- Filtro de Descricao -->
        <q-item>
          <q-item-main>
            <q-field icon="search">
              <q-input v-model="filter.marca" float-label="Descrição" />
            </q-field>
          </q-item-main>
        </q-item>
        <q-list-header>Ordenar Por</q-list-header>
          <!-- Ordena por Vendas -->
        <q-item tag="label">
          <q-item-side icon="trending_up">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Vendas</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.sort" val="abcposicao" />
          </q-item-side>
        </q-item>
        <!-- Ordena Alfabeticamente -->
        <q-item tag="label">
          <q-item-side icon="sort_by_alpha">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Descrição</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.sort" val="marca" />
          </q-item-side>
        </q-item>
        <q-list-header>Estoque</q-list-header>
          <!-- Filtra Estoque Sobrando -->
          <q-item tag="label">
            <q-item-side icon="arrow_upward">
            </q-item-side>
            <q-item-main>
              <q-item-tile title>Sobrando</q-item-tile>
            </q-item-main>
            <q-item-side right>
              <q-toggle v-model="filter.sobrando" />
            </q-item-side>
          </q-item>
          <!-- Filtra Estoque Faltando -->
          <q-item tag="label">
            <q-item-side icon="arrow_downward">
            </q-item-side>
            <q-item-main>
              <q-item-tile title>Faltando</q-item-tile>
            </q-item-main>
            <q-item-side right>
              <q-toggle v-model="filter.faltando" />
            </q-item-side>
          </q-item>

          <q-list-header>Curva ABC</q-list-header>

          <!-- Filtra Pela Classificação da CURVA ABC -->
          <q-item tag="label">
            <q-item-side icon="star">
            </q-item-side>
            <q-item-main>
              <q-range
                v-model="filter.abccategoria"
                label
                markers
                snap
                :min="0"
                :max="4"
                :step="1"
              ></q-range>
            </q-item-main>
          </q-item>

          <q-list-header>Ativos</q-list-header>

          <!-- Filtra Ativos -->
          <q-item tag="label">
            <q-item-side icon="thumb_up">
            </q-item-side>
            <q-item-main>
              <q-item-tile title>Ativos</q-item-tile>
            </q-item-main>
            <q-item-side right>
              <q-radio v-model="filter.inativo" :val='1' />
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

        </q-list>
      <!-- </q-scroll-area> -->
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll :handler="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <q-item :to="'/marca/' + item.codmarca">

              <!-- Imagem -->
              <q-item-side :image="item.imagem.url" v-if="item.imagem" />
              <q-item-side :image="'/statics/semimagem.jpg'" v-else />

              <!-- Coluna 1 -->
              <q-item-main>
                <q-item-tile>
                  {{ item.marca }}
                  <q-chip tag square pointing="left" color="negative" v-if="item.inativo">Inativo</q-chip>
                </q-item-tile>
                <q-item-tile sublabel>
                  #{{ numeral(item.codmarca).format('00000000') }}
                </q-item-tile>
              </q-item-main>

              <!-- Coluna 2 -->
              <q-item-main class="col-sm-2 gt-sm">
                <q-item-tile sublabel>
                  <span v-if="item.itensabaixominimo > 0">
                    {{ numeral(item.itensabaixominimo).format('0,0') }} <q-icon name="arrow_downward" />
                  </span>
                  <span v-if="item.itensacimamaximo > 0">
                    <q-icon name="arrow_upward" /> {{ numeral(item.itensacimamaximo).format('0,0') }}
                  </span>
                </q-item-tile>
                <q-item-tile sublabel>
                  <q-icon name="date_range" />
                  {{ item.estoqueminimodias }} à
                  {{ item.estoquemaximodias }} Dias
                </q-item-tile>
              </q-item-main>

              <!-- Coluna 3 -->
              <!-- <q-item-main class="col-sm-2 gt-xs">
                <q-item-tile sublabel>
                  <template v-if="item.dataultimacompra" class="text-grey">
                    <q-icon name="add_shopping_cart" />
                    {{ moment(item.dataultimacompra).fromNow() }}
                  </template>
                </q-item-tile>
              </q-item-main> -->

              <!-- Direita (Estrelas) -->
              <q-item-side class="col-xs-1" right>
                <q-item-tile v-if="!item.abcignorar">
                  <q-rating readonly v-model="item.abccategoria" :max="3" size="1.7rem" />
                </q-item-tile>
                <q-item-tile sublabel >
                  {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%
                  <template v-if="item.abcposicao">
                    ({{ numeral(item.abcposicao).format('0,0') }}&deg;)
                  </template>
                </q-item-tile>
              </q-item-side>

            </q-item>
            <q-item-separator />

          </template>
        </q-infinite-scroll>
      </q-list>

      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>
      <router-link :to="{ path: '/marca/create' }">
        <q-page-sticky corner="bottom-right" :offset="[18, 18]">
          <q-btn round color="primary">
            <q-icon name="add" />
          </q-btn>
        </q-page-sticky>
      </router-link>

    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import { debounce } from 'quasar'

export default {

  components: {
    MgLayout,
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
    this.filter = this.$store.state.filtroMarca
  }

}
</script>

<style>
</style>
