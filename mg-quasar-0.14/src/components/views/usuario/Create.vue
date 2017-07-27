<template>
  <mg-layout>

    <q-btn flat icon="arrow_back" slot="menu" />

    <q-btn flat icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Novo usuário
    </template>

    <div slot="content">
      <form @submit.prevent="create()" style="padding:20px">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <q-field>
              <q-input
              type="text"
              v-model="data.usuario"
              float-label="Usuário"
              />
            </q-field>
            <mg-erros-validacao :erros="erros.usuario"></mg-erros-validacao>
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
  QInput,
  QBtn
} from 'quasar'

import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-create',
  components: {
    MgLayout,
    MgErrosValidacao,
    QField,
    QInput,
    QBtn
  },
  data () {
    return {
      data: {
        usuario: ''
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
              window.axios.post('usuario', vm.data).then(function (request) {
                Toast.create.positive('Registro inserido')
                // vm.$router.push('/usuario/' + request.data.codgrupousuario)
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
