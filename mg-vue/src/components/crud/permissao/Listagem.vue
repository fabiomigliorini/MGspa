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
                    <!--
                    <template v-for="item in item.codgrupousuario">
                    </template>
                    -->
                    <!-- Grupo: {{ grupo.codgrupousuario }} -->
                    <!-- Grupo: {{ item.codgrupousuario[grupo.codgrupousuario -1] }} -->
                    <!-- {{ item.codgrupousuario.includes(1) }} -->
                    <!--
                    <br>
                    Codgrupo: [{{ grupo.codgrupousuario }}]
                    <br>
                    <br>
                    -->
                    <!-- {{ item[codgrupousuario][grupo.codgrupousuario] }} -->
                    <!-- <input v-on:click.prevent="mudarPermissao(index, grupo.codgrupousuario, item.codgrupousuario[grupo.codgrupousuario])" type="checkbox" v-model="item.codgrupousuario[grupo.codgrupousuario]"> -->
                    <!-- <v-switch v-on:click.prevent="mudarPermissao(index, grupo.codgrupousuario, item.codgrupousuario[grupo.codgrupousuario])" v-model="item.codgrupousuario"></v-switch> -->
                    <!-- <input v-on:click.prevent="mudarPermissao(index, grupo.codgrupousuario)" type="checkbox" v-if="item[codgrupousuario].includes(2)"> -->
                    <input type="checkbox" v-model="item.codgrupousuario[grupo.codgrupousuario]">
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
      var params = this.filtro
      params.page = this.pagina
      this.carregando = true
      window.axios.get('permissao', {
        params
      }).then(response => {
        vm.dados = response.data
        // console.log(JSON.stringify(vm.dados.Permissoes.marca['marca.index'].codgrupousuario[1]))
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
    mudarPermissao (permissao, codgrupousuario, checked) {
      if (checked) {
        this.adicionaPermissao(permissao, codgrupousuario)
      } else {
        this.removePermissao(permissao, codgrupousuario)
      }
    },
    adicionaPermissao (permissao, codgrupousuario) {
      console.log('Adicionando ' + permissao)
      // var vm = this
      // var dados = {
      //   permissao: permissao,
      //   codgrupousuario: codgrupousuario
      // }
      // window.axios.post('permissao', dados).then(function (request) {
      //   console.log(request.data)
      //   if (request.data === true) {
      //     vm.snackbar.status = true
      //     vm.snackbar.mensagem = 'Permissão adicionada!'
      //     vm.snackbar.contexto = 'success'
      //     vm.dados.Permissoes[permissao][codgrupousuario].push(codgrupousuario)
      //   }
      // }).catch(function (error) {
      //   console.log(error.response)
      // })
    },
    removePermissao (permissao, codgrupousuario) {
      console.log('Removendo ' + permissao)
      // var vm = this
      // var dados = {
      //   permissao: permissao,
      //   codgrupousuario: codgrupousuario
      // }
      // window.axios.delete('permissao/' + 1, {params: dados}).then(function (request) {
      //   vm.snackbar.status = true
      //   vm.snackbar.mensagem = 'Permissão removida!'
      //   vm.snackbar.contexto = 'success'
      // }).catch(function (error) {
      //   console.log(error.response)
      // })
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
