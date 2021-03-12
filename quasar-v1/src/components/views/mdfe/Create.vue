<template>
  <mg-layout back-path="/mdfe">

    <template slot="title">
      Novo MDF-e
    </template>

    <div slot="content" class="q-pa-md">
      <mg-mdfe-form :mdfe='mdfe' :errors='errors' @submit.prevent.native="create()" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="create()"  />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgMdfeForm from './Form'
import _ from 'lodash';

export default {
  name: 'mg-mdfe-create',
  components: {
    MgLayout,
    MgMdfeForm,
  },
  data () {
    return {
      mdfe: {
        codfilial: null,
        tipoemitente:  null,
        tipotransportador:  null,
        modelo:  null,
        serie:  null,
        numero:  null,
        chmdfe:  null,
        modal:  null,
        emissao:  null,
        inicioviagem:  null,
        tipoemissao:  null,
        codcidadecarregamento:  null,
        codestadofim:  null,
        informacoesadicionais:  null,
        informacoescomplementares:  null,
      },
      errors: {}
    }
  },
  methods: {
    create: function () {
      var vm = this;
      vm.$axios.post('mdfe', vm.mdfe).then(function (response) {
        vm.$q.notify({
          message:'Tipo de veículo criado!',
          type:'positive'
        });
        vm.state.mdfe.unshift(response.data);
        vm.state.mdfe = _.sortBy(vm.state.mdfe, ['mdfe']);
        vm.$router.push('/mdfe/');
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
    this.state = this.$store.state.mdfe
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
