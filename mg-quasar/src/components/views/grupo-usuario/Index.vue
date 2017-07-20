<template>
  <mg-layout drawer>

    <template slot="title">
      Grupos de usuário
    </template>

    <template slot="drawer">
      <div class="list no-border">
        <form>
          <div class="item three-lines">
            <div class="item-content">
              <div class="floating-label">
                <input required class="full-width" v-model="filter.grupousuario">
                <label>Descrição</label>
              </div>
            </div>
          </div>


                    <div class="list-label">Ativos</div>

                    <label class="item">
                      <i class="item-primary">thumb_up</i>
                      <div class="item-content has-secondary">
                        Ativos
                      </div>
                      <div class="item-secondary">
                        <q-radio v-model="filter.inativo" val="1"></q-radio>
                      </div>
                    </label>

                    <label class="item">
                      <i class="item-primary">thumb_down</i>
                      <div class="item-content has-secondary">
                        Inativos
                      </div>
                      <div class="item-secondary">
                        <q-radio v-model="filter.inativo" val="2"></q-radio>
                      </div>
                    </label>

                    <label class="item">
                      <i class="item-primary">thumbs_up_down</i>
                      <div class="item-content has-secondary">
                        Ativos e Inativos
                      </div>
                      <div class="item-secondary">
                        <q-radio v-model="filter.inativo" val="9"></q-radio>
                      </div>
                    </label>
        </form>
      </div>
    </template>

    <div slot="content">

      <div class="list no-border striped">
        <div class="item" v-for="item in data">
          <q-drawer-link :to="{ path: '/grupo-usuario/' + item.codgrupousuario }">
            <div class="item-content">
              {{ item.grupousuario }}
            </div>
          </q-drawer-link>
        </div>
      </div>

      <router-link :to="{ path: '/grupo-usuario/create' }">
        <button class="primary circular absolute-bottom-right">
          <i>add</i>
        </button>
      </router-link>
    </div>


  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout
  },
  data () {
    return {
      data: [],
      page: 1,
      filter: {},
      fim: true,
      tab: 0,
      carregando: false
    }
  },
  watch: {
    filter: {
      handler: window._.debounce(function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      }, 500),
      deep: true
    }
  },
  methods: {
    // carregaListagem () {
    //   var vm = this
    //   var params = this.filter
    //   params.page = this.page
    //   window.axios.get('grupo-usuario', {params}).then(response => {
    //     vm.data = vm.data.concat(response.data.data)
    //     this.fim = (response.data.current_page >= response.data.last_page)
    //   }).catch(function (error) {
    //     console.log(error.response)
    //   })
    // },
    // mais () {
    //   this.page++
    //   this.carregaListagem()
    // },
    // pesquisar () {
    //   this.page = 1
    //   this.data = []
    //   this.fim = false
    //   this.carregaListagem()
    // },
    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadData (concat, done) {
      this.$store.commit('filter/grupousuario', this.filter)
      var vm = this
      var params = this.filter
      params.page = this.page
      this.carregando = true
      window.axios.get('grupo-usuario', {
        params
      }).then(response => {
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }
        this.fim = (response.data.current_page >= response.data.last_page)
        this.carregando = false
        if (done) {
          done()
        }
      })
    }
  },
  created () {
    this.filter = this.$store.getters['filter/grupousuario']
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
