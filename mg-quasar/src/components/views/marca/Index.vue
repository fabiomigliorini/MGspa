<template>
  <mg-layout drawer>

    <template slot="title">
      Marcas
    </template>

    <template slot="drawer">

      <div class="list">

        <form @change="pesquisar()">

          <div class="item three-lines">
            <i class="item-primary">search</i>
            <div class="item-content">
              <div class="floating-label">
                <input required class="full-width" v-model="filtro.marca">
                <label>Descrição</label>
              </div>
            </div>
          </div>

          <div class="list-label">Ordenar Por</div>

          <label class="item">
            <i class="item-primary">trending_up</i>
            <div class="item-content has-secondary">
              Vendas
            </div>
            <div class="item-secondary">
              <q-radio v-model="filtro.sort" val="abcposicao"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">sort_by_alpha</i>
            <div class="item-content has-secondary">
              Descrição
            </div>
            <div class="item-secondary">
              <q-radio v-model="filtro.sort" val="marca"></q-radio>
            </div>
          </label>

          <div class="list-label">Estoque</div>

          <label class="item">
            <i class="item-primary">arrow_upward</i>
            <div class="item-content has-secondary">
              Sobrando
            </div>
            <div class="item-secondary">
              <q-toggle v-model="filtro.sobrando"></q-toggle>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">arrow_downward</i>
            <div class="item-content has-secondary">
              Faltando
            </div>
            <div class="item-secondary">
              <q-toggle v-model="filtro.faltando"></q-toggle>
            </div>
          </label>

          <div class="list-label">Curva ABC</div>

          <div class="item two-lines">
            <i class="item-primary">star</i>
            <div class="item-content">
              <q-double-range
                v-model="filtro.abccategoriaB"
                label
                markers
                snap
                :min="0"
                :max="3"
                :step="1"
                @input="sliderInput()"
              ></q-double-range>
            </div>
          </div>

          <div class="list-label">Ativos</div>

          <label class="item">
            <i class="item-primary">thumb_up</i>
            <div class="item-content has-secondary">
              Ativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filtro.inativo" val="1"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">thumb_down</i>
            <div class="item-content has-secondary">
              Inativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filtro.inativo" val="2"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">thumbs_up_down</i>
            <div class="item-content has-secondary">
              Ativos e Inativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filtro.inativo" val="9"></q-radio>
            </div>
          </label>
        </form>
      </div>

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

  watch: {
    filtro: function (novoFiltro) {
      console.log('aqui no watcher')
      console.log(novoFiltro)
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

    sliderInput: window._.debounce(function () {
      this.pesquisar()
    }, 500),

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
  created () {
    this.filtro = this.$store.getters['filtro/marca']
    this.carregaListagem()
  }

}
</script>

<style>
</style>
