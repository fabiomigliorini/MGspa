<template>
  <mg-layout back-path="/mdfe">

    <template slot="title">
      Novo MDFe pela Chave da NFe
    </template>

    <div slot="content" class="q-pa-md">
      <div class="row">
          <div class="col-xs-12">
            <q-input autofocus outlined type="text" v-model="nfechave" mask="#### #### #### #### #### #### #### #### #### #### ####" label="Chave da NFe">
            </q-input>
          </div>
      </div>
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
      nfechave: null,
      errors: {}
    }
  },
  methods: {
    hasError: function(field) {
      const item = this.errors[field];
      if (item == undefined) {
        return false;
      }
      return item.length > 0;
    },
    create: function () {
      var vm = this;
      var nfechave=this.nfechave;
      if (nfechave == undefined) {
        vm.$q.notify({
          message: 'Informe a chave da NFe!',
          type: 'negative'
        });
        return;
      }
      nfechave = nfechave.replace(/\s/g, '');
      vm.$axios.post('mdfe/criar-da-nfechave/' + nfechave).then(function (response) {
        vm.$q.notify({
          message:'MDFe criada!',
          type:'positive'
        });
        vm.$router.push('/mdfe/' + response.data.data.codmdfe);
      }).catch(function (error) {
        console.log(error);
        if (error.response.data.message) {
          vm.$q.notify({
            message: error.response.data.message,
            type: 'negative'
          });
        } else {
          vm.$q.notify({
            message: 'Falha ao criar MDFe pela Chave!',
            type: 'negative'
          });
        }
      })
    }
  },
  mounted () {
  },
  created () {
    this.nfechave = this.$route.params.nfechave;
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
