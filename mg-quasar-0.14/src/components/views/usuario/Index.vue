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
            <q-item :to=" '/usuario/grupo-usuario/' + row.codgrupousuario ">
              <q-item-main>
                {{ row.grupousuario }}
              </q-item-main>
            </q-item>
          </template>

        </q-list>
      </form>

    </div>

    <div slot="content">
      <q-modal ref="createModal" :content-css="{ minWidth: '50vw' }">
        <q-card style="box-shadow:none;">
          <q-card-title>
            Novo Grupo de Usuário
          </q-card-title>
          <q-card-main>
            <form @submit.prevent="createGrupoUsuario()">
              <q-field>
                <q-input
                  type="text"
                  v-model="dataGrupousuario.grupousuario"
                  float-label="Grupo"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.grupousuario"></mg-erros-validacao>
            </form>
            <br />
            <q-card-actions vertival>
              <q-btn color="" @click="$refs.createModal.close()">Cancelar</q-btn>
              <q-btn @click.prevent="createGrupoUsuario()" color="primary">Salvar</q-btn>
            </q-card-actions>
          </q-card-main>
        </q-card>
      </q-modal>
      <q-modal ref="updateModal" :content-css="{ minWidth: '50vw' }">
        <q-card style="box-shadow:none;">
          <q-card-title>
            Editar Grupo
          </q-card-title>
          <q-card-main>
            <form @submit.prevent="updateGrupoUsuario()">
              <q-field>
                <q-input
                  type="text"
                  v-model="grupousuario.grupousuario"
                  float-label="Grupo"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.grupousuario"></mg-erros-validacao>
            </form>
            <br />
            <q-card-actions vertival>
              <q-btn color="" @click="$refs.updateModal.close()">Cancelar</q-btn>
              <q-btn @click.prevent="updateGrupoUsuario()" color="primary">Salvar</q-btn>
            </q-card-actions>
          </q-card-main>
        </q-card>
      </q-modal>

      <q-card v-if="grupousuario.inativo">
        <q-card-main>
          <span class="text-red">
            Inativo desde {{ moment(grupousuario.inativo).format('L') }}
          </span>
        </q-card-main>
      </q-card>

      <q-list no-border inset-delimiter link v-if="!grupousuario">
        <template v-for="row in data">
            <q-item :to=" '/usuario/' + row.codusuario ">
              <q-item-main>
                {{ row.usuario }}
              </q-item-main>
            </q-item>
        </template>
      </q-list>
      <q-list multiline no-border inset-delimiter link v-else>
        <q-item v-for="row in grupousuario.Usuarios" :to=" '/usuario/' + row.codusuario " :key="row.codusuario">
          <q-item-main>
            <q-item-tile>
              {{ row.usuario }}
            </q-item-tile>
            <q-item-tile sublabel>
              {{ Object.values(row.grupos).toString() }}
            </q-item-tile>
          </q-item-main>
        </q-item>

        <q-item v-if="grupousuario.Usuarios.length === 0">
          <q-item-main>
            <q-item-tile>
              Nenhum usuário
            </q-item-tile>
          </q-item-main>
        </q-item>
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
          <q-fab-action color="primary" icon="supervisor_account" @click="$refs.createModal.open()">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Novo Grupo</q-tooltip>
          </q-fab-action>
          <q-fab-action color="primary" icon="edit" @click="$refs.updateModal.open()" v-if="grupousuario">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar Grupo</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" icon="thumb_up" @click="activate()" v-if="grupousuario.inativo">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Ativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" icon="thumb_down" @click="inactivate()" v-if="!grupousuario.inativo">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Inativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="red" icon="delete" @click.native="destroy()" v-if="grupousuario">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-fixed-position>
    </div>

    <div slot="footer" v-if="grupousuario">
      <mg-autor
        :criacao="grupousuario.criacao"
        :usuariocriacao="grupousuario.usuariocriacao"
        :alteracao="grupousuario.alteracao"
        :usuarioalteracao="grupousuario.usuarioalteracao"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutor from '../../utils/MgAutor'

import {
  debounce,
  Toast,
  Dialog,
  QList,
  QListHeader,
  QItem,
  QItemTile,
  QItemSide,
  QItemMain,
  QField,
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
  QCardSeparator,
  QCardActions
 } from 'quasar'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout,
    MgErrosValidacao,
    MgAutor,
    QList,
    QListHeader,
    QItem,
    QItemTile,
    QItemSide,
    QItemMain,
    QField,
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
    QCardSeparator,
    QCardActions
  },
  data () {
    return {
      data: [], // Lista de Usuários
      grupos: [], // Lista de Grupos
      grupousuario: false, // Grupo selecionado
      page: 1,
      filter: {},
      fim: true,
      dataGrupousuario: {
        grupousuario: null
      },
      erros: false
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        if (this.$route.name === 'grupo-usuario') {
          this.loadDataGrupo(this.$route.params.id)
        }
        else {
          this.loadData(false, null)
        }
      },
      deep: true
    },
    '$route' (to, from) {
      if (this.$route.name === 'grupo-usuario') {
        this.loadDataGrupo(this.$route.params.id)
      }
    }
  },
  methods: {
    createGrupoUsuario: function () {
      let vm = this
      Dialog.create({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        buttons: [
          {
            label: 'Cancelar',
            handler () {}
          },
          {
            label: 'Salvar',
            handler () {
              window.axios.post('grupo-usuario', vm.dataGrupousuario).then(function (request) {
                Toast.create.positive('Novo grupo inserido')
                vm.dataGrupousuario.grupousuario = null
                vm.erros = false
                vm.loadDataGrupos()
                vm.$refs.createModal.close()
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    },

    updateGrupoUsuario: function () {
      let vm = this
      Dialog.create({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        buttons: [
          {
            label: 'Cancelar',
            handler () {}
          },
          {
            label: 'Salvar',
            handler () {
              window.axios.put('grupo-usuario/' + vm.grupousuario.codgrupousuario, { 'grupousuario': vm.grupousuario.grupousuario }).then(function (request) {
                Toast.create.positive('Grupo atualizado')
                vm.erros = false
                vm.loadDataGrupos()
                vm.loadDataGrupo(vm.grupousuario.codgrupousuario)
                vm.$refs.updateModal.close()
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    },

    activate: function () {
      let vm = this
      Dialog.create({
        title: 'Ativar',
        message: 'Tem certeza de deseja ativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Ativar',
            handler () {
              window.axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
                vm.loadDataGrupo(vm.grupousuario.codgrupousuario)
                Toast.create.positive('Registro ativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },

    inactivate: function () {
      let vm = this
      Dialog.create({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Inativar',
            handler () {
              window.axios.post('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
                vm.loadDataGrupo(vm.grupousuario.codgrupousuario)
                Toast.create.positive('Registro inativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },

    destroy: function () {
      let vm = this
      Dialog.create({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        buttons: [
          'Cancelar',
          {
            label: 'Excluir',
            handler () {
              window.axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario).then(function (request) {
                vm.$router.push('/usuario')
                vm.loadDataGrupos()
                vm.grupousuario = false
                Toast.create.positive('Registro excluído')
              }).catch(function (error) {
                console.log(error)
              })
            }
          }
        ]
      })
    },

    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadDataGrupo: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + id + '/details').then(function (request) {
        vm.grupousuario = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
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

    loadDataGrupos: function () {
      let vm = this
      window.axios.get('grupo-usuario').then(function (response) {
        vm.grupos = response.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.filter = this.$store.getters['filter/grupousuario']
    this.loadDataGrupos()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
