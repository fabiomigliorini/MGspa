<template>
  <mg-layout>

    <template slot="title">
      Início
    </template>

    <div slot="content" class="layout-padding">
      <div class="row wrap">
        <div class="text-center col-md-1 col-xs-3 col-sm-2" v-for="aplicativo in aplicativos">
          <q-btn flat color="primary" :icon="aplicativo.icon" :to="aplicativo.path" size="2rem"
            style="min-height:0" />
          <br>
          <router-link :to="aplicativo.path" class="text-primary" style="text-decoration: none">
            <small>{{ aplicativo.title }}</small>
          </router-link>
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
import auth from '../services/auth'

export default {
  name: 'index',
  components: {
    MgLayout
  },

  data() {
    return {
      left: false
    }
  },

  computed: {
    aplicativos: {
      get() {
        return this.$store.state.aplicativos.aplicativos
      }
    }
  },
  methods: {
    async user() {
      // Valida via /userinfo (OIDC). Se falhar, o guard do router já
      // redireciona pro SSO — aqui só atualiza o store de perfil pra UI.
      const ok = await auth.validarToken()
      if (!ok) return

      const info = auth.state.usuario
      this.$store.commit('perfil/updatePerfil', {
        usuario: info.usuario,
        avatar: info.avatar,
        codusuario: info.codusuario
      })
    }
  },

  mounted() {
    this.user()
  }
}
</script>
<style></style>
