<template>
  <mg-layout>

    <div slot="titulo">
      {{ dados.permissao }}
    </div>

    <div slot="menu">
      <div class="container">
      </div>
    </div>

    <div slot="conteudo">
      <v-card class="elevation-0">
          <v-card-text>
            <v-container fluid>
              <v-layout row wrap>
                <v-flex sm6>
                  <p>Lista</p>
                </v-flex>
              </v-layout>
            </v-container>
          </v-card-text>
        </v-card>
      <v-fab error router :to="{ path: '/marca/' + dados.codmarca + '/editar' }" style="bottom:190px">
        <v-icon light>mode_edit</v-icon>
      </v-fab>
      <v-fab error @click.native.stop="deletar()">
        <v-icon light>delete</v-icon>
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
      dados: {}
    }
  },
  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('permissao/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    deletar: function (id) {
      var vm = this
      window.axios.delete('permissao/' + this.$route.params.id).then(function (request) {
        vm.$router.push('/permissao')
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
  dt {
    font-size: 0.8em;
    color: grey;
  }
  dd {
    margin-bottom: 7px;
    font-weight: 300;
  }


</style>
