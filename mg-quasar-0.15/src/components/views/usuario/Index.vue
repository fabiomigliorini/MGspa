<template>
  <mg-layout drawer>

    <template slot="title">
      <span v-if="grupousuario">{{ grupousuario.grupousuario }}</span>
      <span v-else>
        Usuários
      </span>
    </template>

    <div slot="drawer">

      <form>
        <q-item>
          <q-item-main>
            <q-input v-model="filter.usuario" float-label="Descrição" :before="[{icon: 'search', handler () {}}]"/>
          </q-item-main>
        </q-item>
        <q-list-header>Grupos</q-list-header>
        <q-item tag="label">
          <q-item-side icon="done_all">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Todos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.grupo" :val="null" />
          </q-item-side>
        </q-item>
        <template v-for="grupo in grupos">
          <q-item tag="label">
            <q-item-side icon="people">
            </q-item-side>
            <q-item-main>
              <q-item-tile title>{{ grupo.grupousuario }}</q-item-tile>
            </q-item-main>
            <q-item-side right>
              <q-radio v-model="filter.grupo" :val="grupo.codgrupousuario" />
            </q-item-side>
          </q-item>
        </template>
        <q-list-header>Ativos</q-list-header>
        <!-- Filtra Ativos -->
        <q-item tag="label">
          <q-item-side icon="thumb_up">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Ativos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.inativo" :val="1" />
          </q-item-side>
        </q-item>

        <!-- Filtra Inativos -->
        <q-item tag="label">
          <q-item-side icon="thumb_down">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Inativos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.inativo" :val="2" />
          </q-item-side>
        </q-item>

        <!-- Filtra Ativos e Inativos -->
        <q-item tag="label">
          <q-item-side icon="thumbs_up_down">
          </q-item-side>
          <q-item-main>
            <q-item-tile title>Ativos e Inativos</q-item-tile>
          </q-item-main>
          <q-item-side right>
            <q-radio v-model="filter.inativo" :val="9" />
          </q-item-side>
        </q-item>

      </form>

    </div>

    <div slot="content">
      <q-modal :content-css="{ minWidth: '50vw' }" v-model="createModal">
        <q-card style="box-shadow:none;">
          <q-card-title>
            Novo
            <span slot="subtitle">Novo grupos de usuário</span>
            <q-icon slot="right" name="add" />
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
              <q-btn color="" @click="createModal = false">Cancelar</q-btn>
              <q-btn @click.prevent="createGrupoUsuario" color="primary">Salvar</q-btn>
            </q-card-actions>
          </q-card-main>
        </q-card>
      </q-modal>
      <q-modal :content-css="{ minWidth: '50vw' }" v-model="updateModal">
        <q-card style="box-shadow:none;">
          <q-card-title>
            Editar
            <span slot="subtitle">Editar grupo de usuário</span>
            <q-icon slot="right" name="edit" />
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
              <q-btn color="" @click="updateModal = false">Cancelar</q-btn>
              <q-btn @click.prevent="updateGrupoUsuario" color="primary">Salvar</q-btn>
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

      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll :handler="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <q-item :to="'/usuario/' + item.codusuario">

              <!-- Imagem -->
              <!-- <q-item-side :image="item.imagem.url" v-if="item.imagem" /> -->
              <q-item-side :avatar="item.imagem.url" v-if="item.imagem" />
              <q-item-side v-else />
              <q-item-main>
                <q-item-tile>
                  {{ item.usuario }}
                  <q-chip tag square pointing="left" color="negative" v-if="item.inativo">Inativo</q-chip>
                </q-item-tile>
                <q-item-tile sublabel>
                  <span v-for="grupo in item.grupos">
                    {{ grupo.grupousuario }}: {{ grupo.filial }},
                  </span>
                </q-item-tile>
              </q-item-main>
            </q-item>
            <q-item-separator inset />
          </template>
        </q-infinite-scroll>
      </q-list>
      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky corner="bottom-right" :offset="[90, 18]" v-if="grupousuario">
        <q-fab
          color="primary"
          active-icon="edit"
          icon="edit"
          direction="up"
          class="animate-pop"
        >
          <q-fab-action color="primary" icon="edit" @click="updateModal = true">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar Grupo</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" icon="thumb_up" @click="activate()" v-if="grupousuario.inativo">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Ativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" icon="thumb_down" @click="inactivate()" v-if="!grupousuario.inativo">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Inativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="red" icon="delete" @click.native="destroy()">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>

      </q-page-sticky>

      <q-page-sticky corner="bottom-right" :offset="[18, 18]">

        <q-fab color="primary" icon="add" direction="up">
          <q-fab-action color="primary" @click="$router.push('/usuario/create')" icon="account_circle">
            <q-tooltip anchor="center left" self="center right" :offset="[10, 0]">Novo Usuário</q-tooltip>
          </q-fab-action>

          <q-fab-action color="primary" @click="createModal = true" icon="supervisor_account">
            <q-tooltip anchor="center left" self="center right" :offset="[10, 0]">Novo Grupo</q-tooltip>
          </q-fab-action>

        </q-fab>

      </q-page-sticky>
    </div>

    <div slot="mgfooter" v-if="grupousuario">
      <mg-autor
        :data="grupousuario"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutor from '../../utils/MgAutor'
