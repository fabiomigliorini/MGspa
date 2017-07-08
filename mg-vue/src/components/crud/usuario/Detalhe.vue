<template>
  <mg-layout>

    <template slot="titulo">
      {{ dados.usuario }}
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
                  {{ dados.usuario }}
                </v-flex>
              </v-layout>
            </v-container>
          </v-card-text>
        </v-card>
        <v-speed-dial v-model="fab.fab" :bottom="fab.bottom" :right="fab.right" direction="top" transition="scale">
          <v-btn slot="activator" class="blue darken-2" dark fab hover v-model="fab">
            <v-icon>keyboard_arrow_down</v-icon>
            <v-icon>keyboard_arrow_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="red" @click.native.stop="deletar()" v-tooltip:left="{ html: 'Excluir'}">
            <v-icon>delete</v-icon>
          </v-btn>
          <v-btn v-if="dados.inativo" fab dark small class="orange" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_down</v-icon>
          </v-btn>
          <v-btn v-else fab dark small class="orange" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="indigo" router :to="{ path: '/usuario/' + dados.codusuario + '/imagem' }" v-tooltip:left="{ html: 'Imagem'}">
            <v-icon>add_a_photo</v-icon>
          </v-btn>
          <v-btn fab dark small class="green" router :to="{ path: '/usuario/' + dados.codusuario + '/editar' }" v-tooltip:left="{ html: 'Editar'}">
            <v-icon>edit</v-icon>
          </v-btn>
        </v-speed-dial>
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
      fab: {
        fab: false,
        right: true,
        bottom: true
      },
      dados: {}
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
  .speed-dial {
    position: fixed;
  }
  .btn--floating {
    position: relative;
  }
</style>
