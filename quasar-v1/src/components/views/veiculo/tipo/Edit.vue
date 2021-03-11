<template>
  <mg-layout back-path="/veiculo">

    <template slot="title">
      Editar Tipo de Veículo
    </template>

    <div slot="content" class="q-pa-md">
      <mg-veiculo-tipo-form :tipo='tipo' :errors='errors' @submit.prevent.native="update()" v-if="tipo.codveiculotipo" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="update()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../../layouts/MgLayout'
import MgVeiculoTipoForm from './Form'
import _ from 'lodash';
import { debounce } from 'quasar'

export default {
  name: 'mg-veiculo-create-tipo',
  components: {
    MgLayout,
    MgVeiculoTipoForm,
  },
  data () {
    return {
      tipo: {
        codveiculotipo: null,
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
    update: function () {
      var vm = this;
      vm.$axios.put('veiculo-tipo/' + vm.tipo.codveiculotipo, vm.tipo).then(function (response) {
        const idx = vm.state.veiculoTipo.findIndex(el => el.codveiculotipo === vm.tipo.codveiculotipo);
        vm.$set(vm.state.veiculoTipo, idx, response.data)  //works fine
        vm.state.veiculoTipo = _.sortBy(vm.state.veiculoTipo, ['veiculotipo']);
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
    loadVeiculoTipo: debounce(function (codveiculotipo) {
      var vm = this
      vm.$axios.get('veiculo-tipo/' + codveiculotipo).then(response => {
        vm.tipo = response.data
        const idx = vm.state.veiculoTipo.findIndex(el => el.codveiculotipo === codveiculotipo);
        vm.$set(vm.state.veiculoTipo, idx, response.data)  //works fine
      })
    }, 500)

  },
  mounted () {
  },
  created () {
    this.state = this.$store.state.veiculo;
    this.loadVeiculoTipo(this.$route.params.codveiculotipo);
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
