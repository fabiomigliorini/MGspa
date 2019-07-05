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
          <q-item-section>
            <q-input v-model="filter.usuario" label="Descrição" :before="[{icon: 'search', handler () {}}]">
              <template v-slot:before>
                <q-icon name="search" />
              </template>
            </q-input>
          </q-item-section>
        </q-item>

        <q-item-label header>Grupos</q-item-label>
        <q-separator />

        <q-item tag="label" dense>
          <q-item-section avatar>
            <q-icon name="done_all" />
          </q-item-section>
          <q-item-section title>Todos</q-item-section>
          <q-item-section avatar>
            <q-radio v-model="filter.grupo" :val="null" />
          </q-item-section>
        </q-item>

        <template v-for="grupo in grupos">
          <q-item dense tag="label">
            <q-item-section avatar>
              <q-icon name="people"/>
            </q-item-section>
            <q-item-section title>{{ grupo.grupousuario }}</q-item-section>
            <q-item-section avatar>
              <q-radio v-model="filter.grupo" :val="grupo.codgrupousuario" />
            </q-item-section>
          </q-item>
        </template>

        <q-item-label header>Ativos</q-item-label>
        <q-separator />

        <!-- Filtra Ativos -->
        <q-item tag="label" dense>
          <q-item-section avatar>
            <q-icon name="thumb_up"/>
          </q-item-section>
          <q-item-section title>Ativos</q-item-section>
          <q-item-section avatar>
            <q-radio v-model="filter.inativo" :val="1" />
          </q-item-section>
        </q-item>

        <!-- Filtra Inativos -->
        <q-item tag="label" dense>
          <q-item-section avatar>
            <q-icon name="thumb_down"/>
          </q-item-section>
          <q-item-section>Inativos</q-item-section>
          <q-item-section avatar>
            <q-radio v-model="filter.inativo" :val="2" />
          </q-item-section>
        </q-item>

        <!-- Filtra Ativos e Inativos -->
        <q-item tag="label" dense>
          <q-item-section avatar>
            <q-icon name="thumbs_up_down"/>
          </q-item-section>
          <q-item-section >Ativos e Inativos</q-item-section>
          <q-item-section avatar>
            <q-radio v-model="filter.inativo" :val="9" />
          </q-item-section>
        </q-item>

      </form>
    </div>

    <div slot="content">

      <!--DIALOG-->
      <template>

        <!--NOVO USUARIO-->
        <create-user ref="createUserModal" @criada='loadDataGrupos()'/>

        <!--NOVO GRUPO DE USUSARIO-->
        <q-dialog v-model="createModal">
          <q-card style="width: 400px; max-width: 80vw;">
            <q-toolbar>
              <q-toolbar-title><span class="text-weight-bold">Novo grupo de usuário</span></q-toolbar-title>
            </q-toolbar>

            <q-card-section>
              <form @submit.prevent="createGrupoUsuario()">
                <q-input type="text" v-model="dataGrupousuario.grupousuario" label="Grupo" :rules="[val => !!val || 'Preencha o campo']"/>
              </form>
            </q-card-section>
            <q-card-actions>
              <q-btn v-close-popup :tabindex="-1">Cancelar</q-btn>
              <q-btn @click.prevent="createGrupoUsuario" color="primary">Salvar</q-btn>
            </q-card-actions>
          </q-card>
        </q-dialog>

        <!--EDITAR GRUPO DE USUSARIO-->
        <q-dialog v-model="updateModal">
          <q-card >
            <q-toolbar>
              <q-toolbar-title><span class="text-weight-bold">Editar grupo de usuário</span></q-toolbar-title>
            </q-toolbar>

            <q-card-section>
              <form @submit.prevent="updateGrupoUsuario()">
                <q-input type="text" v-model="grupousuario.grupousuario" label="Grupo" :rules="[val => !!val || 'Preencha o campo']"/>
              </form>
            </q-card-section>
            <q-card-actions>
              <q-btn v-close-popup :tabindex="-1">Cancelar</q-btn>
              <q-btn @click.prevent="updateGrupoUsuario" color="primary">Salvar</q-btn>
            </q-card-actions>
          </q-card>
        </q-dialog>
      </template>

      <q-card v-if="grupousuario.inativo">
        <q-card-section>
          <span class="text-red">
            Inativo desde {{ moment(grupousuario.inativo).format('L') }}
          </span>
        </q-card-section>
      </q-card>

      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll :handler="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="usuario in data">

            <!-- Link para detalhes -->
            <q-item :to="'/usuario/' + usuario.codusuario">

              <!-- Imagem -->
              <q-item-section avatar>
                <q-avatar v-if="usuario.imagem">
                  <img :src="usuario.imagem.url">
                </q-avatar>
                <q-icon color="primary" name="account_circle" v-if="!usuario.imagem"/>
              </q-item-section>

              <q-item-section>
                <q-item-label>
                  {{ usuario.usuario }}
                  <q-chip tag square pointing="left" color="negative" v-if="usuario.inativo">Inativo</q-chip>
                </q-item-label>
                <q-item-label caption>
                  <span v-for="grupo in usuario.grupos">
                    {{ grupo.grupousuario }}: {{ grupo.filial }},
                  </span>
                </q-item-label>

              </q-item-section>

            </q-item>
            <q-separator/>
          </template>
        </q-infinite-scroll>
      </q-list>

      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky corner="bottom-right" :offset="[90, 18]" v-if="grupousuario">
        <q-fab color="primary" active-icon="edit" icon="edit" direction="up" class="animate-pop">
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
          <q-fab-action color="primary" @click.native="createUser()" icon="account_circle">
            <q-tooltip anchor="center left" self="center right" :offset="[10, 0]">Novo Usuário</q-tooltip>
          </q-fab-action>

          <q-fab-action color="primary" @click.native="createModal = true" icon="supervisor_account">
            <q-tooltip anchor="center left" self="center right" :offset="[10, 0]">Novo Grupo</q-tooltip>
          </q-fab-action>

        </q-fab>

      </q-page-sticky>
    </div>

    <div slot="mgfooter" v-if="grupousuario">
      <mg-autor :data="grupousuario"/>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import MgNoData from '../../utils/MgNoData'
