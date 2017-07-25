<template>
  <mg-layout>

    <router-link :to="{ path: '/grupo-usuario/create' }" slot="menu">
      <q-btn round icon="arrow_back"/>
    </router-link>

    <button slot="rightMenu" @click.prevent="create()">
      <i>done</i>
    </button>

    <template slot="title">
      Novo Grupos de usuário
    </template>

    <div slot="content">

      <form @submit.prevent="create()">
        <q-field
           :count="10"
           helper="Some helper"
           :error="error"
           error-label="Oops, we got an error."
         >
           <q-input v-model="text" />
         </q-field>


        <div class="item">
          <div class="item-content row">
            <div class="width-1of3">
              <div class="floating-label">
                <input required class="full-width" v-model="dados.grupousuario"  v-bind:class="{ 'has-error': erros.grupousuario }">
                <label>Descrição</label>
              </div>
              <mg-erros-validacao :erros="erros.grupousuario"></mg-erros-validacao>
            </div>
          </div>
        </div>
      </form>

    </div>

  </mg-layout>
</template>

<script>
import {
  Dialog,
  Toast,
  QField,
  QInput
} from 'quasar'

import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'grupo-usuario-create',
  components: {
    MgLayout,
    MgErrosValidacao,
    QField,
    QInput
  },
  data () {
    return {
      dados: {
        grupousuario: ''
      },
      erros: false
    }
  },
  methods: {
    create: function () {
      var vm = this
      Dialog.create({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        buttons: [
          {
            label: 'Cancelar',
            handler () {}
          },
          {
            label: 'Salvar',
            handler () {
              window.axios.post('grupo-usuario', vm.dados).then(function (request) {
                Toast.create.positive('Registro inserido')
                vm.$router.push('/grupo-usuario/' + request.data.codgrupousuario)
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    }
  },
  mounted () {
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
