<template>
  <mg-layout drawer>

    <template slot="title">
      Marcas
    </template>

    <template slot="drawer">

      <div class="list">

        <div class="item three-lines">
          <div class="item-content">
            <div class="floating-label">
              <input required class="full-width" v-model="filtro.marca" @change.native.stop="pesquisar()">
              <label>Descrição</label>
            </div>
          </div>
        </div>

        <div class="item">
        </div>
      </div>

      <!--
      <v-flex xs12 class="container pt-3 pb-0 mb-0 mt-0">
      <v-text-field class="pt-0 pb-0 mb-0 mt-4"   ></v-text-field>

      </v-flex>

      <v-list dense>

        <v-subheader class="mt-0 grey--text text--darken-1">ORDENAR POR</v-subheader>

        <v-list-tile @click.native="ordena('abcposicao')">
          <v-list-tile-action>
            <v-icon :class="(filtro.sort=='abcposicao')?'blue--text':''">trending_up</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sort=='abcposicao')?'blue--text':''">Vendas</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="ordena('marca')">
          <v-list-tile-action>
            <v-icon :class="(filtro.sort=='marca')?'blue--text':''">sort_by_alpha</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sort=='marca')?'blue--text':''">Descrição</v-list-tile-title>
        </v-list-tile>

        <v-subheader class="mt-0 grey--text text--darken-1">FILTRAR</v-subheader>

        <v-list-tile @click.native="sobrando()">
          <v-list-tile-action>
            <v-icon :class="(filtro.sobrando==true)?'blue--text':''">arrow_upward</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.sobrando==true)?'blue--text':''">Estoque Sobrando</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="faltando()">
          <v-list-tile-action>
            <v-icon :class="(filtro.faltando==true)?'blue--text':''">arrow_downward</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.faltando==true)?'blue--text':''">Estoque Faltando</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="abccategoria()">
          <v-list-tile-action>
            <v-icon :class="filtro.abccategoria?'blue--text':''">star</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="filtro.abccategoria?'blue--text':''">
            <span v-if="filtro.abccategoria">
              {{ 4 - filtro.abccategoria }}
              </span> Estrelas
          </v-list-tile-title>
        </v-list-tile>

        <v-subheader class="mt-0 grey--text text--darken-1">ATIVOS</v-subheader>

        <v-list-tile @click.native="inativo(1)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==1)?'blue--text':''">thumb_up</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==1)?'blue--text':''">Ativos</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="inativo(2)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==2)?'blue--text':''">thumb_down</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==2)?'blue--text':''">Inativos</v-list-tile-title>
        </v-list-tile>

        <v-list-tile @click.native="inativo(9)">
          <v-list-tile-action>
            <v-icon :class="(filtro.inativo==9)?'blue--text':''">thumbs_up_down</v-icon>
          </v-list-tile-action>
          <v-list-tile-title :class="(filtro.inativo==9)?'blue--text':''">Ativos e Inativos</v-list-tile-title>
        </v-list-tile>

      </v-list>
      -->

    </template>

    <div slot="content" class="layout-padding">
      teste
      <!-- if you want automatic padding -->
    </div>


  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'

export default {

  components: {
    MgLayout
  },

  data () {
    return {
      marca: [],
      pagina: 1,
      filtro: {}, // Vem do Store
      fim: true,
      tab: 0,
      carregando: false
    }
  },

  methods: {
    carregaListagem () {
      this.$store.commit('filtro/marca', this.filtro)
      var vm = this
      var params = this.filtro
      params.page = this.pagina
      this.carregando = true
      window.axios.get('marca', {
        params
      }).then(response => {
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
      this.carregaListagem()
    },

    ordena (campo) {
      this.filtro.sort = campo
      this.pesquisar()
    },

    inativo (valor) {
      this.filtro.inativo = valor
      this.pesquisar()
    },

    sobrando () {
      this.filtro.sobrando = !this.filtro.sobrando
      this.pesquisar()
    },

    faltando () {
      this.filtro.faltando = !this.filtro.faltando
      this.pesquisar()
    },

    abccategoria () {
      if (!this.filtro.abccategoria) {
        this.filtro.abccategoria = 0
      }
      this.filtro.abccategoria++
      if (this.filtro.abccategoria > 4) {
        this.filtro.abccategoria = 0
      }
      this.pesquisar()
    }
  },
  mounted () {
    this.filtro = this.$store.getters['filtro/marca']
    this.carregaListagem()
  }

}
</script>

<style>
</style>
