<template>
  <mg-layout>

    <div slot="titulo">
      Grupo de Usuario
    </div>

    <div slot="menu">
      <div class="container">
      </div>
    </div>

    <div slot="conteudo">
      <v-card class="elevation-0">
          <v-card-text>
            <v-container fluid>
              <form autocomplete="off" @submit.prevent="create">
                <v-layout row>
                  <v-flex xs12>
                    <v-text-field
                      name="input-2"
                      label="Grupo"
                      v-model="dados.grupousuario"
                      required
                      autofocus
                    ></v-text-field>
                  </v-flex>
                </v-layout>
                <v-layout row>
                  <v-flex xs12>
                    <v-btn type="submit" primary light>Salvar</v-btn>
                  </v-flex>
                </v-layout>
              </form>
            </v-container>
          </v-card-text>
        </v-card>


    </div>

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
        console.log(error)
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
