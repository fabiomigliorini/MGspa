<template>
  <mg-layout>

    <template slot="titulo">
      Marcas
    </template>

    <template slot="menu">
      <div class="container">
      </div>
    </template>

    <template slot="conteudo">
      <v-card class="elevation-0">
          <v-card-text>
            <v-container fluid>
              <form autocomplete="off" @submit.prevent="update">
                <v-layout row>
                  <v-flex xs12>
                    <v-text-field
                      name="input-2"
                      label="Marca"
                      v-model="dados.marca"
                      autofocus
                      required
                    ></v-text-field>
                    <v-checkbox label="Site" v-model="dados.site"></v-checkbox>
                    <v-text-field
                      name="descricaosite"
                      label="Descricão site"
                      multi-line
                      v-model="dados.descricaosite"
                    ></v-text-field>
                    <v-btn type="submit" primary light>Salvar</v-btn>
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
      dados: {}
    }
  },
  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('marca/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      var vm = this
      window.axios.put('marca/' + this.$route.params.id, vm.dados).then(function (request) {
        vm.$router.push('/marca/' + request.data.codmarca)
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
