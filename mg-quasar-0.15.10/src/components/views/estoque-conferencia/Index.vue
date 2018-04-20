<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="iniciar()" />

    <template slot="title">
      Conferência de estoque
    </template>
    <div slot="content">
      <div class="layout-padding">

        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <mg-select-estoque-local
              label="Local"
              v-model="data.codestoquelocal">
            </mg-select-estoque-local>
            <mg-erros-validacao :erros="erros.codestoquelocal"></mg-erros-validacao>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <q-select
              float-label="Tipo"
              v-model="data.fiscal"
              :options="tipos"
            />
            <mg-erros-validacao :erros="erros.fiscal"></mg-erros-validacao>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <mg-autocomplete-marca placeholder="Marca" v-model="data.codmarca" :init="data.codmarca"></mg-autocomplete-marca>
            <mg-erros-validacao :erros="erros.codmarca"></mg-erros-validacao>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <q-datetime
              format24h
              color="primary"
              v-model="data.data"
              type="datetime"
              float-label="Data"
              clearable
              format="DD/MM/YYYY HH:mm:ss"
             />
            <mg-erros-validacao :erros="erros.data"></mg-erros-validacao>
          </div>
        </div>


      </div>
    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'estoque-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data () {
    return {
      tipos: [
          {
            label: 'Fisico',
            value: 0
          },
          {
            label: 'Fiscal',
            value: 1
          }
      ],
      erros: {}
    }
  },
  computed: {
    data: {
      get () {
        return this.$store.state.estoqueConferencia.estoqueConferenciaState
      }
    }
  },
  methods: {
    iniciar: function () {
      let vm = this
      vm.dadosConferencia(vm.data.codestoquelocal, vm.data.codmarca)

      vm.$router.push('/estoque-conferencia/conferencia')
    },
    dadosConferencia: function (codestoquelocal, codmarca) {
      let vm = this
      let params = {
        fields:['estoquelocal']
      }
      vm.$axios.get('estoque-local/' + codestoquelocal, { params }).then(function (request) {
        vm.data.estoquelocal = request.data.estoquelocal
        params = {
          fields:['marca']
        }
        vm.$axios.get('marca/' + codmarca, { params }).then(function (request) {
          vm.data.marca = request.data.marca
          vm.$store.commit('estoqueConferencia/updateEstoqueConferencia', vm.data)
        }).catch(function (error) {
          console.log(error.response)
        })
      }).catch(function (error) {
        console.log(error.response)
      })

    }
  },
  mounted () {
    console.log(this.data)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
