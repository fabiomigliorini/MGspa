<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/estoque-saldo-conferencia/conferencia')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="create()" />

    <template slot="title">
      Nova conferência
    </template>

    <div slot="content">
      <div class="layout-padding">
        <form @submit.prevent="create()">
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
              <q-field>
                <q-input
                  type="text"
                  v-model="codigoproduto"
                  float-label="Código"
                />
              </q-field>
            </div>
          </div>
          <hr />


        </form>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectImpressora from '../../utils/select/MgSelectImpressora'
import MgSelectFilial from '../../utils/select/MgSelectFilial'

export default {
  name: 'usuario-create',
  components: {
    MgLayout,
    MgErrosValidacao,
    MgAutocompletePessoa,
    MgSelectImpressora,
    MgSelectFilial
  },
  data () {
    return {
      codigoproduto: null,
      data: {},
      erros: false
    }
  },
  methods: {
    create: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        ok: 'Salvar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('usuario', vm.data).then(function (request) {
          vm.$q.notify({
            message: 'Conferência realizada',
            type: 'positive',
          })
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
      })
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
