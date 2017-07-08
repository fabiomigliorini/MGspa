<template>
<mg-layout menu>

  <template slot="titulo">
    Permissões
  </template>

  <template slot="menu">
    <div class="container">
      <v-flex xs8>
        <v-text-field name="filtro" label="Busca" id="filtro" v-model="filtro.permissao" @change.native.stop="pesquisar()"></v-text-field>
      </v-flex>
    </div>
    <v-list>
      <v-list-tile
        @click.native.stop="tab(index)"
        v-bind:key="item.codpermissao"
        v-for="(item, index) in dados.Permissoes"
      >
        <v-list-tile-content>
          <v-list-tile-title>
            {{ index }}
          </v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-divider></v-divider>
    </v-list>
  </template>

  <template slot="conteudo">
    <template v-for="(permissao, index) in dados.Permissoes">
      <transition name="slide-x-transition">
        <div v-if="index == tabs" class="permissoes">
          <div class="container-tabela">
          <table class="datatable table">
            <thead class="datatable__progress">
              <tr>
                <th>&nbsp;</th>
                <th v-for="(grupos, index) in dados.Grupos" class="column">
                    <div>{{ grupos.grupousuario }}</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(item, index) in permissao">
                <tr>
                  <th>
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
        </div>
      </transition>
    </template>

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
      window.axios.delete('permissao/' + 1, {params: dados}).then(function (request) {
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
<style>
.permissoes {
  position: absolute;
}
.column div {
  padding: 15px 0;
}
table.table tr:not(:last-child) {
  border-bottom: 1px solid rgba(0,0,0,0.100);
}
</style>
