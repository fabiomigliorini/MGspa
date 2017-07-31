<template>
  <mg-layout>

    <q-btn flat icon="arrow_back" slot="menu" />

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
              <q-select
                stack-label="Filial"
                v-model="data.codfilial"
                :options="filiais"
              />
            </div>
          </div>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-input v-model="data.codpessoa" placeholder="Pessoa">
                <q-autocomplete
                  @search="searchPessoa"
                  :min-characters="2"
                  @selected=""
                />
              </q-input>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-select
              stack-label="Impressora Matricial"
              v-model="data.impressoramatricial"
              :options="impressoras"
              />
            </div>
          </div>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-select
              stack-label="Impressora Térmica"
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
      filiais: [
        {
          label: 'Filial',
          value: null
        },
        {
          label: 'Google',
          value: 1
        },
        {
          label: 'Facebook',
          value: 2
        },
        {
          label: 'Twitter',
          value: 3
        },
        {
          label: 'Apple Inc.',
          value: 4
        },
        {
          label: 'Oracle',
          value: 5
        }
      ],
      erros: false
    }
  },
  methods: {
    searchPessoa: function () {
      console.log()
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
      window.axios.get('filial').then(function (request) {
        vm.filiais = request.data
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
                // vm.$router.push('/usuario/' + request.data.codgrupousuario)
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
    // this.carregaFiliais()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
