<template>
  <!--NOVO GRUPO DE USUSARIO-->
  <q-dialog v-model="isModalOpened">
    <q-card style="width: 400px; max-width: 80vw;">
      <q-toolbar>
        <q-toolbar-title><span class="text-weight-bold">Novo Usuário</span></q-toolbar-title>
      </q-toolbar>

      <q-card-section>
        <form @submit.prevent="create()">

          <div class="row">
            <div class="col-12">
              <q-input v-model="data.usuario" label="Usuário"/>
              <q-input type="password" v-model="data.senha" label="Senha" />
              <mg-autocomplete-pessoa label="Pessoa" v-model="data.codpessoa"></mg-autocomplete-pessoa>
              <mg-select-filial label="Filial" v-model="data.codfilial"/>
              <mg-select-impressora label="Impressora Matricial" v-model="data.impressoramatricial"/>
              <mg-select-impressora label="Impressora Térmica"  v-model="data.impressoratermica"/>
            </div>
          </div>
        </form>
      </q-card-section>
      <q-card-actions>
        <q-btn v-close-popup :tabindex="-1">Cancelar</q-btn>
        <q-btn @click.prevent="create()" color="primary">Salvar</q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import MgLayout from '../../../../layouts/MgLayout'
import MgErrosValidacao from '../../../utils/MgErrosValidacao'
import MgAutocompletePessoa from '../../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectImpressora from '../../../utils/select/MgSelectImpressora'
import MgSelectFilial from '../../../utils/select/MgSelectFilial'

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
      data: {},
      erros: false,
      isModalOpened: false,
    }
  },
  methods: {
    add: function() {
      this.isModalOpened = true;
    },
    create: function () {
      let vm = this;
      vm.$axios.post('usuario', vm.data).then(function (request) {
        vm.$q.notify({
          message: 'Novo usuário cadastrado',
          type: 'positive',
        });
        vm.$router.push('/usuario/' + request.data.codusuario)
      }).catch(function (error) {
        if(error.response.data.errors.usuario){
          vm.$q.notify({message: error.response.data.errors.usuario, color: 'negative'})
        }
        if(error.response.data.errors.impressoramatricial){
          vm.$q.notify({message: error.response.data.errors.impressoramatricial, color: 'negative'})
        }
        if(error.response.data.errors.impressoratermica){
          vm.$q.notify({message: error.response.data.errors.impressoratermica, color: 'negative'})
        }
        if(error.response.data.errors.senha){
          vm.$q.notify({message: error.response.data.errors.senha, color: 'negative'})
        }
      })
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
