<template>
  <mg-layout>

    <div slot="titulo">
      {{ dados.usuario }}
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
                  {{ dados.usuario }}
                </v-flex>
              </v-layout>
            </v-container>
          </v-card-text>
        </v-card>
      <v-fab error router :to="{ path: '/usuario/' + dados.codusuario + '/editar' }" style="bottom:190px">
        <v-icon light>mode_edit</v-icon>
      </v-fab>
      <v-fab error @click.native.stop="deletar()">
        <v-icon light>delete</v-icon>
      </v-fab>
      <v-snackbar
            :success="snackbar.contexto === 'success'"
            :error="snackbar.contexto === 'error'"
            multi-line
            v-model="snackbar.status"
            >
        {{ snackbar.mensagem }}
        <v-btn light flat @click.native="snackbar = false">Fechar</v-btn>
      </v-snackbar>
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
      dados: {},
      snackbar: {
        status: false,
        contexto: '',
        mensagem: ''
      }
    }
  },
  methods: {
    carregaDados: function (id) {
      var vm = this
      window.axios.get('usuario/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    deletar: function (id) {
      var vm = this
      window.axios.delete('usuario/' + this.$route.params.id).then(function (request) {
        vm.$router.push('/usuario')
      }).catch(function (error) {
        vm.snackbar.status = true
        vm.snackbar.mensagem = error.response.data.mensagem
        vm.snackbar.contexto = 'error'
        console.log(error.response.data)
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
