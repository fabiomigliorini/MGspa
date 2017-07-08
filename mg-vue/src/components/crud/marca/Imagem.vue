<template>
  <mg-layout>

    <template slot="titulo">
      Marcas - {{ dados.marca }} - Imagem
    </template>

    <template slot="menu">
      <div class="container">
      </div>
    </template>

    <template slot="conteudo">
      <v-card class="elevation-0">
          <v-card-text>
            <v-container fluid>
              <v-layout row wrap>
                <v-flex sm6>
                  <h4>{{ dados.marca }}</h4>
                </v-flex>
              </v-layout>
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
    deletar: function (id) {
      var vm = this
      window.axios.delete('marca/' + this.$route.params.id).then(function (request) {
        vm.$router.push('/marca')
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

<style scoped>
</style>
