<template>
  <mg-layout>

    <template slot="titulo">
      Grupo de Usuario
    </template>

    <template slot="botoes-menu-esquerda">
      <v-btn icon class="blue--text" router :to="{ path: '/grupo-usuario/' }">
        <v-icon>arrow_back</v-icon>
      </v-btn>
    </template>

    <template slot="botoes-menu-direita">
      <v-btn icon class="blue--text" @click.native="create()" v-tooltip:left="{ html: 'Salvar' }">
        <v-icon>done</v-icon>
      </v-btn>
    </template>

    <template slot="conteudo">
      <v-container fluid white>
        <form autocomplete="off" @submit.prevent="create" class="pt-2">
          <v-layout row>
            <v-flex md4 sm8 xs12>
              <v-text-field
                name="grupousuario"
                label="Grupo"
                v-case
                required
                autofocus
                v-model="dados.grupousuario"
                v-bind:rules="erros.grupousuario"
              ></v-text-field>
            </v-flex>
          </v-layout>
        </form>
      </v-container>
    </template>

    <!--
    <div fixed slot="rodape">
    </div>
    -->

  </mg-layout>
</template>

<script>
import MgLayout from '../../layout/MgLayout'
import Case from '../../../directives/Case'

export default {
  name: 'grupo-usuario-novo',
  components: {
    MgLayout
  },
  data () {
    return {
      dados: {
        grupousuario: ''
      },
      erros: {}
    }
  },
  directives: {
    Case
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