import CreateUser from './components/CreateUser'

import { debounce } from 'quasar'

export default {
  name: 'grupo-usuario',
  components: {
    MgLayout,
    MgAutor,
    MgNoData,
    CreateUser,
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
        this.page = 1;
        this.loadData(false, null);
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
      this.page++;
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {
      // salva no Vuex filtro da marca
      this.$store.commit('filtroUsuario/updateFiltroUsuario', this.filter);

      // inicializa variaveis
      var vm = this;
      var params = this.filter;
      params.page = this.page;
      params.sort = 'usuario';
      params.fields = 'usuario,codusuario,inativo,codimagem';
      this.loading = true;

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
        this.loading = false;

        // Executa done do scroll infinito
        if (done) {
          done()
        }
      })
    }, 500),

    createUser: function(){
      console.log('entrou');
      this.$refs.createUserModal.add()
    },

    createGrupoUsuario: function () {
      let vm = this;
      vm.$axios.post('grupo-usuario', vm.dataGrupousuario).then( response => {
        vm.$q.notify({
          message: 'Novo grupo inserido',
          type: 'positive',
        });
        vm.dataGrupousuario.grupousuario = null;
        vm.erros = false;
        vm.createModal = false;
        vm.loadDataGrupos()
      }).catch(function (error) {
        vm.erros = error.response.data.errors;
      })
    },

    updateGrupoUsuario: function () {
      let vm = this;
        vm.$axios.put('grupo-usuario/' + vm.grupousuario.codgrupousuario, { 'grupousuario': vm.grupousuario.grupousuario }).then(function (request) {
          vm.$q.notify({
            message: 'Grupo atualizado',
            type: 'positive',
          });
          vm.dataGrupousuario.grupousuario = null;
          vm.erros = false;
          vm.updateModal = false;
          vm.loadDataGrupos()
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
    },

    activate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: true,
        persistent: true,
        title: 'Ativar',
        message: 'Tem certeza que deseja ativar?'
      }).onOk(() => {
        vm.$axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
          vm.loadDataGrupo(vm.grupousuario.codgrupousuario);
          vm.$q.notify({
            message: 'Grupo ativado',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error.response)
        })
      }).onCancel(() => {});
    },

    inactivate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: true,
        persistent: true,
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?'
      }).onOk(() => {
        vm.$axios.post('grupo-usuario/' + vm.grupousuario.codgrupousuario + '/inativo').then(function (request) {
          vm.loadDataGrupo(vm.grupousuario.codgrupousuario);
          vm.$q.notify({
            message: 'Grupo inativado',
            type: 'positive',
          })
        }).catch(function (error) {
          console.log(error.response)
        })
      }).onCancel(() => {});
    },

    destroy: function () {
      let vm = this;
      this.$q.dialog({
        cancel: true,
        persistent: true,
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?'
      }).onOk(() => {
        vm.$axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario).then(function (request) {
          vm.$q.notify({
            message: 'Registro excluido',
            type: 'positive',
          });
          vm.filter.grupo = null;
          vm.loadDataGrupos();
          vm.grupousuario = false
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    },

    refresher (index, done) {
      this.page++;
      this.loadData(true, done)
    },

    loadDataGrupo: function (id) {
      let vm = this;
      vm.$axios.get('grupo-usuario/' + id + '/detalhes').then(function (request) {
        vm.grupousuario = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },

    loadDataGrupos: function () {
      let vm = this;
      let params = {
        sort: 'grupousuario'
      };
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
    this.filter = this.$store.state.filtroUsuario;
    this.loadDataGrupos()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>

</style>
