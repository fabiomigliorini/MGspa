<template>
  <mg-layout>

    <q-side-link to="/usuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">

        <q-card v-if="data.inativo">
          <q-card-main>
            <span class="text-red">
              Inativo desde {{ moment(data.inativo).format('L') }}
            </span>
          </q-card-main>
        </q-card>

        <div class="row gutter">
          <div class="width-1of3">
            <div class="card">
              <div class="card-content">
                <dl>
                    <dt>#</dt>
                    <dd>{{ data.codusuario }}</dd>
                    <dt>Usuário</dt>
                    <dd>{{ data.usuario }}</dd>
                </dl>
              </div>
            </div>
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
          <router-link :to="{ path: '/usuario/' + data.codusuario + '/edit' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="orange" @click.native="activate()" icon="thumb_up" v-if="data.inativo">
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
        :criacao="data.criacao"
        :usuariocriacao="data.usuariocriacao"
        :alteracao="data.alteracao"
        :usuarioalteracao="data.usuarioalteracao"
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
  QSideLink
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
    QSideLink
  },
  data () {
    return {
      data: []
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.data = request.data
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
              window.axios.delete('usuario/' + vm.data.codusuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.data.codusuario)
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
              window.axios.post('usuario/' + vm.data.codusuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.data.codusuario)
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
              window.axios.delete('usuario/' + vm.data.codusuario).then(function (request) {
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
  mounted () {
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
