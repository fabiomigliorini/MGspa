<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/veiculo')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Novo Veículo
    </template>

    <div slot="content" class="q-pa-md">
      <!-- <pre>{{veiculo}}</pre> -->
      <mg-veiculo-form :veiculo='veiculo' :errors='errors' @submit.prevent.native="create()" />
    </div>
    <div>

    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgVeiculoForm from './Form'
// import _ from 'lodash';

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
        veiculo: null,
        placa: null,
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
        console.log(response);
        conole.log('aqui');
        vm.state.veiculo.unshift(response.data);
        vm.state.veiculo = _.sortBy(vm.state.veiculo, ['veiculo']);
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
