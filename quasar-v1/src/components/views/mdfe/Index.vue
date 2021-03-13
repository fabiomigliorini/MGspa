<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
<mg-layout back-path="/">
  <!-- Título da Página -->
  <template slot="title">
    MDF-e's
  </template>

  <!-- Conteúdo Princial (Meio) -->
  <div slot="content" v-if="state">
    teste
    <!--
    <template>
      <q-tabs v-model="state.tab" class="bg-primary text-white shadow-2">
        <q-tab name="1" label="Digitação" />
        <q-tab name="2" label="Transmitidas" />
        <q-tab name="3" label="Autorizadas" />
        <q-tab name="4" label="Não Autorizadas" />
        <q-tab name="5" label="Encerradas" />
        <q-tab name="9" label="Canceladas" />
      </q-tabs>
    </template>
    -->
    <template>
      <div>
        <q-list separator>
          <template v-for="mdfe in state.mdfes.data">
            <q-item clickable v-ripple>
              <q-item-section avatar>
                <q-avatar :color="statusColor(mdfe.codmdfestatus)">
                  {{mdfe.mdfestatussigla}}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>Item with caption {{ mdfe.codmdfe }}</q-item-label>
                <q-item-label caption>Caption</q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-icon color="primary" name="bluetooth" />
            </q-item-section>

            <q-item-section>Icon as avatar</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar color="teal" text-color="white" icon="bluetooth" />
            </q-item-section>

            <q-item-section>Avatar-type icon</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar rounded color="purple" text-color="white" icon="bluetooth" />
            </q-item-section>

            <q-item-section>Rounded avatar-type icon</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white">
                R
              </q-avatar>
            </q-item-section>

            <q-item-section>Letter avatar-type</q-item-section>
          </q-item>

          <q-separator />

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar>
                <img src="https://cdn.quasar.dev/img/boy-avatar.png">
              </q-avatar>
            </q-item-section>
            <q-item-section>Image avatar</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar square>
                <img src="https://cdn.quasar.dev/img/boy-avatar.png">
              </q-avatar>
            </q-item-section>
            <q-item-section>Image square avatar</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar rounded>
                <img src="https://cdn.quasar.dev/img/boy-avatar.png">
              </q-avatar>
            </q-item-section>
            <q-item-section>Image rounded avatar</q-item-section>
          </q-item>

          <q-separator />

          <q-item clickable v-ripple>
            <q-item-section avatar>
              <q-avatar rounded>
                <img src="https://cdn.quasar.dev/img/mountains.jpg">
              </q-avatar>
            </q-item-section>
            <q-item-section>List item</q-item-section>
          </q-item>

          <q-item clickable v-ripple>
            <q-item-section thumbnail>
              <img src="https://cdn.quasar.dev/img/mountains.jpg">
            </q-item-section>
            <q-item-section>List item</q-item-section>
          </q-item>
        </q-list>
      </div>
    </template>
    <q-list dark bordered separator>
      <q-item-section>
        <q-item-label>Item with caption </q-item-label>
        <q-item-label caption>Caption</q-item-label>
      </q-item-section>
      <template v-for="mdfe in state.mdfes.data">
        <q-item clickable v-ripple>
          <q-item-section>
            <q-item-label>Item with caption {{ mdfe.codmdfe }}</q-item-label>
            <q-item-label caption>Caption</q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/mdfe/tipo/create" v-if="state.tab == 'tipo'" />
      <q-btn fab icon="add" color="primary" to="/mdfe/conjunto/create" v-if="state.tab == 'conjunto'" />
      <q-btn fab icon="add" color="primary" to="/mdfe/create" v-if="state.tab == 'mdfe'" />
    </q-page-sticky>

  </div>

</mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import {
  debounce
} from 'quasar'