import MgNoData from '../../utils/MgNoData'

import { debounce } from 'quasar'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout,
    MgErrosValidacao,
    MgAutor,
    MgNoData
  },
  data () {
    return {
      data: [], // Lista de Usuários
      grupos: [], // Lista de Grupos
      grupousuario: false, // Grupo selecionado
      page: 1,
      filter: {},
      loading: true,
      dataGrupousuario: {
        grupousuario: null
      },
      erros: false,
      createModal: false,
      updateModal: false
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
        if (val.grupo !== null) {
          this.loadDataGrupo(val.grupo)
        }
        else {
          this.grupousuario = false
        }
      },
      deep: true
    }
  },
  methods: {
    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {
      // salva no Vuex filtro da marca
      this.$store.commit('filtroUsuario/updateFiltroUsuario', this.filter)

      // inicializa variaveis
      var vm = this
      var params = this.filter
      params.page = this.page
      params.sort = 'usuario'
      params.fields = 'usuario,codusuario,inativo,codimagem'
      this.loading = true

      // faz chamada api
      vm.$axios.get('usuario', {
        params
      }).then(response => {
        // Se for para concatenar, senao inicializa
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (response.data.data.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // desmarca flag de carregando
        this.loading = false

        // Executa done do scroll infinito
        if (done) {
          done()
        }
      })
    }, 500),

    createGrupoUsuario: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        ok: 'Salvar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('grupo-usuario', vm.dataGrupousuario).then(function (request) {
          vm.$q.notify({
            message: 'Novo grupo inserido',
            type: 'positive',
          })
          vm.dataGrupousuario.grupousuario = null
          vm.erros = false
          vm.createModal = false
          vm.loadDataGrupos()
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
      })
    },

    updateGrupoUsuario: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        ok: 'Salvar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.put('grupo-usuario/' + vm.grupousuario.codgrupousuario, { 'grupousuario': vm.grupousuario.grupousuario }).then(function (request) {
          vm.$q.notify({
            message: 'Grupo atualizado',
            type: 'positive',
          })
          vm.dataGrupousuario.grupousuario = null
          vm.erros = false
          vm.updateModal = false
          vm.loadDataGrupos()
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
      })
    },

    activate: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Ativar',
        message: 'Tem certeza que deseja ativar?',
        ok: 'Ativar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
          vm.loadDataGrupo(vm.grupousuario.codgrupousuario)
          vm.$q.notify({
            message: 'Grupo ativado',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error.response)
        })
      })
    },

    inactivate: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Inativar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
          vm.loadDataGrupo(vm.grupousuario.codgrupousuario)
          vm.$q.notify({
            message: 'Grupo inativado',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error.response)
        })
      })
    },

    destroy: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario).then(function (request) {
          vm.$q.notify({
            message: 'Registro excluido',
            type: 'positive',
          })
          vm.filter.grupo = null
          vm.loadDataGrupos()
          vm.grupousuario = false
        }).catch(function (error) {
          console.log(error)
        })
      })
    },

    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadDataGrupo: function (id) {
      let vm = this
      vm.$axios.get('grupo-usuario/' + id + '/detalhes').then(function (request) {
        vm.grupousuario = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },

    loadDataGrupos: function () {
      let vm = this
      let params = {
        sort: 'grupousuario'
      }
      vm.$axios.get('grupo-usuario', {
        params
      }).then(function (response) {
        vm.grupos = response.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.filter = this.$store.state.filtroUsuario
    this.loadDataGrupos()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.q-item-sublabel > span {
  font-weight: normal;
}
</style>
