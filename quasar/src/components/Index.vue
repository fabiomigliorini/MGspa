<template>
  <mg-layout>

    <template slot="title">
      Início
    </template>

    <div slot="content" class="layout-padding">
      <div class="row wrap">
        <div class="text-center col-md-1 col-xs-3 col-sm-2" v-for="aplicativo in aplicativos">
          <q-btn flat color="primary" :icon="aplicativo.icon" @click="$router.push(aplicativo.path)" size="2rem" style="min-height:0"/>
          <br>
          <small @click="$router.push(aplicativo.path)" class="text-primary" style="cursor:pointer">
            {{aplicativo.title}}
          </small>
        </div>
      </div>
    </div>
    <div v-if="user">
      Autenticado {{ user() }}
    </div>
  </mg-layout>
</template>

<script>
import MgLayout from '../layouts/MgLayout'

export default {
  name: 'index',
  components: {
    MgLayout
  },

  data () {
    return {
      left: false
    }
  },

  computed: {
    aplicativos: {
      get () {
        return this.$store.state.aplicativos.aplicativos
      }
    }
  },
  methods: {
    user: function (e) {
      var vm = this
      let data = {
        usuario:'',
        codusuario:'',
      }
        vm.$axios.get('auth/user').then(response => {
          // salva código da imagem avatar do usuário
          localStorage.setItem('auth.usuario.usuario', response.data.usuario)
          localStorage.setItem('auth.usuario.codusuario', response.data.codusuario)
          this.$store.commit('perfil/updatePerfil', {
            usuario: localStorage.getItem('auth.usuario.usuario'),
            avatar: localStorage.getItem('auth.usuario.avatar'),
            codusuario: localStorage.getItem('auth.usuario.codusuario')
          })
        }).catch(error => {
          console.log(error.response)
        })
    }
  },

  mounted () {
  }

}
  const  urlParams = new URLSearchParams(window.location.search);
  const Token = urlParams.get("accesstoken");
  if (Token){
  localStorage.setItem('auth.token', Token);
  setTimeout(function() {
    window.location.href = "/";
}, 1000);
}
</script>
<style>
</style>
