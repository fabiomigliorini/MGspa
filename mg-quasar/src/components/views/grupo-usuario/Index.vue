<template>
  <mg-layout drawer>

    <template slot="title">
      Grupos de usuário
    </template>

    <template slot="drawer">
      <div class="list">

        <div class="item three-lines">
          <div class="item-content">
            <div class="floating-label">
              <input required class="full-width" v-model="filtro.grupousuario" @change.native.stop="pesquisar()">
              <label>Descrição</label>
            </div>
          </div>
        </div>

        <div class="item">
        </div>
      </div>
    </template>

    <div slot="content">

      <div class="list">
        <div class="item" v-for="item in dados">
          <q-drawer-link :to="{ path: '/grupo-usuario/' + item.codgrupousuario }">
            <div class="item-content">
              {{ item.grupousuario }}
            </div>
          </q-drawer-link>
        </div>
      </div>

      <router-link :to="{ path: '/grupo-usuario/create' }">
        <button
          class="primary circular absolute-bottom-right"
          router :to="{ path: '/grupo-usuario/novo' }"
          style="right: 18px; bottom: 18px;">
          <i>add</i>
        </button>
      </router-link>
    </div>


  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import { Loading } from 'quasar'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout, Loading
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
      Loading.show()
      var vm = this
      var params = this.filtro
      params.page = this.pagina
      window.axios.get('grupo-usuario', {params}).then(response => {
        vm.dados = vm.dados.concat(response.data.data)
        this.fim = (response.data.current_page >= response.data.last_page)
        Loading.hide()
      }).catch(function (error) {
        Loading.hide()
        console.log(error.response)
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
