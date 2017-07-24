<template>
  <mg-layout drawer>

    <template slot="title">
      Permissões
    </template>

    <template slot="drawer">

      <div class="list no-border striped">
        <div class="item item-link" v-for="(item, index) in dados.Permissoes">
          <div class="item-content" @click.prevent="tab(index)">
            {{ index }}
          </div>
        </div>
      </div>

    </template>

    <div slot="content">
      <template v-for="(permissao, index) in dados.Permissoes">
          <div v-if="index == tabs" class="permissoes">
            <div class="container-tabela">
            <table class="q-table normal">
              <thead>
                <tr>
                  <th>&nbsp;</th>
                  <th v-for="(grupos, index) in dados.Grupos">
                      <div>{{ grupos.grupousuario }}</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(item, index) in permissao">
                  <tr>
                    <td>
                      <strong>{{ index }}</strong>
                    </td>
                    <td style="text-align: center" v-for="grupo in dados.Grupos">
                      <button @click.prevent="removePermissao(tabs, index, grupo.codgrupousuario)" icon v-if="item.codgrupousuario.includes(grupo.codgrupousuario)" class="small clear green circular">
                        <i>thumb_up</i>
                      </button>
                      <button @click.prevent="adicionaPermissao(tabs, index, grupo.codgrupousuario)" icon v-else class="small clear circular red" small>
                        <i>thumb_down</i>
                      </button>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          </div>
      </template>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'

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
      let vm = this
      vm.tabs = codpermissao
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
