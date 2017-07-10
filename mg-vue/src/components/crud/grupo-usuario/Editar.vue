<template>
  <mg-layout>

    <template slot="titulo">
      Grupo de Usuário
    </template>

    <template slot="botoes-menu-esquerda">
      <v-btn icon class="blue--text" router :to="{ path: '/grupo-usuario/' }">
        <v-icon>arrow_back</v-icon>
      </v-btn>
    </template>

    <template slot="botoes-menu-direita">
      <v-btn icon class="blue--text" @click.native="update()" v-tooltip:left="{ html: 'Salvar' }">
        <v-icon>done</v-icon>
      </v-btn>
    </template>

    <template slot="conteudo">
      <v-container fluid white>
        <form autocomplete="off" @submit.prevent="update" class="pt-2">
          <v-layout row>
            <v-flex md4 sm8 xs12>
              <v-text-field
                name="grupousuario"
                label="Grupo"
                v-model="dados.grupousuario"
                autofocus
                required
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

export default {
  name: 'hello',
  components: {
    MgLayout
  },
  data () {
    return {
      dados: {}
    }
  },
  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('grupo-usuario/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      var vm = this
      window.axios.put('grupo-usuario/' + this.$route.params.id, vm.dados).then(function (request) {
        vm.$router.push('/grupo-usuario/' + request.data.codgrupousuario)
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
