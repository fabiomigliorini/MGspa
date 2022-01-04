<template>
  <mg-layout back-path="/veiculo">

    <template slot="title">
      Novo Veículo
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-form :veiculo='veiculo' :errors='errors' @submit.prevent.native="create()" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="create()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgVeiculoForm from './Form'
import _ from 'lodash';

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
    create: function () {
      var vm = this;
      vm.$axios.post('veiculo', vm.veiculo).then(function (response) {
        vm.$q.notify({
          message:'Tipo de veículo criado!',
          type:'positive'
        });
        vm.state.veiculo.unshift(response.data);
        vm.state.veiculo = _.sortBy(vm.state.veiculo, ['veiculo']);
        vm.$router.push('/veiculo/');
      }).catch(function (error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao salvar!',
          type: 'negative'
        });
        vm.errors = error.response.data.errors;
      })
    }
  },
  mounted () {
  },
  created () {
    this.state = this.$store.state.veiculo
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
