<template>
  <mg-layout>

    <button slot="menu" v-link=" '/grupo-usuario' ">
      <i>arrow_back</i>
    </button>

    <button slot="rightMenu" @click.prevent="create()">
      <i>done</i>
    </button>

    <template slot="title">
      Novo Grupos de usuário
    </template>

    <div slot="content">

      <form @submit.prevent="create()">
        <div class="item">
          <div class="item-content row">
            <div class="width-1of3">
              <div class="floating-label">
                <input required class="full-width" v-model="dados.grupousuario"  v-bind:class="{ 'has-error': erros.grupousuario }">
                <label>Descrição</label>
              </div>
              <erros-validacao :erros="erros.grupousuario"></erros-validacao>
            </div>
          </div>
        </div>
      </form>

    </div>

  </mg-layout>
</template>

<script>
import { Dialog, Toast } from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import ErrosValidacao from '../../errors/ErrosValidacao'

export default {
  name: 'grupo-usuario-create',
  components: {
    MgLayout, ErrosValidacao
  },
  data () {
    return {
      dados: {
        grupousuario: ''
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
              window.axios.post('grupo-usuario', vm.dados).then(function (request) {
                Toast.create.positive('Registro inserido')
                vm.$router.push('/grupo-usuario/' + request.data.codgrupousuario)
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
