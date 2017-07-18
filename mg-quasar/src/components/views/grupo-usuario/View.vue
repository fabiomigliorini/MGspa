<template>
  <mg-layout>

    <template slot="title">
      {{ dados.grupousuario }}
    </template>
<!--
    <template slot="drawer">
      <div class="list">
        <div class="item three-lines">
          <div class="item-content">
            <form @submit.prevent="pesquisar()">
              <div class="floating-label">
                <input class="full-width" v-model="filtro.grupousuario">
                <label>Descrição</label>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>
-->
    <div slot="content">
      <div class="card-content">
        {{ dados.grupousuario }}
      </div>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import { Loading } from 'quasar'

export default {
  name: 'grupo-usuario-view',
  components: {
    MgLayout, Loading
  },
  data () {
    return {
      dados: {}
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + this.$route.params.id + '/details').then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  mounted () {
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
