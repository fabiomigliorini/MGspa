<template>
  <mg-layout drawer back-path="/">

    <template slot="title">
      Permissões – {{ tabs }}
    </template>

    <template slot="drawer">

      <q-list highlight>

        <q-item link v-for="(item, index) in dados.Permissoes" :key="item.codpermissao" v-bind:class="{ 'active': tabs == index }">
          <q-item-main @click.native="tab(index)">
            <span style="word-wrap: break-word;">{{ index }}</span>
          </q-item-main>
        </q-item>

      </q-list>

    </template>

    <div slot="content">
      <div class="layout-padding">
        <q-list highlight  v-if="permissoes">
          <q-item>
            <q-item-main class="col-xs-4"></q-item-main>
            <q-item-main class="col-sm-1" v-for="grupo in dados.Grupos" :key="grupo.codgrupousuario">
              {{ grupo.grupousuario.substr(0, 3) }}
            </q-item-main>
          </q-item>
          <q-item v-for="(permissao, index) in permissoes" :key="permissao">
            <q-item-main class="col-xs-4">
              <q-item-tile class="permissao-item-title">{{ index }}</q-item-tile>
            </q-item-main>
            <q-item-main class="col-sm-1" v-for="grupo in dados.Grupos" :key="grupo.codgrupousuario">
              <q-btn @click.prevent="removePermissao(tabs, index, grupo.codgrupousuario)" flat round small class="text-positive" icon="check_box" v-if="permissao.codgrupousuario.includes(grupo.codgrupousuario)"></q-btn>
              <q-btn @click.prevent="adicionaPermissao(tabs, index, grupo.codgrupousuario)" flat round small class="text-grey" icon="check_box_outline_blank" v-else></q-btn>
            </q-item-main>
          </q-item>
        </q-list>
      </div>
    </div>
  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'

export default {
  name: 'permissao-listagem',
  components: {
    MgLayout
  },
  data () {
    return {
      leftSide: true,
      dados: [],
      permissoes: false,
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
      vm.$axios.get('permissao').then(response => {
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
      vm.$axios.post('permissao', dados).then(function (request) {
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
      vm.$axios.delete('permissao/' + 1, {params: dados}).then(function (request) {
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
.permissao-item-title {
  word-break: break-all;
}
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
