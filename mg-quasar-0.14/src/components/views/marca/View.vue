<template>
  <mg-layout back-path="/marca">

    <!-- Título da Página -->
    <template slot="title">
      Marca: {{ item.marca }}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <ul class="breadcrumb">
        <li>
          <router-link :to="{ path: '/' }">
            <q-icon name="home"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca' }">
            <q-icon name="label_outline"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca/' + this.id }">
            {{ item.marca }}
          </router-link>
        </li>
      </ul>

      <div class="row">
        <q-card inline class="col-md-2">

          <!-- Imagem -->
          <q-card-media v-if="item.imagem">
            <img :src="item.imagem.url">
          </q-card-media>

          <!-- Titulo -->
          <q-card-title>
            {{ item.marca }}

            <!-- Estrelas -->
            <q-rating readonly slot="subtitle" v-model="item.abccategoria" :max="3" v-if="!item.abcignorar" />

            <!-- Posição ABC -->
            <span slot="right" class="row items-center">
              <template v-if="item.abcposicao">
                {{ numeral(item.abcposicao).format('0,0') }}&deg; lugar,
                <br />
              </template>
              {{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}% <br />
              das vendas!
            </span>

          </q-card-title>

        </q-card>


      </div>
    </div>

    <div slot="footer">
      <mg-autor
        :data="item"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import { QIcon, QCard, QCardMedia, QCardTitle, QRating, debounce } from 'quasar'

export default {

  components: {
    MgLayout,
    MgAutor,
    QIcon,
    QCard,
    QCardMedia,
    QCardTitle,
    QRating
  },

  data () {
    return {
      item: {},
      id: null
    }
  },

  methods: {

    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      var vm = this
      var params = {}
      this.loading = true

      // faz chamada api
      window.axios.get('marca/' + this.id, { params }).then(response => {
        vm.item = response.data
        // desmarca flag de carregando
        this.loading = false
      })
    }, 500)

  },

  // na criacao, busca filtro do Vuex
  created () {
    this.id = this.$route.params.id
    this.loadData()
  }

}
</script>

<style>
</style>
