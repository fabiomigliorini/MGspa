<template>
  <mg-layout drawer>

    <template slot="title">
      <span v-if="grupousuario">{{ grupousuario.grupousuario }}</span>
      <span v-else>
        Grupos de usuários
      </span>
    </template>

    <div slot="drawer">
      <form>
        <q-item>
          <q-item-main>
            <q-input v-model="filter.grupousuario" float-label="Descrição" :before="[{icon: 'search', handler () {}}]"/>
          </q-item-main>
        </q-item>
        <q-list no-border link>
          <template v-for="row in grupos">
            <q-item>
              <q-item-main>
                {{ row.grupousuario }}
              </q-item-main>
            </q-item>
          </template>

        </q-list>
      </form>

    </div>

    <div slot="content">
      <q-modal ref="basicModal" :content-css="{ minWidth: '50vw' }">
        <q-card>
          <q-card-title>
            Novo Grupo de Usuário
          </q-card-title>
          <q-card-separator />
          <q-card-main>
            <q-input v-model="filter.grupousuario" float-label="Descrição" :before="[{icon: 'search', handler () {}}]"/>
            <q-btn color="" @click="$refs.basicModal.close()">Cancelar</q-btn>
            <q-btn color="primary" @click="$refs.basicModal.close()">Salvar</q-btn>
          </q-card-main>
        </q-card>
      </q-modal>

      <q-list no-border inset-delimiter link>
        <template v-for="row in data">
            <q-item :to=" '/usuario/' + row.codusuario ">
              <q-item-main>
                {{ row.usuario }}
              </q-item-main>
            </q-item>
        </template>
      </q-list>

      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          active-icon="add"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/usuario/create'}">
            <q-fab-action color="primary" icon="account_circle">
                <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Novo Usuário</q-tooltip>
            </q-fab-action>
          </router-link>
          <!-- <router-link :to="{ path: '/usuario/grupo-usuario/create'}"> -->
            <q-fab-action color="primary" icon="supervisor_account" @click="$refs.basicModal.open()">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Novo Grupo</q-tooltip>
            </q-fab-action>
          <!-- </router-link> -->
        </q-fab>
      </q-fixed-position>
    </div>


  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import {
  debounce,
  // Dialog,
  // Toast,
  QList,
  QListHeader,
  QItem,
  QItemSide,
  QItemMain,
  QInput,
  QIcon,
  QRadio,
  QFixedPosition,
  QBtn,
  QFab,
  QFabAction,
  QTooltip,
  QModal,
  QCard,
  QCardTitle,
  QCardMain,
  QCardSeparator
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
    QBtn,
    QFab,
    QFabAction,
    QTooltip,
    QModal,
    QCard,
    QCardTitle,
    QCardMain,
    QCardSeparator
  },
  data () {
    return {
      data: [],
      grupos: [],
      page: 1,
      filter: {},
      fim: true,
      tab: 0,
      carregando: false,
      grupousuario: false
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
        this.loadDataGrupo(false, null)
      },
      deep: true
    }
  },
  methods: {
    grupoUsuarioCreate: function () {
    },

    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadData: debounce(function (concat, done) {
      // this.$store.commit('filter/usuario', this.filter)
      var vm = this
      var params = this.filter
      params.page = this.page
      this.loading = true
      window.axios.get('usuario', {
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
    }, 500),
    loadDataGrupo: debounce(function (concat, done) {
      this.$store.commit('filter/grupousuario', this.filter)
      var vm = this
      var params = this.filter
      params.page = this.page
      this.loading = true
      window.axios.get('grupo-usuario', {
        params
      }).then(response => {
        if (concat) {
          vm.grupos = vm.grupos.concat(response.data.data)
        }
        else {
          vm.grupos = response.data.data
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
