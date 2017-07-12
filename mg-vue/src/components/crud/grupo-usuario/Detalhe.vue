<template>
  <mg-layout>

    <template slot="titulo">
      {{ dados.grupousuario }}
    </template>

    <template slot="botoes-menu-esquerda">
      <v-btn icon class="blue--text" router :to="{ path: '/grupo-usuario/' }">
        <v-icon>arrow_back</v-icon>
      </v-btn>
    </template>

    <template slot="conteudo">
      <v-container fluid>
          <v-layout row wrap class="mb-3"  v-if="dados.inativo">
            <v-flex xs12>
              <v-card hover>
                  <v-card-text class="grey lighten-4 red--text">
                    <strong>Inativo desde {{ moment(dados.inativo).format('L') }}</strong>
                  </v-card-text>
              </v-card>
            </v-flex>
          </v-layout>
          {{ dialog }}
          <v-layout row wrap>
            <v-flex md4 xs12>
              <v-card hover>
                <v-card-text>
                  <dl>
                      <dt>#</dt>
                      <dd>{{ dados.codgrupousuario }}</dd>
                      <dt>Grupo:</dt>
                      <dd>{{ dados.grupousuario }}</dd>
                  </dl>
                </v-card-text>
              </v-card>
            </v-flex>
            <v-flex md4 xs12>
              <v-card hover>
                <v-card-title primary-title>
                  <h5 class="headline mb-0 mt-0">Usuários</h5>
                </v-card-title>
                <v-list>
                  <v-list-tile v-for="usuario in dados.Usuarios" router :to="{path: '/usuario/' + usuario.codusuario }" :key="usuario.codusuario">
                    <v-list-tile-content>
                      <v-list-tile-title>{{ usuario.usuario }}</v-list-tile-title>
                      <v-list-tile-sub-title>{{ usuario.filial.filial }}</v-list-tile-sub-title>
                    </v-list-tile-content>
                  </v-list-tile>
                </v-list>
              </v-card>
            </v-flex>
            <v-flex md4 xs12>
              <v-card hover>
                <v-card-title primary-title>
                  <h5 class="headline mb-0 mt-0">Permissões</h5>
                </v-card-title>
                <v-list>
                  <v-list-tile v-for="permissao in dados.Permissoes" :key="permissao.codpermissao">
                    <v-list-tile-content>
                      <v-list-tile-title>{{ permissao.permissao }}</v-list-tile-title>
                    </v-list-tile-content>
                  </v-list-tile>
                </v-list>
              </v-card>
            </v-flex>
          </v-layout>

          <v-layout row justify-center style="position: relative;">
            <v-dialog v-model="dialog" persistent>
              <v-card>
                <v-card-title class="headline">Use Google's location service?</v-card-title>
                <v-card-text>Let Google help apps determine location. This means sending anonymous location data to Google, even when no apps are running.</v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn class="green--text darken-1" flat="flat" @click.native="dialog = false">Disagree</v-btn>
                  <v-btn class="green--text darken-1" flat="flat" @click.native="dialog = false">Agree</v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>
          </v-layout>

        </v-container>

        <v-speed-dial
          v-model="fab"
          bottom="bottom"
          right="right"
          direction="top"
          transition="scale">
          <v-btn slot="activator" class="blue darken-2" dark fab hover v-model="fab">
            <v-icon>edit</v-icon>
            <v-icon>close</v-icon>
          </v-btn>
          <v-btn fab dark small class="red" @click.native.stop="confirmar('Tem certeza que deseja deletar', 'deletar()')" v-tooltip:left="{ html: 'Excluir'}">
            <v-icon>delete</v-icon>
          </v-btn>
          <v-btn v-if="dados.inativo" fab dark small class="orange" @click.native.prevent="confirmar('Tem certeza que deseja ativar', 'ativar()')" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_down</v-icon>
          </v-btn>
          <v-btn v-else fab dark small class="orange" @click.native.prevent="confirmar('Tem certeza que deseja inativar', 'inativar()')" v-tooltip:left="{ html: 'inativar'}">
            <v-icon>thumb_up</v-icon>
          </v-btn>
          <v-btn fab dark small class="green" router :to="{ path: '/grupo-usuario/' + dados.codgrupousuario + '/editar' }" v-tooltip:left="{ html: 'Editar'}">
            <v-icon>edit</v-icon>
          </v-btn>
        </v-speed-dial>
        <!--
        <v-dialog v-model="dialog" width="50%" persistent>
          <v-card>
            <v-card-title class="headline">{{ dialog.pergunta }} '{{ dados.grupousuario }}'?</v-card-title>
            <v-divider></v-divider>
            <v-card-actions class="grey lighten-4">
              <v-spacer></v-spacer>
              <v-btn flat @click.native="dialog = false">Cancelar</v-btn>
              <v-btn v-if="dialog.metodo == 'inativar()'" primary dark @click.native="inativar()">Confirmar</v-btn>
              <v-btn v-if="dialog.metodo == 'ativar()'" primary dark @click.native="ativar()">Confirmar</v-btn>
              <v-btn v-if="dialog.metodo == 'deletar()'" primary dark @click.native="deletar()">Confirmar</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
        -->
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
  name: 'grupo-usuario-detalhe',
  components: {
    MgLayout
  },
  data () {
    return {
      fab: false,
      tabs: null,
      dados: {},
      dialog: false,
      pergunta: '',
      metodo: ''
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + this.$route.params.id + '/details').then(function (request) {
        vm.dados = request.data
        // store.commit('grupoUsuario/dados', mensagem)
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    deletar: function () {
      let vm = this
      vm.dialog = false
      window.axios.delete('grupo-usuario/' + vm.dados.codgrupousuario).then(function (request) {
        vm.$router.push('/grupo-usuario')
        vm.$store.commit('snackbar/error', 'Registro Excluído!')
      }).catch(function (error) {
        console.log(error)
      })
    },
    inativar: function () {
      let vm = this
      vm.dialog = false
      window.axios.post('grupo-usuario/' + this.dados.codgrupousuario + '/inativo').then(function (request) {
        vm.dados = request.data
        vm.$store.commit('snackbar/success', 'Registro Inativado!')
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    ativar: function () {
      let vm = this
      vm.dialog = false
      window.axios.delete('grupo-usuario/' + this.dados.codgrupousuario + '/inativo').then(function (request) {
        vm.dados = request.data
        vm.$store.commit('snackbar/success', 'Registro Ativado!')
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    confirmar: function (pergunta, metodo) {
      let vm = this
      vm.dialog = true
      vm.pergunta = pergunta
      vm.metodo = metodo
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
