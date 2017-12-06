<template>
  <mg-layout>

    <q-side-link :to="'/usuario/' + data.codusuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">
        <div class="row">
          <div class="col-md-4">
            <q-list multiline link>
              <q-item to="impressoras">
                <q-item-main>
                  <q-item-tile label>Impressoras</q-item-tile>
                  <q-item-tile sublabel>Alterar impressoras</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="print" color="" />
                </q-item-side>
              </q-item>
              <q-item to="senha">
                <q-item-main>
                  <q-item-tile label>Senha</q-item-tile>
                  <q-item-tile sublabel>Trocar senha do sistema</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="vpn_key" color="" />
                </q-item-side>
              </q-item>
              <q-item to="foto">
                <q-item-main>
                  <q-item-tile label>Foto</q-item-tile>
                  <q-item-tile sublabel>Cadastrar/Alterar foto</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="account_box" color="" />
                </q-item-side>
              </q-item>
            </q-list>
          </div>
        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
  Dialog,
  Toast,
  QBtn,
  QField,
  QInput,
  QSelect,
  QSideLink,
  QUploader,
  QCardMedia,
  QCard,
  QCardMain,
  QCardTitle,
  QList,
  QListHeader,
  QItem,
  QItemSeparator,
  QItemSide,
  QItemMain,
  QItemTile
} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput,
    QSelect,
    QSideLink,
    QUploader,
    QCardMedia,
    QCard,
    QCardMain,
    QCardTitle,
    QList,
    QListHeader,
    QItem,
    QItemSeparator,
    QItemSide,
    QItemMain,
    QItemTile
  },
  data () {
    return {
      data: {},
      erros: false
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      window.axios.get('usuario/' + id + '/details').then(function (request) {
        vm.data = request.data
        vm.data.senha = ''
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      var vm = this
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
              window.axios.put('usuario/' + vm.data.codusuario, vm.data).then(function (request) {
                Toast.create.positive('Registro atualizado')
                vm.$router.push('/usuario/' + request.data.codusuario)
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    }
  },
  created () {
    this.loadData(localStorage.getItem('auth.codusuario'))
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
