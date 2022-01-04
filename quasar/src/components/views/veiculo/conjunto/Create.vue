<template>
  <mg-layout back-path="/veiculo">

    <template slot="title">
      Novo Conjunto de Veículos
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-conjunto-form :conjunto='conjunto' :errors='errors' @submit.prevent.native="create()" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="create()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../../layouts/MgLayout'
import MgVeiculoConjuntoForm from './Form'
import _ from 'lodash';

export default {
  name: 'mg-veiculo-conjunto-create',
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
    create: function () {
      var vm = this;
      vm.$axios.post('veiculo-conjunto', vm.conjunto).then(function (response) {
        vm.$q.notify({
          message:'Conjunto de veículo criado!',
          type:'positive'
        });
        vm.state.veiculoConjunto.unshift(response.data.data);
        vm.state.veiculoConjunto = _.sortBy(vm.state.veiculoConjunto, ['veiculoconjunto']);
        vm.$router.push('/veiculo/');
      }).catch(function (error) {
        console.log(vm.errors);
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
