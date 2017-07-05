<template>
  <mg-layout menu>

    <div slot="titulo">
      Marcas
    </div>

    <div slot="menu">
      <div class="container">
        <v-flex xs8>
              <v-text-field
                name="filtro"
                label="Busca"
                id="filtro"
                v-model="filtro.marca"
                @change.native.stop="pesquisar()"
              ></v-text-field>
            </v-flex>
      </div>
    </div>

    <div slot="conteudo">

       <v-list two-line>
        <template v-for="item in marca">
          <v-list-tile avatar router :to="{path: '/marca/' + item.codmarca }" v-bind:key="item.codmarca">
            <v-list-tile-avatar>
              <!-- <img src="http://localhost/MGUplon/public/imagens/{{ item.codimagem }}.jpg"> -->
              <img v-if="item.codimagem" :src="'http://localhost/MGUplon/public/imagens/'+ item.codimagem + '.jpg'">
              <img v-else :src="'http://localhost/MGUplon/public/imagens/semimagem.jpg'">
            </v-list-tile-avatar>
            <v-list-tile-content>
              <v-list-tile-title>
                {{ item.marca }}
              </v-list-tile-title>
              <v-list-tile-sub-title>
                #{{ item.codmarca }}
              </v-list-tile-sub-title>
            </v-list-tile-content>
            <v-list-tile-content class="hidden-sm-and-down">
              <v-list-tile-sub-title>
                5 abaixo do mínimo /
                2 acima do máximo
              </v-list-tile-sub-title>
              <v-list-tile-sub-title>
                25/dez/16 Última compra
              </v-list-tile-sub-title>
            </v-list-tile-content>
            <v-list-tile-content>
              <v-list-tile-title class="text-xs-right">
                <v-icon class="yellow--text text--darken-3">star</v-icon>
                <v-icon class="yellow--text text--darken-3">star</v-icon>
                <v-icon class="grey--text text--lighten-1">star_border</v-icon>
              </v-list-tile-title>
              <v-list-tile-sub-title class="text-xs-right">
                #1
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

    <v-fab-transition >
      <v-btn router :to="{path: '/marca/nova'}" class="red white--text" light absolute bottom right fab>
        <v-icon>add</v-icon>
      </v-btn>
    </v-fab-transition>

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
      marca: [],
      pagina: 1,
      filtro: {
        marca: null
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
      window.axios.get('marca', {params}).then(response => {
        vm.marca = vm.marca.concat(response.data.data)
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
      this.marca = []
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
