<template>
  <mg-layout menu>

    <div slot="titulo">
      Grupos de Usuário
    </div>

    <div slot="menu">
      <div class="container">
        <v-flex xs8>
              <v-text-field
                name="filtro"
                label="Busca"
                id="filtro"
                v-model="filtro.grupousuario"
                @change.native.stop="pesquisar()"
              ></v-text-field>
            </v-flex>
      </div>
    </div>

    <div slot="conteudo">

       <v-list>
        <template v-for="item in dados">
          <v-list-tile avatar router :to="{path: '/grupo-usuario/' + item.codgrupousuario }" v-bind:key="item.codgrupousuario">
            <v-list-tile-content>
              <v-list-tile-title>
                {{ item.grupousuario }}
              </v-list-tile-title>
              <v-list-tile-sub-title>
                #{{ item.codgrupousuario }}
              </v-list-tile-sub-title>
            </v-list-tile-content>
          </v-list-tile>
          <v-divider></v-divider>
        </template>
      </v-list>

      <div class="container" v-if="!fim">
        <v-btn @click.native.stop="mais()" block info :loading="carregando">
          Mais
          <v-icon right>expand_more</v-icon>
        </v-btn>
      </div>

      <v-btn router :to="{path: '/grupo-usuario/novo'}" class="red white--text" light fixed bottom right fab>
        <v-icon>add</v-icon>
      </v-btn>

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
      pagina: 1,
      filtro: {
        grupousuario: null
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
      window.axios.get('grupo-usuario', {params}).then(response => {
        vm.dados = vm.dados.concat(response.data.data)
        this.fim = (response.data.current_page >= response.data.last_page)
        this.carregando = false
      })
    },
    mais () {
      this.pagina++
      this.carregaListagem()
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
