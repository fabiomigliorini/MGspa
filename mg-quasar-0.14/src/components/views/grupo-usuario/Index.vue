<template>
  <mg-layout drawer>

    <template slot="title">
      Grupos de usuário
    </template>

    <div slot="drawer">
      <form>
      <q-list no-border inset-delimiter>
        <q-item>
          <q-item-main>
             <q-input v-model="filter.grupousuario" float-label="Descrição" :before="[{icon: 'search', handler () {}}]"/>
          </q-item-main>
        </q-item>
        <q-list-header>Ativos</q-list-header>
        <q-item>
          <q-item-side icon="thumb_up" />
          <q-item-main label="Ativos" />
          <q-item-side>
            <q-radio v-model="filter.inativo" val="1"></q-radio>
          </q-item-side>
        </q-item>
        <q-item>
          <q-item-side icon="thumb_down" />
          <q-item-main label="Inativos" />
          <q-item-side>
            <q-radio v-model="filter.inativo" val="2"></q-radio>
          </q-item-side>
        </q-item>
        <q-item>
          <q-item-side icon="thumbs_up_down" />
          <q-item-main label="Ativos e Inativos" />
          <q-item-side>
            <q-radio v-model="filter.inativo" val="9"></q-radio>
          </q-item-side>
        </q-item>
      </q-list>
      </form>

    </div>

    <div slot="content">
      <q-list no-border inset-delimiter striped link>
        <template v-for="row in data">
            <q-item :to=" '/grupo-usuario/' + row.codgrupousuario ">
              <q-item-main>
                {{ row.grupousuario }}
              </q-item-main>
            </q-item>
        </template>
      </q-list>
      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <router-link :to="{ path: '/grupo-usuario/create' }">
          <q-btn round color="primary" icon="add" class="animate-pop"/>
        </router-link>
      </q-fixed-position>
    </div>


  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import {
  debounce,
  QList,
  QListHeader,
  QItem,
  QItemSide,
  QItemMain,
  QInput,
  QIcon,
  QRadio,
  QFixedPosition,
  QBtn
 } from 'quasar'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout,
    QList,
    QListHeader,
    QItem,
    QItemSide,
    QItemMain,
    QInput,
    QIcon,
    QRadio,
    QFixedPosition,
    QBtn
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
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    }
  },
  methods: {
    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadData: debounce(function (concat, done) {
      this.$store.commit('filter/grupousuario', this.filter)
      var vm = this
      var params = this.filter
      params.page = this.page
      this.loading = true
      window.axios.get('grupo-usuario', {
        params
      }).then(response => {
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }
        this.loading = false
        if (done) {
          done()
        }
      })
    }, 500)
  },
  created () {
    this.filter = this.$store.getters['filter/grupousuario']
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
