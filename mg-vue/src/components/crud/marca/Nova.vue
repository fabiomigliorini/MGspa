<template>
  <mg-layout>

    <div slot="titulo">
      Marcas
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
                      label="Marca"
                      v-model="dados.marca"
                      required
                      autofocus
                    ></v-text-field>
                  </v-flex>
                </v-layout>
                <v-layout row>
                  <v-flex xs12>
                    <v-checkbox label="Site" v-model="dados.site"></v-checkbox>
                  </v-flex>
                </v-layout>
                <v-layout row>
                  <v-flex xs12>
                    <v-text-field
                      name="descricaosite"
                      label="Descricão site"
                      multi-line
                      v-model="dados.descricaosite">
                    </v-text-field>
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

      <v-fab error router :to="{path: '/marca/nova'}">
        <v-icon light>add</v-icon>
      </v-fab>

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
        marca: null,
        descricaosite: null,
        site: null
      }
    }
  },
  methods: {
    create: function () {
      var vm = this
      window.axios.post('marca', vm.dados).then(function (request) {
        vm.$router.push('/marca/' + request.data.codmarca)
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
