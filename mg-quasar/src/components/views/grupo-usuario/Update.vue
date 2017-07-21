<template>
  <mg-layout>

    <button slot="menu" v-link=" '/grupo-usuario/' + dados.codgrupousuario ">
      <i>arrow_back</i>
    </button>

    <button slot="rightMenu" @click.prevent="update()">
      <i>done</i>
    </button>

    <template slot="title">
      {{ dados.grupousuario }}
    </template>

    <div slot="content">

      <form @submit.prevent="update()">
        <div class="item">
          <div class="item-content row">
            <div class="width-1of3">
              <div class="floating-label">
                <input required class="full-width" v-model="dados.grupousuario"  v-bind:class="{ 'has-error': erros.grupousuario }">
                <label>Descrição</label>
              </div>
              <mg-erros-validacao :erros="erros.grupousuario"></mg-erros-validacao>
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
import MgErrosValidacao from '../../partials/MgErrosValidacao'

export default {
  name: 'grupo-usuario-update',
  components: {
    MgLayout, MgErrosValidacao
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
    carregaDados: function (id) {
      let vm = this
      window.axios.get('grupo-usuario/' + id).then(function (request) {
        vm.dados = request.data
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
              window.axios.put('grupo-usuario/' + vm.dados.codgrupousuario, vm.dados).then(function (request) {
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
    this.carregaDados(this.$route.params.id)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
