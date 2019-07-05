<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/marca/' + data.codmarca)">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.native="update()" />

    <template slot="title">
      {{ data.marca }}
    </template>

    <div slot="content" class="q-pa-md">

      <form @submit.prevent="update()">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="row">

              <div class="col-12">
                <q-input type="text" v-model="data.marca" label="Marca"/>
              </div>
              <div class="col-12">
                <q-toggle v-model="data.abcignorar" label="Ignorar curva ABC" />
              </div>
              <div class="col-12">
                <q-toggle v-model="data.site" label="Disponível no site" />
              </div>
              <div class="col-12">
                <q-toggle v-model="data.controlada" label="Controlada" />
              </div>
              <div class="col-12">
                <q-input filled v-model="data.descricaosite" type="textarea" label="Descrição do site" :max-height="100" :min-rows="3"/>
              </div>
            </div>
          </div>
        </div>
      </form>

    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
export default {
  name: 'usuario-create',
  components: {
    MgLayout,
  },
  data () {
    return {
      data: {},
      erros: false
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this;
      vm.$axios.get('marca/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      var vm = this;
      vm.$axios.put('marca/' + vm.data.codmarca, vm.data).then(function (request) {
        vm.$q.notify({
          message: 'Marca alterada',
          type: 'positive',
        });
        vm.$router.push('/marca/' + request.data.codmarca)
      }).catch(function (error) {
        vm.erros = error.response.data.erros
      })
    }
  },
  created () {
    this.loadData(this.$route.params.id)
  }}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
