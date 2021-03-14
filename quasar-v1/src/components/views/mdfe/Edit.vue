<template>
  <mg-layout back-path="/mdfe">

    <template slot="title">
      Editar MDFe
    </template>

    <div slot="content" class="q-pa-md">
      <mg-mdfe-form :mdfe='mdfe' :errors='errors' @submit.prevent.native="update()" v-if="mdfe.codmdfe" />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="done" color="primary" @click.prevent="update()" />
      </q-page-sticky>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgMdfeForm from './Form'
import _ from 'lodash';
import { debounce } from 'quasar'

export default {
  name: 'mg-mdfe-create',
  components: {
    MgLayout,
    MgMdfeForm,
  },
  data () {
    return {
      mdfe: {
      },
      errors: {}
    }
  },
  methods: {
    update: function () {
      var vm = this;
      vm.$axios.put('mdfe/' + vm.mdfe.codmdfe, vm.mdfe).then(function (response) {
        vm.$q.notify({
          message: 'MDFe alterado!',
          type: 'positive',
        });
        vm.$router.push('/mdfe/' + vm.mdfe.codmdfe);
      }).catch(function (error) {
        console.log(vm.errors);
        vm.$q.notify({
          message: 'Falha ao salvar!',
          type: 'negative'
        });
        vm.errors = error.response.data.errors;
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative'
        });
      })
    },
    loadMdfe: debounce(function (codmdfe) {
      var vm = this
      vm.$axios.get('mdfe/' + codmdfe).then(response => {
        vm.mdfe = response.data.data
      })
    }, 500)

  },
  mounted () {
  },
  created () {
    this.state = this.$store.state.mdfe;
    this.loadMdfe(this.$route.params.codmdfe);
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
