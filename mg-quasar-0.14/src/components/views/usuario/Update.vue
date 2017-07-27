<template>
  <mg-layout>

    <q-btn flat icon="arrow_back" slot="menu" />

    <q-btn flat icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">

      <form @submit.prevent="update()" style="padding:20px">
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
  QBtn,
  QField,
  QInput
} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-update',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput
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
    carregaDados: function (id) {
      let vm = this
      window.axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
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
              window.axios.put('usuario/' + vm.data.codusuario, vm.data).then(function (request) {
                Toast.create.positive('Registro inserido')
                vm.$router.push('/usuario/' + request.data.codusuario)
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
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
