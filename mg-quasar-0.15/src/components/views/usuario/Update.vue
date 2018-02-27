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
              <mg-select-filial
                label="Filial"
                v-model="data.codfilial">
              </mg-select-filial>
              <mg-erros-validacao :erros="erros.codfilial"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <mg-autocomplete-pessoa placeholder="Pessoa" v-model="data.codpessoa" :init="data.codpessoa"></mg-autocomplete-pessoa>
              <mg-erros-validacao :erros="erros.codpessoa"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <mg-select-impressora
                label="Impressora Matricial"
                v-model="data.impressoramatricial">
              </mg-select-impressora>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <mg-select-impressora
                label="Impressora Térmica"
                v-model="data.impressoratermica">
              </mg-select-impressora>
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
  
  QBtn,
  QField,
  QInput,
  QSelect,
  QSearch,
  QAutocomplete,
  QSideLink
} from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgSelectImpressora from '../../utils/select/MgSelectImpressora'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectFilial from '../../utils/select/MgSelectFilial'

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
    
    MgAutocompletePessoa,
    MgSelectImpressora,
    MgSelectFilial
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
      let params = {
        fields: ['codusuario', 'usuario', 'codfilial', 'impressoratermica', 'impressoramatricial', 'codpessoa']
      }
      window.axios.get('usuario/' + id, { params }).then(function (request) {
        vm.data = request.data
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
                Notify.create.positive('Registro atualizado')
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
    this.loadData(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
