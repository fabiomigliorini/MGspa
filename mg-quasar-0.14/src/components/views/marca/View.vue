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

      <q-card v-if="item.inativo">
        <q-card-main>
          <span class="text-red">
            Inativo desde {{ moment(item.inativo).format('L') }}
          </span>
        </q-card-main>
      </q-card>

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
import { QIcon, QCard, QCardMedia, QCardTitle, QRating, debounce, QBtn, QFixedPosition, QFab, QFabAction, QTooltip, Dialog, Toast, QCardMain } from 'quasar'

export default {

  components: {
    MgLayout,
    MgAutor,
    QIcon,
    QCard,
    QCardMedia,
    QCardTitle,
    QCardMain,
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
    }, 500),
    activate: function () {
      let vm = this
      Dialog.create({
        title: 'Ativar',
        message: 'Tem certeza de deseja ativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Ativar',
            handler () {
              window.axios.delete('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
                vm.loadData(vm.item.codmarca)
                Toast.create.positive('Registro ativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },
    inactivate: function () {
      let vm = this
      Dialog.create({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Inativar',
            handler () {
              window.axios.post('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
                vm.loadData(vm.item.codusuario)
                Toast.create.positive('Registro inativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },
    destroy: function () {
      let vm = this
      Dialog.create({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        buttons: [
          'Cancelar',
          {
            label: 'Excluir',
            handler () {
              window.axios.delete('marca/' + vm.item.codmarca).then(function (request) {
                vm.$router.push('/marca')
                Toast.create.positive('Registro excluído')
              }).catch(function (error) {
                console.log(error)
              })
            }
          }
        ]
      })
    }
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
