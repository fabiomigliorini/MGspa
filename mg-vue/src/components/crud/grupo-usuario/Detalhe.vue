<template>
  <mg-layout>

    <div slot="titulo">
      {{ dados.grupousuario }}
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
                  {{ dialog }}
                  <dl>
                      <dd v-if="dados.inativo" class="red--text">Inativo desde {{ dados.inativo }}</dd>
                      <dt>#</dt>
                      <dd>{{ dados.codgrupousuario }}</dd>
                      <dt>Grupo:</dt>
                      <dd>{{ dados.grupousuario }}</dd>
                  </dl>
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
          <v-btn v-if="dados.inativo" fab dark small class="orange" @click.native.prevent="ativar(dados.codgrupousuario)" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_down</v-icon>
          </v-btn>
          <v-btn v-else fab dark small class="orange" @click.native.prevent="inativar(dados.codgrupousuario)" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="green" router :to="{ path: '/grupousuario/' + dados.codgrupousuario + '/editar' }" v-tooltip:left="{ html: 'Editar'}">
            <v-icon>edit</v-icon>
          </v-btn>
        </v-speed-dial>

        <v-dialog v-model="dialog.dialog" persistent>
          <!-- <v-btn primary dark slot="activator">Open Dialog</v-btn> -->
          <v-card>
            <v-card-title class="headline">Tem certeza de desenha inativar {{ dados.grupousuario }}?</v-card-title>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn class="green--text darken-1" flat="flat" @click.native="dialog.dialog = false">Cancelar</v-btn>
              <v-btn class="green--text darken-1" flat="flat" @click.native="confirmar(inativar)">OK</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

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
      fab: {
        fab: false,
        right: true,
        bottom: true
      },
      dialog: {
        dialog: false,
        confirma: false
      }
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + this.$route.params.id).then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    deletar: function () {
      let vm = this
      window.axios.delete('grupo-usuario/' + vm.dados.codgrupousuario).then(function (request) {
        vm.$router.push('/grupo-usuario')
      }).catch(function (error) {
        console.log(error.response.data)
      })
    },
    inativar: function () {
      let vm = this
      console.log('confirmado')
      window.axios.post('grupo-usuario/' + this.dados.codgrupousuario + '/inativo').then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    ativar: function () {
      let vm = this
      window.axios.delete('grupo-usuario/' + this.dados.codgrupousuario + '/inativo').then(function (request) {
        vm.dados = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    confirmar: function (metodo) {
      let vm = this
      vm.dialog.confirma = true
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
    position: absolute;
  }
  .btn--floating {
    position: relative;
  }
</style>
