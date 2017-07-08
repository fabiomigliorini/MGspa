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
      <v-btn icon class="blue--text" @click.native="create()" v-tooltip:left="{ html: 'Salvar'}">
        <v-icon>done</v-icon>
      </v-btn>
    </template>

    <template slot="conteudo">
      <v-card class="elevation-0">
        <v-card-text>
          <v-container fluid>
            <form autocomplete="off" @submit.prevent="create" ref="form">
              <v-layout row>
                <v-flex  md4 sm8 xs12>
                  <v-text-field
                    name="grupousuario"
                    label="Grupo"
                    v-model="dados.grupousuario"
                    required
                    autofocus
                  ></v-text-field>
                </v-flex>
              </v-layout>
            </form>
          </v-container>
        </v-card-text>
      </v-card>
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
      dados: {
        grupousuario: null
      }
    }
  },
  methods: {
    create: function () {
      var vm = this
      window.axios.post('grupo-usuario', vm.dados).then(function (request) {
        vm.$router.push('/grupo-usuario/' + request.data.codgrupousuario)
      }).catch(function (error) {
        console.log(error.response)
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
