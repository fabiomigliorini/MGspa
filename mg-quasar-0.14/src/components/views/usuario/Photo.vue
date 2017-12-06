<template>
  <mg-layout>

    <q-side-link :to="'/usuario/perfil'" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="upload()" />

    <template slot="title">
      <span v-if="data.codimagem">Alterar </span>
      <span v-else="data.codimagem">Cadastrar </span>
      foto
    </template>

    <div slot="content">
      <div class="layout-padding">

        <form @submit.prevent="upload()" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-4">
              <q-card>
                <q-card-main>
                    <slim-cropper :options="slimOptions"/>
                </q-card-main>
              </q-card>
            </div>
          </div>
        </form>
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
import Slim from '../../utils/slim/slim.vue'
// called when slim has initialized
function slimInit (data, slim) {
  // slim instance reference
  console.log(slim)

  // current slim data object and slim reference
  console.log(data)
}

// called when upload button is pressed or automatically if push is enabled
function slimService (formdata, progress, success, failure, slim) {
  // slim instance reference
  console.log(slim)

  // form data to post to server
  console.log(formdata)

  // call these methods to handle upload state
  console.log(progress, success, failure)
}

export default {
  name: 'usuario-photo',
  components: {
    MgLayout,
    'slim-cropper': Slim,
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
      data: {},
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Authorization: `Bearer ${localStorage.getItem('auth.token')}`
      },
      endpoint: process.env.API_BASE_URL + 'imagem?codusuario=',
      erros: false
    }
  },
  methods: {
    slimOptions: {
      ratio: '1:1',
      // initialImage: '',
      // initialImage: `http://127.0.0.1:8000/imagens/13462.jpg`,
      service: slimService,
      didInit: slimInit
    },
    upload () {
      let vm = this
      let form = document.forms[0]
      let input = form.querySelector('input[name="slim[]"]')
      let imagem = input.value
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
              let data = {
                codusuario: vm.data.codusuario,
                'slim[]': imagem
              }

              if (vm.data.imagem) {
                console.log('ALTERANDO...')
                window.axios.put('imagem/' + vm.data.codimagem, data).then(function (request) {
                  Toast.create.positive('Sua foto foi alterada')
                  // vm.$router.push('/usuario/foto')
                }).catch(function (error) {
                  vm.erros = error.response.data.erros
                })
              }
              else {
                console.log('CADASTRANDO...')
                window.axios.post('imagem', data).then(function (request) {
                  Toast.create.positive('Sua foto foi cadastrada')
                  // vm.$router.push('/usuario/foto')
                }).catch(function (error) {
                  vm.erros = error.response.data.erros
                })
              }
            }
          }
        ]
      })
    },
    loadData: function (id) {
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
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
