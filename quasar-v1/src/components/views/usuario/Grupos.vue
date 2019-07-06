<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/usuario/' + data.codusuario)">
      <q-icon name="arrow_back" />
    </q-btn>

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="row q-pa-md">

        <div class="col-12 q-py-md gt-xs">
          <div class="row justify-center text-subtitle1 text-grey-7">
            <div class="col-sm-2 col-md-2 col-lg-1">
              Filial
            </div>
            <div class="col-sm-1 col-md-1 col-lg-1 text-center" v-for="grupo in grupos" :key="grupo.codgrupousuario">
              {{ grupo.grupousuario.substring(0, 3) }}
            </div>
          </div>
          <q-separator/>
        </div>

        <div class="col-12 ">
          <div class="row justify-center" v-for="filial in filiais" :key="filial.codfilial">
            <div class="col-xs-12 col-sm-2 col-md-2 col-lg-1 text-subtitle1">
              {{ filial.filial }}
            </div>

            <div class="col-12 lt-sm">
              <div class="row justify-center text-grey-7">
                <div class="col-xs-2 text-center" v-for="grupo in grupos" :key="grupo.codgrupousuario">
                  {{ grupo.grupousuario.substring(0, 3) }}
                </div>
              </div>
            </div>

            <div class="col-xs-2 col-sm-1 col-md-1 col-lg-1 text-center" v-for="item in grupos" :key="item.codgrupousuario">

              <q-btn @click.prevent="removeGrupo(filial.codfilial, item.codgrupousuario)"
                     flat round small color="positive" icon="check_box"
                     v-if="grupousuario[item.codgrupousuario] && grupousuario[item.codgrupousuario][filial.codfilial]"
              />

              <q-btn @click.prevent="adicionaGrupo(filial.codfilial, item.codgrupousuario)"
                     flat round small color="grey-7" icon="check_box_outline_blank" v-else
              />
            </div>
            <div class="col-12 lt-sm">
              <q-separator/>
            </div>
          </div>
        </div>

      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'

export default {
  name: 'usuario-grupos',
  components: {
    MgLayout,
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
      let vm = this;
      vm.$axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      });
      vm.$axios.get('usuario/' + id + '/grupos').then(function (request) {
        vm.grupousuario = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaFiliais: function () {
      let vm = this;
      let params = {
        sort: 'filial',
        fields: 'codfilial,filial'
      };
      vm.$axios.get('filial', { params }).then(function (request) {
        vm.filiais = request.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    carregaGrupos: function () {
      let vm = this;
      let params = {
        sort: 'grupousuario',
        fields: 'grupousuario,codgrupousuario'
      };
      vm.$axios.get('grupo-usuario', { params }).then(function (response) {
        vm.grupos = response.data.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    adicionaGrupo (codfilial, codgrupousuario) {
      var vm = this;
      var dados = {
        codfilial: codfilial,
        codgrupousuario: codgrupousuario
      };
      vm.$axios.post('usuario/' + vm.data.codusuario + '/grupos', dados).then(function (request) {
        if (request.status === 201) {
          vm.carregaDados(vm.data.codusuario)
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    removeGrupo (codfilial, codgrupousuario) {
      var vm = this;
      var dados = {
        codfilial: codfilial,
        codgrupousuario: codgrupousuario
      };
      vm.$axios.delete('usuario/' + vm.data.codusuario + '/grupos', { params: dados }).then(function (request) {
        if (request.status === 204) {
          vm.carregaDados(vm.data.codusuario)
        }
      }).catch(function (error) {
        console.log(error.response)
      })
    }

  },
  created () {
    this.carregaDados(this.$route.params.id);
    this.carregaGrupos();
    this.carregaFiliais()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.filial-item-title, .grupo-item-main {
  word-break: break-all;
}
</style>
