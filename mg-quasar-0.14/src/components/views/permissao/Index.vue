<template>
  <mg-layout drawer>

    <template slot="title">
      Permissões – {{ tabs }}
    </template>

    <template slot="drawer">

      <q-list highlight>
        <q-item link v-for="(item, index) in dados.Permissoes" :key="item.codpermissao" v-bind:class="{ 'active': tabs == index }">
          <q-item-main>
            <q-item-tile title @click.prevent="tab(index)">
              <span style="word-wrap: break-word;">{{ index }}</span>
            </q-item-tile>
          </q-item-main>
        </q-item>
      </q-list>

    </template>

    <div slot="content">
      <div class="layout-padding">
<!--
        <q-list highlight>
          <q-item v-for="(permissao, index) in permissoes" :key="permissao">
            <q-item-main>
              <q-item-tile>{{ index }}</q-item-tile>
            </q-item-main>
            <q-item-main class="col-sm-1" v-for="grupo in dados.Grupos" :key="grupo.codgrupousuario">
              <q-btn @click.prevent="removePermissao(index, permissao, grupo.codgrupousuario)" flat round small class="text-positive" icon="check_box" v-if="permissao.codgrupousuario.includes(grupo.codgrupousuario)"></q-btn>
              <q-btn @click.prevent="adicionaPermissao(index, permissao, grupo.codgrupousuario)" flat round small class="text-grey" icon="check_box_outline_blank" v-else></q-btn>
            </q-item-main>
          </q-item>
        </q-list>
-->

        <template v-for="(permissao, index) in dados.Permissoes">
          <div v-if="index == tabs" class="permissoes">
            <div class="container-tabela">
              <table class="q-table striped-odd">
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
                      <td class="text-left">
                        <strong>{{ index }}</strong>
                      </td>
                      <td class="text-center" v-for="grupo in dados.Grupos">
                        <q-btn @click.prevent="removePermissao(tabs, index, grupo.codgrupousuario)" flat round small class="text-positive" icon="check_box" v-if="item.codgrupousuario.includes(grupo.codgrupousuario)"></q-btn>
                        <q-btn @click.prevent="adicionaPermissao(tabs, index, grupo.codgrupousuario)" flat round small class="text-grey" icon="check_box_outline_blank" v-else></q-btn>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </template>

      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import {
  QList,
  QListHeader,
  QItem,
  QItemTile,
  QItemSide,
  QItemMain,
  QItemSeparator,
  QBtn,
  QToggle
 } from 'quasar'

export default {
  name: 'permissao-listagem',
  components: {
    MgLayout,
    QList,
    QListHeader,
    QItem,
    QItemTile,
    QItemSide,
    QItemMain,
    QItemSeparator,
    QBtn,
    QToggle
  },
  data () {
    return {
      dados: [],
      permissoes: [],
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
      vm.permissoes = vm.dados.Permissoes[codpermissao]
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
