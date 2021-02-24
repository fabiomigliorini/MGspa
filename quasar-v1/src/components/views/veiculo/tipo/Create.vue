<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/veiculo')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Novo Tipo de Veículo
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-tipo-form :tipo='tipo' :errors='errors' @submit.prevent.native="create()" />
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../../layouts/MgLayout'
import MgVeiculoTipoForm from './Form'
import _ from 'lodash';

export default {
  name: 'mg-veiculo-create-tipo',
  components: {
    MgLayout,
    MgVeiculoTipoForm,
  },
  data () {
    return {
      tipo: {
        veiculotipo: null,
        tracao: true,
        reboque: false,
        tiporodado: 1,
        tipocarroceria: 0
      },
      errors: {}
    }
  },
  methods: {
    create: function () {
      var vm = this;
      vm.$axios.post('veiculo-tipo', vm.tipo).then(function (response) {
        vm.$q.notify({
          message:'Tipo de veículo criado!',
          type:'positive'
        });
        vm.state.veiculoTipo.unshift(response.data);
        vm.state.veiculoTipo = _.sortBy(vm.state.veiculoTipo, ['veiculotipo']);
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
