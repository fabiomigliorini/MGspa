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

      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="edit"
          active-icon="edit"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/marca/' + item.codmarca + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="orange" @click.native="activate()" icon="thumb_up" v-if="item.inativo">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Ativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" @click.native="inactivate()" icon="thumb_down" v-else>
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Inativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-fixed-position>

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
import { QIcon, QCard, QCardMedia, QCardTitle, QRating, debounce, QBtn, QFixedPosition, QFab, QFabAction, QTooltip } from 'quasar'

export default {

  components: {
    MgLayout,
    MgAutor,
    QIcon,
    QCard,
    QCardMedia,
    QCardTitle,
    QRating,
    QBtn,
    QFixedPosition,
    QFabAction,
    QFab,
    QTooltip
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
