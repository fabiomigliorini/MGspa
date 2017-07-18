<template>
  <mg-layout>

    <template slot="title">
      Novo Grupos de usuário
    </template>

    <div slot="content">

      <form @submit.prevent="create()">
        <div class="item">
          <div class="item-content row">
            <div class="width-1of3">
              <div class="floating-label">
                <input required class="full-width" v-model="dados.grupousuario" @change.native.stop="pesquisar()" v-bind:rules="erros.grupousuario">
                <label>Descrição</label>
              </div>
            </div>
          </div>
        </div>
      </form>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import { Loading } from 'quasar'

export default {
  name: 'grupo-usuario-create',
  components: {
    MgLayout, Loading
  },
  data () {
    return {
      dados: {
        grupousuario: ''
      },
      erros: {

      }
    }
  },
  methods: {
    create: function () {
      var vm = this
      window.axios.post('grupo-usuario', vm.dados).then(function (request) {
        vm.$router.push('/grupo-usuario/' + request.data.codgrupousuario)
      }).catch(function (error) {
        vm.erros = error.response.data.erros
      })
    }
  },
  mounted () {
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
