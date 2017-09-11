<template>
  <mg-layout>

    <q-side-link :to="'/usuario/perfil'" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="upload()" />

    <template slot="title">
      Foto
    </template>

    <div slot="content">
      <div class="layout-padding">
        <form refs="form" @submit.prevent="upload()" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-4">
            <q-card>
              <q-card-main>
                <!-- <q-uploader
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
                  @finish="uploaded(data.codusuario)" /> -->
                  <slim-cropper v-model="data.imagem" :options="slimOptions"/>
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
  // Dialog,
  // Toast,
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
    slimOptions: {
      ratio: '1:1',
      // initialImage: require('../assets/test.jpg'),
      service: slimService,
      didInit: slimInit
    },
    upload (data) {
      let vm = this
      console.log(vm.$refs.form)
      let imagem = vm.$refs.form.slim
      console.log(imagem)
    },
    loadData: function (id) {
      let vm = this
      window.axios.get('usuario/' + id).then(function (request) {
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
