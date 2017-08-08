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
              <q-select
                float-label="Filial"
                v-model="data.codfilial"
                :options="filiais"
              />
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-search v-model="terms" placeholder="Pessoa">
                <q-autocomplete @search="pessoaSearch" @selected="pessoaSelected" :min-characters="3" :debounce="600"/>
              </q-search>
            </div>
          </div>

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
  QSideLink,
  Dialog,
  Toast,
  QField,
  QBtn,
  QInput,
  QSelect,
  QSearch,
  QAutocomplete
} from 'quasar'

import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

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
    QSearch,
    QAutocomplete
  },
  data () {
    return {
      data: {
        usuario: null,
        codfilial: null,
        codpessoa: null,
        impressoramatricial: null,
        impressoratermica: null
      },
      impressoras: [],
      filiais: [],
      terms: '',
      erros: false
    }
  },
  watch: {
    terms: {
      handler: function (val, oldVal) {
        if (val.length === 0) {
          this.data.codpessoa = null
        }
      }
    }
  },
  methods: {
    pessoaSelected (item) {
      let vm = this
      vm.data.codpessoa = item.id
    },
    pessoaSearch (terms, done) {
      let params = {}
      params.sort = 'fantasia'
      params.pessoa = terms
      window.axios.get('pessoa/autocomplete', { params }).then(response => {
        let results = response.data
        done(results)
      }).catch(function (error) {
        done([])
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
  },
  mounted () {
    this.carregaImpressoras()
    this.carregaFiliais()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
