<template>
  <mg-layout>

    <q-side-link :to="'/usuario/' + data.codusuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">
        <form @submit.prevent="update()">
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-input
                type="text"
                v-model="data.usuario"
                float-label="Usuário"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.usuario"></mg-erros-validacao>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-select
                float-label="Filial"
                v-model="data.codfilial"
                :options="filiais"
              />
            </div>
          </div>
          <!--
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-search v-model="data.codpessoa" placeholder="Pessoa">
                <q-autocomplete @search="search" />
              </q-search>
            </div>
          </div>
          -->
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-select
              float-label="Impressora Matricial"
              v-model="data.impressoramatricial"
              :options="impressoras"
              />
            </div>
          </div>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-select
              float-label="Impressora Térmica"
              v-model="data.impressoratermica"
              :options="impressoras"
              />
            </div>
          </div>
        </form>
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
  QSearch,
  QAutocomplete,
  QSideLink
} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-update',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput,
    QSelect,
    QSearch,
    QAutocomplete,
    QSideLink
  },
  data () {
    return {
      data: {
        usuario: ''
      },
      impressoras: [],
      filiais: [],
      erros: false
    }
  },
  methods: {
    carregaDados: function (id) {
      let vm = this
      window.axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaImpressoras: function () {
      let vm = this
      window.axios.get('usuario/impressoras').then(function (request) {
        vm.impressoras = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaFiliais: function () {
      let vm = this
      window.axios.get('filial', { params: { fields: 'codfilial,filial' } }).then(function (request) {
        vm.filiais = request.data.data.map(filial => {
          return {
            value: filial.codfilial,
            label: filial.filial
          }
        })
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
  mounted () {
    this.carregaDados(this.$route.params.id)
    this.carregaImpressoras()
    this.carregaFiliais()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
