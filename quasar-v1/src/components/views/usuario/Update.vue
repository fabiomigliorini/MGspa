<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/usuario/' + data.codusuario)">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="row q-pa-md">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">


          <form @submit.prevent="update()">
            <div class="row">

              <div class="col-12">
                <q-input type="text" v-model="data.usuario" label="Usuário"/>
              </div>
              <div class="col-12">
                <mg-select-filial label="Filial" v-model="data.codfilial"/>
              </div>
              <div class="col-12">
                <mg-autocomplete-pessoa label="Pessoa" v-model="data.codpessoa" :init="data.codpessoa"/>
              </div>
              <div class="col-12">
                <mg-select-impressora label="Impressora Matricial" v-model="data.impressoramatricial"/>
              </div>
              <div class="col-12">
                <mg-select-impressora label="Impressora Térmica" v-model="data.impressoratermica"/>
              </div>
            </div>

          </form>

        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgSelectImpressora from '../../utils/select/MgSelectImpressora'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectFilial from '../../utils/select/MgSelectFilial'

export default {
  name: 'usuario-update',
  components: {
    MgLayout,
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
      let vm = this;
      let params = {
        fields: ['codusuario', 'usuario', 'codfilial', 'impressoratermica', 'impressoramatricial', 'codpessoa']
      };
      vm.$axios.get('usuario/' + id, { params }).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      let vm = this;
      vm.$axios.put('usuario/' + vm.data.codusuario, vm.data).then(function (request) {
        vm.$q.notify({
          message: 'Usuário alterado',
          type: 'positive',
        });
        vm.$router.push('/usuario/' + request.data.codusuario)
      }).catch(function (error) {
        vm.erros = error.response.data.erros
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
