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
        <table class="q-table striped-odd">
          <thead>
            <tr>
              <th>
                &nbsp;
              </th>
                <th v-for="grupo in grupos">
                    {{ grupo.grupousuario }}
                </th>
            </tr>
          </thead>
          <tbody>
              <tr v-for="filial in filiais">
                <th class="text-left">
                  {{ filial.filial }}
                </th>
                  <td class="text-center" v-for="item in grupos">
                    <q-btn @click.prevent="removeGrupo(filial.codfilial, item.codgrupousuario)" flat round small class="text-positive" icon="check_box" v-if="grupousuario[item.codgrupousuario] && grupousuario[item.codgrupousuario][filial.codfilial]"></q-btn>
                    <q-btn @click.prevent="adicionaGrupo(filial.codfilial, item.codgrupousuario)" flat round small class="text-grey" icon="check_box_outline_blank" v-else></q-btn>
                  </td>
              </tr>
          </tbody>
        </table>

      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
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
  name: 'usuario-grupos',
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
      data: {},
      grupos: null,
      filiais: null,
      grupousuario: null
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
      window.axios.get('usuario/' + id + '/grupos').then(function (request) {
        vm.grupousuario = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaFiliais: function () {
      let vm = this
      let params = {
        sort: 'filial',
        fields: 'codfilial,filial'
      }
      window.axios.get('filial', { params }).then(function (request) {
        vm.filiais = request.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaGrupos: function () {
      let vm = this
      let params = {
        sort: 'grupousuario',
        fields: 'grupousuario,codgrupousuario'
      }
      window.axios.get('grupo-usuario', { params }).then(function (response) {
        vm.grupos = response.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    adicionaGrupo (codfilial, codgrupousuario) {
      var vm = this
      var dados = {
        codfilial: codfilial,
        codgrupousuario: codgrupousuario
      }
      window.axios.post('usuario/' + vm.data.codusuario + '/grupos', dados).then(function (request) {
        if (request.status === 201) {
          vm.carregaDados(vm.data.codusuario)
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    removeGrupo (codfilial, codgrupousuario) {
      var vm = this
      var dados = {
        codfilial: codfilial,
        codgrupousuario: codgrupousuario
      }
      window.axios.delete('usuario/' + vm.data.codusuario + '/grupos', { params: dados }).then(function (request) {
        if (request.status === 204) {
          vm.carregaDados(vm.data.codusuario)
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    }

  },
  created () {
    this.carregaDados(this.$route.params.id)
    this.carregaGrupos()
    this.carregaFiliais()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
