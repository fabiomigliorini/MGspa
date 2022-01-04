<template>
  <mg-layout back-path="/veiculo">

    <template slot="title">
      Editar Conjunto de Veículos
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-conjunto-form :conjunto='conjunto' :errors='errors' @submit.prevent.native="update()" v-if="conjunto.codveiculoconjunto" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="update()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../../layouts/MgLayout'
import MgVeiculoConjuntoForm from './Form'
import _ from 'lodash';
import { debounce } from 'quasar'

export default {
  name: 'mg-veiculo-conjunto-edit',
  components: {
    MgLayout,
    MgVeiculoConjuntoForm,
  },
  data () {
    return {
      conjunto: {
        veiculoconjunto: null,
        codestado: null,
        codveiculo: null,
        veiculos: [
          {codveiculo: null},
          {codveiculo: null},
        ]
      },
      errors: {}
    }
  },
  methods: {
    update: function () {
      var vm = this;
      vm.$axios.put('veiculo-conjunto/' + vm.conjunto.codveiculoconjunto, vm.conjunto).then(function (response) {
        const idx = vm.state.veiculoConjunto.findIndex(el => el.codveiculoconjunto === vm.conjunto.codveiculoconjunto);
        vm.$set(vm.state.veiculoConjunto, idx, response.data.data)  //works fine
        vm.state.veiculoConjunto = _.sortBy(vm.state.veiculoConjunto, ['veiculoconjunto']);
        vm.$q.notify({
          message: 'Conjunto alterado!',
          type: 'positive',
        });
        vm.$router.push('/veiculo/');
      }).catch(function (error) {
        console.log(vm.errors);
        vm.$q.notify({
          message: 'Falha ao salvar!',
          type: 'negative'
        });
        vm.errors = error.response.data.errors;
      })
    },
    loadVeiculoConjunto: debounce(function (codveiculoconjunto) {
      var vm = this
      vm.$axios.get('veiculo-conjunto/' + codveiculoconjunto).then(response => {
        vm.conjunto = response.data.data
        const idx = vm.state.veiculoConjunto.findIndex(el => el.codveiculoconjunto === codveiculoconjunto);
        vm.$set(vm.state.veiculoConjunto, idx, response.data)  //works fine
      })
    }, 500)

  },
  mounted () {
  },
  created () {
    this.state = this.$store.state.veiculo;
    this.loadVeiculoConjunto(this.$route.params.codveiculoconjunto);
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
