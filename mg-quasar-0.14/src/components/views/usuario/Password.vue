<template>
  <mg-layout>

    <q-side-link :to="'/usuario/perfil'" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      Alterar senha
    </template>

    <div slot="content">
      <div class="layout-padding">

        <div class="row">
          <div class="col-md-4">
            <q-card>
              <q-card-main>
                <form @submit.prevent="update()">
                  <q-input
                    type="password"
                    v-model="data.senha"
                    float-label="Senha antiga"
                  />

                  <q-input
                    type="password"
                    v-model="data.senha"
                    float-label="Nova senha"
                  />
                  <mg-erros-validacao :erros="erros.senha"></mg-erros-validacao>

                  <q-input
                    type="password"
                    v-model="data.senha"
                    float-label="Confirmar senha"
                  />

                </form>
              </q-card-main>
            </q-card>
          </div>
        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
  Dialog,
  Toast,
  QBtn,
  QField,
  QInput,
  QSelect,
  QSideLink,
  QCard,
  QCardMain,
  QCardTitle
} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput,
    QSelect,
    QSideLink,
    QCard,
    QCardMain,
    QCardTitle
  },
  data () {
    return {
      data: {},
      erros: false
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      window.axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
        vm.data.senha_antiga = request.data.senha
        vm.data.senha = ''
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
                Toast.create.positive('Registro atualizado')
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
  created () {
    this.loadData(localStorage.getItem('auth.codusuario'))
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
