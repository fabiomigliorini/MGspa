<template>
  <mg-layout back-path="/veiculo">

    <template slot="title">
      Editar Tipo de Veículo
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-form :veiculo='veiculo' :errors='errors' @submit.prevent.native="update()" v-if="veiculo.codveiculo" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="update()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgVeiculoForm from './Form'
import _ from 'lodash';
import { debounce } from 'quasar'

export default {
  name: 'mg-veiculo-create',
  components: {
    MgLayout,
    MgVeiculoForm,
  },
  data () {
    return {
      veiculo: {
        codveiculotipo: null,
        veiculo:  null,
        placa:  null,
        codestado: null,
        renavam: null,
        tara: null,
        capacidade: null,
        capacidadem3: null,
        codpessoaproprietario: null,
        tipoproprietario: null,
      },
      errors: {}
    }
  },
  methods: {
    update: function () {
      var vm = this;
      vm.$axios.put('veiculo/' + vm.veiculo.codveiculo, vm.veiculo).then(function (response) {
        const idx = vm.state.veiculo.findIndex(el => el.codveiculo === vm.veiculo.codveiculo);
        vm.$set(vm.state.veiculo, idx, response.data)  //works fine
        vm.state.veiculo = _.sortBy(vm.state.veiculo, ['placa']);
        vm.$q.notify({
          message: 'Tipo alterado!',
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
    loadVeiculo: debounce(function (codveiculo) {
      var vm = this
      vm.$axios.get('veiculo/' + codveiculo).then(response => {
        vm.veiculo = response.data
        const idx = vm.state.veiculo.findIndex(el => el.codveiculo === codveiculo);
        vm.$set(vm.state.veiculo, idx, response.data)  //works fine
      })
    }, 500)

  },
  mounted () {
  },
  created () {
    this.state = this.$store.state.veiculo;
    this.loadVeiculo(this.$route.params.codveiculo);
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
