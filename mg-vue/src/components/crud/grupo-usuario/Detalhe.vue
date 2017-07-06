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
          <v-layout row wrap>
            <v-flex xs4>
              <v-card>
                <v-card-text>
                  <dl>
                      <dd v-if="dados.inativo" class="red--text">Inativo desde {{ dados.inativo }}</dd>
                      <dt>#</dt>
                      <dd>{{ dados.codgrupousuario }}</dd>
                      <dt>Grupo:</dt>
                      <dd>{{ dados.grupousuario }}</dd>
                  </dl>
                </v-card-text>
              </v-card>
            </v-flex>
            <v-flex xs4>
              <v-card>
                <v-card-title primary-title>
                  <h5 class="headline mb-0 mt-0">Usuários</h5>
                </v-card-title>
                <v-list two-line>
                  <v-list-tile v-for="usuario in dados.Usuarios" router :to="{path: '/usuario/' + usuario.codusuario }" :key="usuario.codusuario">
                    <v-list-tile-content>
                      <v-list-tile-title>{{ usuario.usuario }}</v-list-tile-title>
                      <v-list-tile-sub-title>{{ usuario.filial.filial }}</v-list-tile-sub-title>
                    </v-list-tile-content>
                  </v-list-tile>
                </v-list>
              </v-card>
            </v-flex>
            <v-flex xs4>
              <v-card>
                <v-card-title primary-title>
                  <h5 class="headline mb-0 mt-0">Permissões</h5>
                </v-card-title>
                <v-list two-line>
                  <v-list-tile v-for="permissao in dados.Permissoes" :key="permissao.codpermissao">
                    <v-list-tile-content>
                      <v-list-tile-title>{{ permissao.permissao }}</v-list-tile-title>
                    </v-list-tile-content>
                  </v-list-tile>
                </v-list>
              </v-card>
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
          <v-btn v-else fab dark small class="orange" @click.native.prevent="confirmar('Tem certeza que deseja inativar')" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="green" router :to="{ path: '/grupo-usuario/' + dados.codgrupousuario + '/editar' }" v-tooltip:left="{ html: 'Editar'}">
            <v-icon>edit</v-icon>
          </v-btn>
        </v-speed-dial>

        <v-dialog v-model="dialog.dialog" width="50%" persistent>
          <v-card>
            <v-card-title class="headline">{{ dialog.perunta }} '{{ dados.grupousuario }}'?</v-card-title>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn class="green--text darken-1" flat="flat" @click.native="dialog.dialog = false">Cancelar</v-btn>
              <v-btn class="green--text darken-1" flat="flat" @click.native="inativar()">OK</v-btn>
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
        pergunta: ''
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
        let mensagem = error.response.data
        this.$store.commit('snackbar/error', mensagem)
        console.log(mensagem)
      })
    },
    inativar: function () {
      let vm = this
      vm.dialog.dialog = false
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
    confirmar: function (pergunta) {
      let vm = this
      vm.dialog.perunta = pergunta
      vm.dialog.dialog = true
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
