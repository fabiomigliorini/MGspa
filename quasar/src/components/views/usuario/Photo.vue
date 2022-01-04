<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/usuario/perfil')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="upload()" />

    <template slot="title">
      <span v-if="data.codimagem">Alterar </span>
      <span v-else="data.codimagem">Cadastrar </span>
      foto
    </template>

    <div slot="content">
      <div class="row justify-center items-center q-pa-md" style="min-height: 80vh">
        <div class="col-xs-12 col-sm-8 col-md-4 col-lg-3">

          <form @submit.prevent="upload()" enctype="multipart/form-data">
            <q-card >
              <q-card-section>
                <slim-cropper :options="slimOptions"/>
              </q-card-section>
            </q-card>
          </form>

        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import Slim from '../../utils/slim/slim.vue'

function slimInit (data, slim) {
  console.log(slim);
  console.log(data)
}

function slimService (formdata, progress, success, failure, slim) {
  console.log(slim);
  console.log(formdata);
  console.log(progress, success, failure)
}

export default {
  name: 'usuario-photo',
  components: {
    MgLayout,
    'slim-cropper': Slim
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
      let vm = this;
      let form = document.forms[0];
      let input = form.querySelector('input[name="slim[]"]');
      let imagem = input.value;
      let data = {
        codusuario: vm.data.codusuario,
        'slim[]': imagem
      };
      if (vm.data.codimagem === null) {
        vm.$axios.post('imagem', data).then(function (response) {
          vm.$q.notify({
            message: 'Sua foto foi cadastrada',
            type: 'positive',
          });
          vm.avatar()
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
      }
      else {
        vm.$axios.put('imagem/' + vm.data.codimagem, data).then(function (response) {
          vm.$q.notify({
            message: 'Sua foto foi alterada',
            type: 'positive',
          });
          vm.avatar()
          // vm.$router.push('/usuario/foto')
        }).catch(function (error) {
          console.log(error)
          // vm.erros = error.response.data.erros
        })
      }

    },
    avatar: function () {
      let vm = this;
      vm.$axios.get('usuario/' + vm.data.codusuario + '/detalhes').then(function (request) {
        vm.data = request.data;
        let perfil = {
          codusuario: vm.data.codusuario,
          avatar: vm.data.avatar,
          usuario: vm.data.usuario
        };
        localStorage.setItem('auth.usuario.avatar', vm.data.avatar);
        vm.$store.commit('perfil/updatePerfil', perfil)
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    loadData: function (id) {
      let vm = this;
      vm.$axios.get('usuario/' + id + '/detalhes').then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
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
