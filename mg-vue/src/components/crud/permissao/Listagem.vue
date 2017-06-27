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
      <template v-for="item in dados">
          <transition name="component-fade">
            <v-list-item v-bind:key="item.codpermissao">
              <v-list-tile @click.native.stop="tab(item.codpermissao)">
                <v-list-tile-content>
                  <v-list-tile-title>
                    {{ item.permissao }}
                  </v-list-tile-title>
                  <v-list-tile-sub-title>
                    #{{ item.codpermissao }}
                  </v-list-tile-sub-title>
                </v-list-tile-content>
              </v-list-tile>
              <v-divider></v-divider>
            </v-list-item>
          </transition>
        </template>
      </v-list>

  </div>

  <div slot="conteudo">
    <template v-for="item in dados">
      <p v-if="item.codpermissao == tabs">{{ item.permissao }}</p>
    </template>


<!--
    <v-list two-line>
      <template v-for="item in dados">
          <transition name="component-fade">
            <v-list-item v-bind:key="item.codpermissao">
              <v-list-tile avatar router :to="{path: '/permissao/' + item.codpermissao }">

                <v-list-tile-content>
                  <v-list-tile-title>
                    {{ item.permissao }}
                  </v-list-tile-title>
                  <v-list-tile-sub-title>
                    #{{ item.codpermissao }}
                  </v-list-tile-sub-title>
                </v-list-tile-content>

              </v-list-tile>
              <v-divider></v-divider>
            </v-list-item>

          </transition>
        </template>
    </v-list>

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
      dados: [],
      tabs: null,
      pagina: 1,
      filtro: {
        permissao: null
      },
      fim: false,
      carregando: false
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
        vm.dados = response.data.data
      })
    },
    tab (codpermissao) {
      this.tabs = codpermissao
      console.log(this.tabs)
    },
    pesquisar () {
      this.pagina = 1
      this.dados = []
      this.fim = false
      this.carregaListagem()
    }

  },
  mounted () {
    this.carregaListagem()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
