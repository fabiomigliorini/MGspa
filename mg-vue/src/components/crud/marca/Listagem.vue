<template>
  <mg-layout>

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
          <transition name="component-fade">
          <v-list-item v-bind:key="item.codmarca">
            <v-list-tile avatar router :to="{path: '/marca/' + item.codmarca }">
              <v-list-tile-avatar>
                <!-- <img src="http://localhost/MGUplon/public/imagens/{{ item.codimagem }}.jpg"> -->
                <img v-if="item.codimagem" :src="'http://localhost/MGUplon/public/imagens/'+ item.codimagem + '.jpg'">
                <img v-else :src="'http://localhost/MGUplon/public/imagens/semimagem.jpg'">
              </v-list-tile-avatar>
              </v-list-tile-avatar>
              <v-list-tile-content>
                <v-list-tile-title v-html="item.marca"></v-list-tile-title>
                <v-list-tile-sub-title v-html="item.alteracao"></v-list-tile-sub-title>
              </v-list-tile-content>
            </v-list-tile>
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

      <v-fab error router :to="{path: '/marca/nova'}">
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
      window.axios.get('marcas', {params}).then(response => {
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
