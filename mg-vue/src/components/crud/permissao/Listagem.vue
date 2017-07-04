<template>
<mg-layout menu>

  <div slot="titulo">
    Permissões
  </div>

  <div slot="menu">
    <div class="container">
      <v-flex xs8>
        <v-text-field name="filtro" label="Busca" id="filtro" v-model="filtro.permissao" @change.native.stop="pesquisar()"></v-text-field>

      </v-flex>
    </div>
    <v-list two-line>
      <template v-for="(item, index) in dados.Permissoes">
          <transition name="component-fade">
            <v-list-item v-bind:key="item.codpermissao">
              <v-list-tile @click.native.stop="tab(index)">
                <v-list-tile-content>
                  <v-list-tile-title>
                    {{ index }}
                  </v-list-tile-title>
                </v-list-tile-content>
              </v-list-tile>
              <v-divider></v-divider>
            </v-list-item>
          </transition>
        </template>
      </v-list>

  </div>

  <div slot="conteudo">
    <template v-for="(permissao, index) in dados.Permissoes">
      <div v-if="index == tabs">
        <table class="datatable table">
          <thead>
            <tr>
              <th>
                &nbsp;
              </th>
                <th v-for="(grupos, index) in dados.Grupos">
                    {{ grupos.grupousuario }}
                </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(item, index) in permissao">
              <tr>
                <th style="text-align: right">
                  {{ index }}
                </th>
                  <td style="text-align: center" v-for="grupo in dados.Grupos">
                    <v-btn @click.native.prevent="removePermissao(tabs, index, grupo.codgrupousuario)" icon v-if="item.codgrupousuario.includes(grupo.codgrupousuario)" class="green--text" small>
                      <v-icon>thumb_up</v-icon>
                    </v-btn>
                    <v-btn @click.native.prevent="adicionaPermissao(tabs, index, grupo.codgrupousuario)" icon v-else class="red--text" small>
                      <v-icon>thumb_down</v-icon>
                    </v-btn>
                  </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>

<!--
    <transition name="component-fade">
      <div class="container" v-if="!fim">
        <v-btn @click.native.stop="mais()" block info :loading="carregando">
          Mais
          <v-icon right>expand_more</v-icon>
        </v-btn>
      </div>
    </transition>
-->
    <v-fab error router :to="{path: '/permissao/nova'}">
      <v-icon light>add</v-icon>
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
  name: 'permissao-listagem',
  components: {
    MgLayout
  },
  data () {
    return {
      dados: [],
      tabs: null,
      pagina: 1,
      filtro: {
        permissao: null
      },
      fim: false,
      carregando: false,
      snackbar: {
        status: false,
        contexto: '',
        mensagem: ''
      }
    }
  },
  methods: {
    carregaListagem () {
      var vm = this
      this.carregando = true
      window.axios.get('permissao').then(response => {
        vm.dados = response.data
      })
    },
    tab (codpermissao) {
      this.tabs = codpermissao
    },
    pesquisar () {
      this.pagina = 1
      this.dados = []
      this.fim = false
      this.carregaListagem()
    },
    adicionaPermissao (index, permissao, codgrupousuario) {
      var vm = this
      var dados = {
        permissao: permissao,
        codgrupousuario: codgrupousuario
      }
      window.axios.post('permissao', dados).then(function (request) {
        console.log(request.data)
        if (request.data === true) {
          vm.dados.Permissoes[index][permissao].codgrupousuario.push(codgrupousuario)
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    removePermissao (index, permissao, codgrupousuario) {
      var vm = this
      var dados = {
        permissao: permissao,
        codgrupousuario: codgrupousuario
      }
      window.axios.delete('permissao/' + dados.permissao + '/' + dados.codgrupousuario).then(function (request) {
        if (request.status === 204) {
          var rm = vm.dados.Permissoes[index][permissao]['codgrupousuario'].indexOf(codgrupousuario)
          if (rm !== -1) {
            vm.dados.Permissoes[index][permissao]['codgrupousuario'].splice(rm, 1)
          }
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  mounted () {
    this.carregaListagem()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.list__tile {
  height: 50px;
}
</style>
