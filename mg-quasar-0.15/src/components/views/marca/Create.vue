<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/marca')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Nova marca
    </template>

    <div slot="content">

      <div class="layout-padding">
        <form @submit.prevent="create()">

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-input
                  type="text"
                  v-model="data.marca"
                  float-label="Marca"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.marca"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-toggle v-model="data.abcignorar" label="Ignorar curva ABC" />
              </q-field>
              <mg-erros-validacao :erros="erros.abcignorar"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-toggle v-model="data.controlada" label="Controlada" />
              </q-field>
              <mg-erros-validacao :erros="erros.controlada"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-toggle v-model="data.site" label="Disponível no site" />
              </q-field>
              <mg-erros-validacao :erros="erros.descricao"></mg-erros-validacao>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-input
                  v-model="data.descricaosite"
                  type="textarea"
                  float-label="Descrição do site"
                  :max-height="100"
                  :min-rows="3"
                />
              </q-field>
              <mg-erros-validacao :erros="erros.descricao"></mg-erros-validacao>
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
  QField,
  QBtn,
  QInput,
  QToggle
} from 'quasar'

import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-create',
  components: {
    MgLayout,
    MgErrosValidacao,
    QField,
    QBtn,
    QInput,
    QToggle
  },
  data () {
    return {
      data: {
        marca: null,
        descricaosite: null,
        // codopencart: null,
        site: false,
        abcignorar: false,
        controlada: false
      },
      erros: false
    }
  },
  methods: {
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
              vm.$axios.post('marca', vm.data).then(function (request) {
                Notify.create.positive('Registro inserido')
                vm.$router.push('/marca/' + request.data.codmarca)
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
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
