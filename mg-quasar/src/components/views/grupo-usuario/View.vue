<template>
  <mg-layout>

    <button slot="menu" v-link=" '/grupo-usuario' ">
      <i>arrow_back</i>
    </button>

    <template slot="title">
      {{ dados.grupousuario }}
    </template>

    <div slot="content">
      <div class="card-content">
        <div class="card" v-if="dados.inativo">
          <div class="card-content text-red">
            Inativo desde {{ moment(dados.inativo).format('L') }}
          </div>
        </div>

        <div class="row gutter">
          <div class="width-1of3">
            <div class="card">
              <div class="card-content">
                <dl>
                    <dt>#</dt>
                    <dd>{{ dados.codgrupousuario }}</dd>
                    <dt>Grupo</dt>
                    <dd>{{ dados.grupousuario }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="width-1of3">
            <div class="card">
              <div class="card-title bg-light">
                Usuários
              </div>
              <div class="list no-border">
                <div class="item item-link two-lines" v-for="usuario in dados.Usuarios" v-link="'/usuario/' + usuario.codusuario">
                  <!-- <q-drawer-link :to="{ path: '/usuario/' + usuario.codusuario }"> -->
                    <div class="item-content has-secondary">
                      <div>{{ usuario.usuario }}</div>
                      <div>{{ usuario.filial.filial }}</div>
                    </div>
                  <!-- </q-drawer-link> -->
                </div>
                <div class="item two-lines"  v-if="dados.Usuarios.length === 0">
                    <div class="item-content has-secondary">
                      <div>Nenhum usuário</div>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <div class="width-1of3">
            <div class="card">
              <div class="card-title">
                Permissões
              </div>
              <div class="card-content">
                Card Content
              </div>
            </div>
          </div>
        </div>
      </div>

      <q-fab
        icon="edit"
        direction="up"
        class="primary circular absolute-bottom-right"
      >
        <router-link :to="{ path: '/grupo-usuario/' + dados.codgrupousuario + '/update'}">
          <q-small-fab class="primary" icon="edit" ></q-small-fab>
        </router-link>
        <q-small-fab class="orange" @click.native="activate()" icon="thumb_up" v-if="dados.inativo"></q-small-fab>
        <q-small-fab class="orange" @click.native="inactivate()" icon="thumb_down" v-else></q-small-fab>
        <q-small-fab class="red" @click.native="destroy()" icon="delete"></q-small-fab>
      </q-fab>
    </div>

  </mg-layout>
</template>

<script>
import { Dialog, Toast } from 'quasar'
import MgLayout from '../../layouts/MgLayout'

export default {
  name: 'grupo-usuario-view',
  components: {
    MgLayout
  },
  data () {
    return {
      dados: []
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + id + '/details').then(function (request) {
        vm.dados = request.data
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
              window.axios.delete('grupo-usuario/' + vm.dados.codgrupousuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.dados.codgrupousuario)
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
              window.axios.post('grupo-usuario/' + vm.dados.codgrupousuario + '/inativo').then(function (request) {
                vm.carregaDados(vm.dados.codgrupousuario)
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
              window.axios.delete('grupo-usuario/' + vm.dados.codgrupousuario).then(function (request) {
                vm.$router.push('/grupo-usuario')
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
