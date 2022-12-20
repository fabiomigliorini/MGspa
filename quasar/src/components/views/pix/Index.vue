<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/">
    <!-- Título da Página -->
    <template slot="title">
      Pix
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">

      <!-- Filtro de Descricao -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.pix" label="Descrição" >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item-label header>Ordenar Por</q-item-label>
      <q-separator/>

      <!-- Ordena por Vendas -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="trending_up" />
        </q-item-section>
        <q-item-section>Vendas</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.sort" val="abcposicao" />
        </q-item-section>
      </q-item>

      <!-- Ordena Alfabeticamente -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="sort_by_alpha" />
        </q-item-section>
        <q-item-section>Descrição</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.sort" val="pix" />
        </q-item-section>
      </q-item>

      <q-item-label header>Estoque</q-item-label>
      <q-separator/>

      <!-- Filtra Estoque Sobrando -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="arrow_upward" />
        </q-item-section>
        <q-item-section>Sobrando</q-item-section>
        <q-item-section side>
          <q-toggle v-model="filter.sobrando" />
        </q-item-section>
      </q-item>

      <!-- Filtra Estoque Faltando -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="arrow_downward" />
        </q-item-section>
        <q-item-section>Faltando</q-item-section>
        <q-item-section side>
          <q-toggle v-model="filter.faltando" />
        </q-item-section>
      </q-item>

      <q-item-label header>Curva ABC</q-item-label>
      <q-separator/>

      <!-- Filtra Pela Classificação da CURVA ABC -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="star" />
        </q-item-section>
        <q-item-section>
          <q-range v-model="filter.abccategoria" label markers snap :min="0" :max="3" :step="1"/>
        </q-item-section>
      </q-item>

      <q-item-label header>Ativos</q-item-label>
      <q-separator/>

      <!-- Filtra Ativos -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumb_up" />
        </q-item-section>
        <q-item-section>Ativos</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val='1' />
        </q-item-section>
      </q-item>

      <!-- Filtra Inativos -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumb_down" />
        </q-item-section>
        <q-item-section>Inativos</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val="2" />
        </q-item-section>
      </q-item>

      <!-- Filtra Ativos e Inativos -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="thumbs_up_down" />
        </q-item-section>
        <q-item-section>Ativos e Inativos</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.inativo" :val="9" />
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
            <!-- <q-item :to="'/pix/' + item.codpix" > -->
            <q-item  >

              <!-- Imagem -->
              <q-item-section avatar>
                <!-- <q-avatar icon="hourglass_empty" color="red"  text-color="white" v-if="(item.codpix == null)" /> -->
                <q-avatar icon="hourglass_empty" color="red"  text-color="white" v-if="(item.codpix == null)" />
                <q-avatar icon="done" text-color="white" color="green" v-else />
              </q-item-section>

              </q-item-section>

              <!-- Coluna 1 -->
              <q-item-section >
                <q-item-label>
                  {{ item.nome }}
                </q-item-label>
                <q-item-label caption>
                  {{ item.portador }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-xs">
                <q-item-label class="row" caption>
                  <div class="col-6">
                  </div>
                </q-item-label>

                <q-item-label caption>

                  <!-- <q-icon name="bookmark" /> -->
                  <!-- PIX #{{ numeral(item.codpix).format('00000000') }} -->

                  <!-- <q-icon name="bookmark" /> -->
                  <!-- COB #{{ numeral(item.codpixcob).format('00000000') }} -->

                  <template v-if="item.codnegocio">
                    <q-icon name="bookmark" />
                    Negócio #{{ numeral(item.codnegocio).format('00000000') }}
                  </template>
                </q-item-label>
              </q-item-section>

              <!-- Direita (Estrelas) -->
              <q-item-section avatar>
                <q-item-label>
                  <small class="text-grey">R$</small> {{ numeral(item.valor).format('0,0.00') }}
                </q-item-label>
                <q-item-label caption>
                  <abbr :title="moment(item.horario).format('LLL')">
                    {{ moment(item.horario).fromNow() }}
                  </abbr>
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
        <q-btn fab icon="refresh" color="primary" @click="refresh()" :loading="consultando" />
      </q-page-sticky>

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
      loading: true,
      consultando: false
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

    refresh: debounce(function () {
      // inicializa variaveis
      var vm = this

      // monta URL pesquisa
      var url = 'pix/consultar'
      vm.consultando = true

      // faz chamada api
      vm.$axios.post(url).then(response => {
        vm.consultando = false
        vm.page = 1
        vm.loadData(false, vm.done)
        console.log(response)
      })
    }, 500),

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {
      // salva no Vuex filtro da pix
      this.$store.commit('filtroPix/updateFiltroPix', this.filter)

      // inicializa variaveis
      var vm = this
      var params = this.filter
      params.page = this.page
      console.log(this.page)
      this.loading = true

      // faz chamada api
      vm.$axios.get('pix', {
        params
      }).then(response => {
        // Se for para concatenar, senao inicializa
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }
        // console.log(vm.data);

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (response.data.data.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // despix flag de carregando
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
    this.filter = this.$store.state.filtroPix
  }

}
</script>

<style>
</style>
