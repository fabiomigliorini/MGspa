<template>
  <mg-layout>

    <button slot="menu" v-link=" '/grupo-usuario' ">
      <i>arrow_back</i>
    </button>

    <template slot="title">
      {{ dados.grupousuario }}
    </template>

    <div slot="content">
      <div class="card-content">
        {{ dados.grupousuario }}
      </div>

      <q-fab
        icon="keyboard_arrow_up"
        direction="up"
        class="primary circular absolute-bottom-right"
      >
        <router-link :to="{ path: '/grupo-usuario/' + dados.codgrupousuario + '/update'}">
          <q-small-fab class="primary" icon="edit" ></q-small-fab>
        </router-link>
        <q-small-fab class="orange" @click.native="activate()" icon="thumb_up" v-if="dados.inativo"></q-small-fab>
        <q-small-fab class="orange" @click.native="inactivate()" icon="thumb_down" v-else></q-small-fab>
        <q-small-fab class="primary" @click.native="photo()" icon="insert_photo"></q-small-fab>
        <q-small-fab class="red" @click.native="destroy()" icon="delete"></q-small-fab>
      </q-fab>
    </div>

  </mg-layout>
</template>

<script>
import { Loading } from 'quasar'
import MgLayout from '../../layouts/MgLayout'

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
      window.axios.get('grupo-usuario/' + id + '/details').then(function (request) {
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
