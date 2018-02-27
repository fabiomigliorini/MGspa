<template>
  <mg-layout>

    <q-side-link to="/usuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <template slot="title">
      {{ item.usuario }}
    </template>

    <div slot="content">
      <div class="row">
        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Dados
              <span slot="subtitle">Dados do usuário</span>
              <q-icon slot="right" name="account_circle" />
            </q-card-title>
            <q-card-main>
              <dl>
                <dt>#</dt>
                <dd>{{ numeral(item.codusuario).format('00000000') }}</dd>
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
                <dt>Ativo</dt>
                <dd>
                  <span class="text-red" v-if="item.inativo">Inativo desde {{ moment(item.inativo).format('L') }}</span>
                  <span v-else>Sim</span>
                </dd>
              </dl>
            </q-card-main>
            <q-card-separator />
            <q-card-actions>
              <q-btn color="primary" flat @click.native="activate()" v-if="item.inativo">Ativar</q-btn>
              <q-btn color="red" flat @click.native="inactivate()" v-else>Inativar</q-btn>
            </q-card-actions>

          </q-card>
        </div>
        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Grupos
              <span slot="subtitle">Grupos do usuário</span>
              <q-icon slot="right" name="supervisor_account" />
            </q-card-title>
            <q-card-main>
              <dl>
                <template v-for="grupo in item.grupos">
                  <dt>{{ grupo.grupousuario }}</dt>
                  <dd>{{ grupo.filiais.toString() }}</dd>
                </template>
              </dl>
            </q-card-main>
            <q-card-separator />
            <q-card-actions>
              <router-link :to="{ path: '/usuario/' + item.codusuario + '/grupos' }">
                <q-btn flat>Grupos</q-btn>
              </router-link>
            </q-card-actions>
          </q-card>
        </div>
        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Permissões
              <span slot="subtitle">Permissões do usuário</span>
              <q-icon slot="right" name="lock_open" />
            </q-card-title>
            <q-list separator>
              <template v-for="(permissao, index) in item.permissoes">
                  <q-collapsible :label="index">
                    <div v-for="item in permissao">
                      {{ item }}
                    </div>
                  </q-collapsible>
              </template>
            </q-list>
          </q-card>
        </div>
      </div>

      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="edit"
          active-icon="edit"
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
  
  QFixedPosition,
  QBtn,
  QIcon,
  QFab,
  QFabAction,
  QTooltip,
  
  QCard,
  QList,
  QCardMain,
  QCardTitle,
  QCardSeparator,
  QCardActions,
  QCollapsible
} from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'

export default {
  name: 'usuario-view',
  components: {
    MgLayout,
    MgAutor,
    QFixedPosition,
    QBtn,
    QIcon,
    QFab,
    QFabAction,
    QTooltip,
    
    QCard,
    QList,
    QCardMain,
    QCardTitle,
    QCardSeparator,
    QCardActions,
    QCollapsible
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
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.item = request.data
        console.log(vm.item)
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
                Notify.create.positive('Registro ativado')
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
                Notify.create.positive('Registro inativado')
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
                Notify.create.positive('Registro excluído')
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
<style scoped>

.q-collapsible-sub-item div {
  margin: 5px 0;
}
</style>
