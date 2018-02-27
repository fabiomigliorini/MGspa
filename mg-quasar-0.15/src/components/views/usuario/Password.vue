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
                    v-model="data.senha_antiga"
                    float-label="Senha antiga"
                  />
                  <mg-erros-validacao :erros="erros.senha_antiga"></mg-erros-validacao>

                  <q-input
                    type="password"
                    v-model="data.senha"
                    float-label="Nova senha"
                  />
                  <mg-erros-validacao :erros="erros.senha"></mg-erros-validacao>

                  <q-input
                    type="password"
                    v-model="data.senha_confirmacao"
                    float-label="Confirmar senha"
                  />
                  <mg-erros-validacao :erros="erros.senha_confirmacao"></mg-erros-validacao>

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
  
  QBtn,
  QField,
  QInput,
  QSelect,
  
  QCard,
  QCardMain,
  QCardTitle
} from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
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
    
    QCard,
    QCardMain,
    QCardTitle
  },
  data () {
    return {
      data: {
        senha: null,
        senha_antiga: null,
        senha_confirmacao: null,
        usuario: null
      },
      erros: false
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      let params = {
        fields: ['usuario', 'impressoratermica', 'impressoramatricial']
      }
      window.axios.get('usuario/' + id, { params }).then(function (request) {
        vm.data = request.data
        vm.data.senha = null
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
              window.axios.put('usuario/' + localStorage.getItem('auth.usuario.codusuario'), vm.data).then(function (request) {
                Notify.create.positive('Registro atualizado')
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
    this.loadData(localStorage.getItem('auth.usuario.codusuario'))
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
