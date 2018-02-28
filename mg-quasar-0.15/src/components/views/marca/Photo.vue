<template>
  <mg-layout>

    <q-btn round slot="menu" @click="$router.push('/marca')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="upload()" />

    <template slot="title">
      Foto
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
  QBtn,
  QField,
  QInput,
  QSelect,

  QUploader,
  QCardMedia,
  QCard,
  QCardMain,
  QCardTitle
} from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
import Slim from '../../utils/slim/slim.vue'

function slimInit (data, slim) {
  console.log(slim)
  console.log(data)
}
function slimService (formdata, progress, success, failure, slim) {
  console.log(slim)
  console.log(formdata)
  console.log(progress, success, failure)
}
export default {
  name: 'marca-photo',
  components: {
    MgLayout,
    'slim-cropper': Slim,
    QBtn,
    QField,
    QInput,
    QSelect,
    QUploader,
    QCardMedia,
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
                codmarca: vm.data.codmarca,
                'slim[]': imagem
              }
              if (vm.data.codimagem === null) {
                vm.$axios.post('imagem', data).then(function (request) {
                  Notify.create.positive('Sua foto foi cadastrada')
                  vm.$router.push('/marca/' + vm.data.codmarca)
                }).catch(function (error) {
                  vm.erros = error.response.data.erros
                })
              }
              else {
                vm.$axios.put('imagem/' + vm.data.codimagem, data).then(function (request) {
                  Notify.create.positive('Sua foto foi alterada')
                  vm.$router.push('/marca/' + vm.data.codmarca)
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
      vm.$axios.get('marca/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.loadData(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
