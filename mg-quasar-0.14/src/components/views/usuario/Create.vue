<template>
  <mg-layout>

    <q-side-link to="/usuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Novo usuário
    </template>

    <div slot="content">
      <div class="layout-padding">
        <form @submit.prevent="create()">
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
              <q-field>
                <q-input
                  type="password"
                  v-model="data.senha"
                  float-label="Senha"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.senha"></mg-erros-validacao>
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
              <mg-autocomplete-pessoa placeholder="Pessoa" v-on:seleciona="pessoa"></mg-autocomplete-pessoa>
              <mg-erros-validacao :erros="erros.codpessoa"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <mg-select-impressora
                label="Impressora Matricial"
                v-model="data.impressoramatricial">
              </mg-select-impressora>
              <mg-erros-validacao :erros="erros.impressoramatricial"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <mg-select-impressora
                label="Impressora Térmica"
                v-model="data.impressoratermica">
              </mg-select-impressora>
              <mg-erros-validacao :erros="erros.impressoratermica"></mg-erros-validacao>
            </div>
          </div>

        </form>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
  QSideLink,
  Dialog,
  Toast,
  QField,
  QBtn,
  QInput,
  QSelect
} from 'quasar'

import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectImpressora from '../../utils/select/MgSelectImpressora'
import MgSelectFilial from '../../utils/select/MgSelectFilial'

export default {
  name: 'usuario-create',
  components: {
    MgLayout,
    MgErrosValidacao,
    QSideLink,
    QField,
    QBtn,
    QInput,
    QSelect,
    MgAutocompletePessoa,
    MgSelectImpressora,
    MgSelectFilial
  },
  data () {
    return {
      data: {
        usuario: null,
        senha: null,
        codfilial: null,
        codpessoa: null,
        impressoramatricial: null,
        impressoratermica: null
      },
      erros: false
    }
  },
  methods: {
    pessoa (value) {
      let vm = this
      vm.data.codpessoa = value
    },
    create: function () {
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
              window.axios.post('usuario', vm.data).then(function (request) {
                Toast.create.positive('Registro inserido')
                vm.$router.push('/usuario/' + request.data.codusuario)
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
