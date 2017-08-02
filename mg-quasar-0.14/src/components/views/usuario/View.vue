<template>
  <mg-layout>

    <q-side-link to="/usuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <template slot="title">
      {{ item.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">

        <q-card v-if="item.inativo">
          <q-card-main>
            <span class="text-red">
              Inativo desde {{ moment(item.inativo).format('L') }}
            </span>
          </q-card-main>
        </q-card>

        <div class="row">
          <div class="col-md-4">
            <q-card>
              <q-card-main>
                <dl>
                  <dt>#</dt>
                  <dd>{{ item.codusuario }}</dd>
                  <dt>Usuário</dt>
                  <dd>{{ item.usuario }}</dd>
                  <dt>Filial</dt>
                  <dd>{{ item.filial.filial }}</dd>
                  <dt>Pessoa</dt>
                  <dd>{{ item.pessoa.pessoa }}</dd>
                  <dt>Impressora Matricial</dt>
                  <dd>{{ item.impressoramatricial }}</dd>
                  <dt>Impressora Térmica</dt>
                  <dd>{{ item.impressoratermica }}</dd>
                  <dt>Último acesso</dt>
                  <dd>{{ moment(item.ultimoacesso).format('LLLL') }}</dd>
                </dl>
              </q-card-main>
            </q-card>
          </div>
          <div class="col-md-4">
            <q-card>
              <q-card-title>
                Grupos
              </q-card-title>
              <q-card-main>
                <dl>
                  <template v-for="grupo in item.grupos">
                    <dt>{{ grupo.grupousuario }}</dt>
                    <dd>{{ grupo.filiais.toString() }}</dd>
                  </template>
                </dl>
              </q-card-main>
            </q-card>
          </div>
          <div class="col-md-4">
            <q-card>
              <q-card-title>
                Permissões
              </q-card-title>
              <q-card-main>
                <dl>
                  <template v-for="permissao in item.permissoes">
                    <dt>{{ permissao.grupousuario }}</dt>
                    <dd>{{ permissao.filiais.toString() }}</dd>
                  </template>
                </dl>
              </q-card-main>
            </q-card>
          </div>
        </div>

      </div>

      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          active-icon="add"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/usuario/' + item.codusuario + '/edit' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="orange" @click.native="activate()" icon="thumb_up" v-if="item.inativo">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Ativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="orange" @click.native="inactivate()" icon="thumb_down" v-else>
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Inativar</q-tooltip>
          </q-fab-action>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-fixed-position>

    </div>

    <div slot="footer">
      <mg-autor
        :data="item"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>
import {
  Dialog,
  Toast,
  QFixedPosition,
  QBtn,
  QFab,
  QFabAction,
  QTooltip,
  QSideLink,
  QCard,
  QCardMain,
  QCardTitle
 } from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'

export default {
  name: 'usuario-view',
  components: {
    MgLayout,
    MgAutor,
    QFixedPosition,
    QBtn,
    QFab,
    QFabAction,
    QTooltip,
    QSideLink,
    QCard,
    QCardMain,
    QCardTitle
  },
  data () {
    return {
      item: []
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.item = request.data
      }).catch(function (error) {
        console.log(error.response)
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
              window.axios.delete('usuario/' + vm.item.codusuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.item.codusuario)
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
              window.axios.post('usuario/' + vm.item.codusuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.item.codusuario)
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
              window.axios.delete('usuario/' + vm.item.codusuario).then(function (request) {
                vm.$router.push('/usuario')
                Toast.create.positive('Registro excluído')
              }).catch(function (error) {
                console.log(error)
              })
            }
          }
        ]
      })
    }
  },
  created () {
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
dl {
  margin: 0;
}
dt {
  color: rgba(0,0,0,0.54);
}
dd {
  margin-bottom: 16px;
}
dd:last-child {
  margin-bottom: 0;
}
</style>
