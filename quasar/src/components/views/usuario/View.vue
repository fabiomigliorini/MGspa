<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/usuario')">
      <q-icon name="arrow_back" />
    </q-btn>

    <template slot="title">
      {{ item.usuario }}
    </template>

    <div slot="content">
      <div class="row q-pa-sm q-col-gutter-sm">
        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
          <q-card>
            <q-card-section :class="(!item.inativo)?'text-h6 bg-positive text-white':'text-h6 bg-negative text-white' ">
              Usuário
              <span v-if="!item.inativo">Ativo</span>
              <span v-if="item.inativo">Inativo</span>
              <q-icon name="account_circle" class="float-right" size="30px"/>
            </q-card-section>
            <q-separator/>

            <q-card-section>

              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label caption>
                    {{ item.usuario }} # {{ numeral(item.codusuario).format('00000000') }}
                  </q-item-label>
                  <q-item-label v-if="item.pessoa">
                    {{ item.pessoa.pessoa }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label caption>Filial</q-item-label>
                  <q-item-label>{{ item.filial.filial }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label caption>Impressora Matricial</q-item-label>
                  <q-item-label>{{ item.impressoramatricial }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label caption>Impressora Térmica</q-item-label>
                  <q-item-label>{{ item.impressoratermica }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none">
                <q-item-section>
                  <q-item-label caption>
                    Último acesso
                  </q-item-label>
                  <q-item-label>
                    {{ moment(item.ultimoacesso).format('LLLL') }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item class="q-px-none" v-if="item.inativo">
                <q-item-section>
                  <q-item-label caption>
                    Inativo desde
                  </q-item-label>
                  <q-item-label>
                    {{ moment(item.inativo).format('L') }}
                  </q-item-label>
                </q-item-section>
              </q-item>

            </q-card-section>

            <q-card-actions>
              <q-btn color="primary" flat @click.native="activate()" v-if="item.inativo">Ativar</q-btn>
              <q-btn color="red" flat @click.native="inactivate()" v-else>Inativar</q-btn>
            </q-card-actions>

          </q-card>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
          <q-card>
            <q-card-section class="text-h6">
              Grupos do usuário
              <q-icon  name="supervisor_account" class="float-right" size="30px"/>
            </q-card-section>
            <q-separator/>

            <q-card-section>

              <q-list separator highlight	>
                <q-item v-for="grupo in item.grupos" class="q-px-none" :key="grupo.id">
                  <q-item-section>
                    <q-item-label caption>
                      {{ grupo.grupousuario }}
                    </q-item-label>
                    <q-item-label>
                      {{ grupo.filiais.toString() }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>

            </q-card-section>

            <q-card-actions>
              <router-link :to="{ path: '/usuario/' + item.codusuario + '/grupos' }">
                <q-btn flat>Grupos</q-btn>
              </router-link>
            </q-card-actions>

          </q-card>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
          <q-card>
            <q-card-section class="text-h6">
              Permissões do usuário
              <q-icon name="lock_open" class="float-right" size="30px"/>
            </q-card-section>
            <q-separator/>

            <q-card-section>
              <q-list separator>
                <template v-for="(itens, permissao) in item.permissoes">
                  <q-expansion-item expand-separator :label="permissao" :key="permissao" dense dense-toggle>
                    <q-card>
                      <q-card-section v-for="(item, index) in itens" :key="permissao + '_' + index">
                        {{ item }}
                      </q-card-section>
                    </q-card>
                  </q-expansion-item>
                </template>
              </q-list>
            </q-card-section>


          </q-card>
        </div>
      </div>

      <q-page-sticky corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="add"
          active-icon="add"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/usuario/' + item.codusuario + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-page-sticky>

    </div>
<!--
    <q-toolbar slot="teste">
     <q-toolbar-title>
       <mg-autor :data="item"></mg-autor>
     </q-toolbar-title>
   </q-toolbar>
 -->

     <q-toolbar-title slot="footer">
       <mg-autor :data="item"></mg-autor>
     </q-toolbar-title>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'

export default {
  name: 'usuario-view',
  components: {
    MgLayout,
    MgAutor
  },
  data () {
    return {
      item: {
        filial: {
          filial: null
        },
        pessoa: {
          pessoa: null
        }
      }
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this;
      vm.$axios.get('usuario/' + id + '/detalhes').then(function (request) {
        vm.item = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    activate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Ativar',
        message: 'Tem certeza que deseja Ativar?'
      }).onOk(() => {
        vm.$axios.delete('usuario/' + vm.item.codusuario + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro ativado',
            color: 'positive',
          });
          vm.carregaDados(vm.item.codusuario)
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    },
    inactivate: function () {
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?'
      }).onOk(() => {
        vm.$axios.post('usuario/' + vm.item.codusuario + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Registro inativado',
            color: 'positive',
          });
          vm.carregaDados(vm.item.codusuario)
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    },
    destroy: function () {
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?'
      }).onOk(() => {
        vm.$axios.delete('usuario/' + vm.item.codusuario).then(function (request) {
          vm.$q.notify({
            message: 'Registro excluido',
            color: 'positive',
          });
          vm.$router.push('/usuario')
        }).catch(function (error) {
          console.log(error)
        })
      }).onCancel(() => {});
    }
  },
  mounted () {
    this.carregaDados(this.$route.params.id)
  }
}
</script>
<style scoped>

.q-collapsible-sub-item div {
  margin: 5px 0;
}
</style>