export default {

  components: {
    MgLayout,
    MgNoData
  },

  data() {
    return {
      tab: 'tipo',
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      loading: true,
    }
  },

  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function(val, oldVal) {
        this.page = 1
        //this.loadData(false, null)
      },
      deep: true
    }

  },

  methods: {

    statusColor: function (codmdfestatus) {
      var ret = this.$store.state.mdfe.colorsStatus.find(el => el.value === codmdfestatus)
      if (ret) {
        return ret.color
      }
      return null;
    },

    descricaoTipoCarroceria(value) {
      const item = this.state.optionsTipoCarroceria.find(item => item.value === value);
      if (item) {
        return item.label;
      }
      return null;
    },

    descricaoTipoRodado(value) {
      const item = this.state.optionsTipoRodado.find(item => item.value === value);
      if (item) {
        return item.label;
      }
      return null;
    },

    formataCodigo: function(codigo) {
      if (codigo == null) {
        return null;
      }
      return '#' + this.numeral(parseFloat(codigo)).format('00000000');
    },

    inconeMdfe: function(mdfe) {
      const tipo = this.state.mdfeTipo.find(item => item.codmdfetipo === mdfe.codmdfetipo);
      return this.inconeTracao(tipo);
    },

    descricaoMdfeTipo: function(mdfe) {
      const tipo = this.state.mdfeTipo.find(item => item.codmdfetipo === mdfe.codmdfetipo);
      return tipo.mdfetipo;
    },

    inconeTracao: function(tipo) {
      if (tipo.tracao == undefined) {
        return null;
      }
      if (tipo.tracao) {
        return 'fas fa-truck-pickup';
      }
      return 'fas fa-trailer';
    },

    inativoClass(inativo) {
      if (inativo) {
        return 'bg-red-1 text-grey-8';
      }
      return null;
    },

    inativarTipo(tipo) {
      var vm = this
      vm.$axios.post('mdfe-tipo/' + tipo.codmdfetipo + '/inativo').then(response => {
        const idx = vm.state.mdfeTipo.findIndex(el => el.codmdfetipo === tipo.codmdfetipo);
        vm.$set(vm.state.mdfeTipo, idx, response.data) //works fine
        vm.$q.notify({
          message: 'Tipo inativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao inativar tipo!',
          type: 'negative',
        });
      });
    },

    reativarTipo(tipo) {
      var vm = this
      vm.$axios.delete('mdfe-tipo/' + tipo.codmdfetipo + '/inativo').then(response => {
        const idx = vm.state.mdfeTipo.findIndex(el => el.codmdfetipo === tipo.codmdfetipo);
        vm.$set(vm.state.mdfeTipo, idx, response.data) //works fine
        vm.$q.notify({
          message: 'Tipo reativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao reativar tipo!',
          type: 'negative',
        });
      });
    },

    excluirTipo(tipo) {
      var vm = this
      vm.$axios.delete('mdfe-tipo/' + tipo.codmdfetipo).then(response => {
        const idx = vm.state.mdfeTipo.findIndex(el => el.codmdfetipo === tipo.codmdfetipo);
        vm.state.mdfeTipo.splice(idx, 1);
        vm.$q.notify({
          message: 'Tipo excluído!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao excluir tipo! Já não está em uso?',
          type: 'negative',
        });
      });

    },

    inativarConjunto(conjunto) {
      var vm = this
      vm.$axios.post('mdfe-conjunto/' + conjunto.codmdfeconjunto + '/inativo').then(response => {
        const idx = vm.state.mdfeConjunto.findIndex(el => el.codmdfeconjunto === conjunto.codmdfeconjunto);
        vm.$set(vm.state.mdfeConjunto, idx, response.data.data) //works fine
        vm.$q.notify({
          message: 'Conjunto inativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao inativar conjunto!',
          type: 'negative',
        });
      });
    },

    reativarConjunto(conjunto) {
      var vm = this
      vm.$axios.delete('mdfe-conjunto/' + conjunto.codmdfeconjunto + '/inativo').then(response => {
        const idx = vm.state.mdfeConjunto.findIndex(el => el.codmdfeconjunto === conjunto.codmdfeconjunto);
        vm.$set(vm.state.mdfeConjunto, idx, response.data.data) //works fine
        vm.$q.notify({
          message: 'Conjunto reativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao reativar conjunto!',
          type: 'negative',
        });
      });
    },

    excluirConjunto(conjunto) {
      var vm = this
      vm.$axios.delete('mdfe-conjunto/' + conjunto.codmdfeconjunto).then(response => {
        const idx = vm.state.mdfeConjunto.findIndex(el => el.codmdfeconjunto === conjunto.codmdfeconjunto);
        vm.state.mdfeConjunto.splice(idx, 1);
        vm.$q.notify({
          message: 'Conjunto excluído!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao excluir conjunto! Já não está em uso?',
          type: 'negative',
        });
      });

    },

    inativar(mdfe) {
      var vm = this
      vm.$axios.post('mdfe/' + mdfe.codmdfe + '/inativo').then(response => {
        const idx = vm.state.mdfe.findIndex(el => el.codmdfe === mdfe.codmdfe);
        vm.$set(vm.state.mdfe, idx, response.data) //works fine
        vm.$q.notify({
          message: ' inativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao inativar mdfe!',
          type: 'negative',
        });
      });
    },

    reativar(mdfe) {
      var vm = this
      vm.$axios.delete('mdfe/' + mdfe.codmdfe + '/inativo').then(response => {
        const idx = vm.state.mdfe.findIndex(el => el.codmdfe === mdfe.codmdfe);
        vm.$set(vm.state.mdfe, idx, response.data) //works fine
        vm.$q.notify({
          message: ' reativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao reativar mdfe!',
          type: 'negative',
        });
      });
    },

    excluir(mdfe) {
      var vm = this
      vm.$axios.delete('mdfe/' + mdfe.codmdfe).then(response => {
        const idx = vm.state.mdfe.findIndex(el => el.codmdfe === mdfe.codmdfe);
        vm.state.mdfe.splice(idx, 1);
        vm.$q.notify({
          message: ' excluído!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao excluir mdfe! Já não está em uso?',
          type: 'negative',
        });
      });

    },

    loadMdfe: debounce(function(concat, done) {
      var vm = this
      vm.$axios.get('mdfe').then(response => {
        vm.state.mdfes = response.data
      })
    }, 500),

  },

  // na criacao, busca filtro do Vuex
  created() {
    this.state = this.$store.state.mdfe
    this.loadMdfe();
  }

}
</script>

<style>
</style>
