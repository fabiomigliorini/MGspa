<template>
  <mg-layout>

    <q-side-link :to="'/usuario/' + data.codusuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">
        <div class="row">
          <div class="col-md-4">
            <form @submit.prevent="update()">
              <q-input
                type="password"
                v-model="data.senha"
                float-label="Nova senha"
              />
              <mg-erros-validacao :erros="erros.senha"></mg-erros-validacao>

              <q-select
                style="width:100%"
                float-label="Impressora Matricial"
                v-model="data.impressoramatricial"
                :options="impressoras"
              />

              <q-select
                style="width:100%"
                float-label="Impressora Térmica"
                v-model="data.impressoratermica"
                :options="impressoras"
              />

            </form>
          </div>
          <div class="col-md-4">
            <q-card>
              <!-- <q-card-title>
                Foto
              </q-card-title> -->
              <q-card-main>
                <q-card-media v-if="data.imagem">
                  <img :src="data.imagem">
                </q-card-media>
                <q-uploader
                  v-if="data.imagem"
                  :url=" endpoint + 'imagem/' + data.codimagem + '?_method=PUT&codusuario=' + data.codusuario"
                  :headers= "headers"
                  stack-label="ALTERAR IMAGEM"
                  :multiple="false"
                  hide-upload-progress
                  @finish="uploaded(data.codusuario)" />

                <q-uploader
                  v-else
                  :url=" endpoint + 'imagem?codusuario=' + data.codusuario"
                  :headers= "headers"
                  stack-label="CADASTRAR IMAGEM"
                  :multiple="false"
                  @finish="uploaded(data.codusuario)" />
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
  QUploader,
  QCardMedia,
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
    QUploader,
    QCardMedia,
    QCard,
    QCardMain,
    QCardTitle
  },
  data () {
    return {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Authorization: `Bearer ${localStorage.getItem('auth.token')}`
      },
      endpoint: process.env.API_BASE_URL,
      data: {},
      impressoras: [],
      erros: false
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.data = request.data
        vm.data.senha = ''
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaImpressoras: function () {
      let vm = this
      window.axios.get('usuario/impressoras').then(function (request) {
        vm.impressoras = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    uploaded (codusuario) {
      this.carregaDados(codusuario)
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
    this.carregaDados(localStorage.getItem('auth.codusuario'))
    this.carregaImpressoras()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
