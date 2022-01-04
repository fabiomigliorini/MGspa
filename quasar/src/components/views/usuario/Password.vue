<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/usuario/perfil')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      Alterar senha
    </template>

    <div slot="content">

      <div class="row justify-center items-center q-pa-md" style="min-height: 80vh">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
          <q-card>
            <q-card-section>
              <form @submit.prevent="update()">
                <q-input type="password" v-model="data.senha_antiga" label="Senha antiga"/>
                <q-input type="password" v-model="data.senha" label="Nova senha"/>
                <q-input type="password" v-model="data.senha_confirmacao" label="Confirmar senha"/>
              </form>
            </q-card-section>
          </q-card>
        </div>
      </div>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
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
      let vm = this;
      let params = {
        fields: ['usuario', 'impressoratermica', 'impressoramatricial']
      };
      vm.$axios.get('usuario/' + id, { params }).then(function (request) {
        vm.data = request.data;
        vm.data.senha = null
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      let vm = this;
      vm.$axios.put('usuario/' + localStorage.getItem('auth.usuario.codusuario'), vm.data).then(function (request) {
        vm.$q.notify({
          message: 'Usuário alterado',
          type: 'positive',
        });
        vm.$router.push('/usuario/' + request.data.codusuario)
      }).catch(function (error) {
        if(error.response.data.errors.impressoramatricial){
          vm.$q.notify({message: error.response.data.errors.impressoramatricial, color: 'negative'});
        }
        if(error.response.data.errors.impressoratermica){
          vm.$q.notify({message: error.response.data.errors.impressoratermica, color: 'negative'});
        }
        if(error.response.data.errors.senha){
          vm.$q.notify({message: error.response.data.errors.senha, color: 'negative'});
        }
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
