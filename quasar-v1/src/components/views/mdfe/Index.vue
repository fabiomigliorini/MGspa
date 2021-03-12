<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
<mg-layout back-path="/">
  <!-- Título da Página -->
  <template slot="title">
    Veículos
  </template>

  <!-- Conteúdo Princial (Meio) -->
  <div slot="content">
    <!-- <pre>{{veiculoTipo}}</pre> -->

    <template v-if="state">
      <template>
        <q-tabs v-model="state.tab" class="bg-primary text-white shadow-2">
          <q-tab name="veiculo" icon="local_shipping" label="Veículos" />
          <q-tab name="conjunto" icon="commute" label="Conjuntos" />
          <q-tab name="tipo" icon="handyman" label="Tipos" />
        </q-tabs>
      </template>

      <template>
        <q-tab-panels v-model="state.tab" animated swipeable>
          <q-tab-panel name="veiculo">
            <mg-no-data v-if="state.veiculo.length == 0" class="layout-padding"></mg-no-data>
            <q-list inset bordered class="rounded-borders" style="max-width: 600px; margin:auto">
              <template v-for="veiculo in state.veiculo">

                <q-item :class="inativoClass(veiculo.inativo)">
                  <q-item-section avatar top>
                    <q-avatar :icon="inconeVeiculo(veiculo)" color="primary" text-color="white" />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label lines="1">
                      {{veiculo.placa}}
                      {{veiculo.veiculo}}
                    </q-item-label>
                    <q-item-label lines="1" caption>
                      {{formataCodigo(veiculo.codveiculo)}}
                      {{descricaoVeiculoTipo(veiculo)}}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <div class="text-grey-8 q-gutter-xs">
                      <q-btn color="grey-7" round flat icon="more_vert">
                        <q-menu cover auto-close>
                          <q-list>
                            <q-item clickable :to="'/veiculo/'+veiculo.codveiculo+'/edit'">
                              <q-item-section side>
                                <q-icon name="edit" />
                              </q-item-section>
                              <q-item-section>
                                Editar
                              </q-item-section>
                            </q-item>
                            <q-item clickable v-if="veiculo.inativo" @click="reativar(veiculo)">
                              <q-item-section side>
                                <q-icon name="thumb_up" />
                              </q-item-section>
                              <q-item-section>Reativar</q-item-section>
                            </q-item>
                            <q-item clickable v-else @click="inativar(veiculo)">
                              <q-item-section side>
                                <q-icon name="thumb_down" />
                              </q-item-section>
                              <q-item-section>Inativar</q-item-section>
                            </q-item>
                            <q-item clickable @click="excluir(veiculo)">
                              <q-item-section side>
                                <q-icon name="delete" />
                              </q-item-section>
                              <q-item-section>Excluir</q-item-section>
                            </q-item>
                          </q-list>
                        </q-menu>
                      </q-btn>
                    </div>
                  </q-item-section>

                </q-item>

                <q-separator inset="item" />

              </template>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="conjunto">
            <!-- Se não tiver registros -->
            <mg-no-data v-if="state.veiculoConjunto.length == 0" class="layout-padding"></mg-no-data>
            <q-list inset bordered class="rounded-borders" style="max-width: 600px; margin:auto">
              <template v-for="conjunto in state.veiculoConjunto">

                <q-item :class="inativoClass(conjunto.inativo)">
                  <!-- <q-item-section avatar top> -->
                  <!-- </q-item-section> -->

                  <q-item-section>
                    <q-item-label lines="1">
                      {{conjunto.veiculoconjunto}}
                    </q-item-label>
                    <q-item-label lines="1" caption>
                      <template v-for="veiculo in conjunto.veiculos">
                        <q-chip size="sm" :icon="inconeTracao(veiculo)">
                          {{veiculo.placa}}
                        </q-chip>
                      </template>
                      <!-- {{formataCodigo(conjunto.codveiculoconjunto)}} | -->
                      <!-- {{descricaoConjuntoRodado(conjunto.conjuntorodado)}} | -->
                      <!-- {{descricaoConjuntoCarroceria(conjunto.conjuntocarroceria)}} -->
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <div class="text-grey-8 q-gutter-xs">
                      <q-btn color="grey-7" round flat icon="more_vert">
                        <q-menu cover auto-close>
                          <q-list>
                            <q-item clickable :to="'/veiculo/conjunto/'+conjunto.codveiculoconjunto+'/edit'">
                              <q-item-section side>
                                <q-icon name="edit" />
                              </q-item-section>
                              <q-item-section>
                                Editar
                              </q-item-section>
                            </q-item>
                            <q-item clickable v-if="conjunto.inativo" @click="reativarConjunto(conjunto)">
                              <q-item-section side>
                                <q-icon name="thumb_up" />
                              </q-item-section>
                              <q-item-section>Reativar</q-item-section>
                            </q-item>
                            <q-item clickable v-else @click="inativarConjunto(conjunto)">
                              <q-item-section side>
                                <q-icon name="thumb_down" />
                              </q-item-section>
                              <q-item-section>Inativar</q-item-section>
                            </q-item>
                            <q-item clickable @click="excluirConjunto(conjunto)">
                              <q-item-section side>
                                <q-icon name="delete" />
                              </q-item-section>
                              <q-item-section>Excluir</q-item-section>
                            </q-item>
                          </q-list>
                        </q-menu>
                      </q-btn>
                    </div>
                  </q-item-section>

                </q-item>

                <q-separator />

              </template>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="tipo">
            <!-- Se não tiver registros -->
            <mg-no-data v-if="state.veiculoTipo.length == 0" class="layout-padding"></mg-no-data>
            <q-list inset bordered class="rounded-borders" style="max-width: 600px; margin:auto">
              <template v-for="tipo in state.veiculoTipo">

                <q-item :class="inativoClass(tipo.inativo)">
                  <q-item-section avatar top>
                    <q-avatar :icon="inconeTracao(tipo)" color="primary" text-color="white" />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label lines="1">
                      {{tipo.veiculotipo}}
                    </q-item-label>
                    <q-item-label lines="1" caption>
                      {{formataCodigo(tipo.codveiculotipo)}} |
                      {{descricaoTipoRodado(tipo.tiporodado)}} |
                      {{descricaoTipoCarroceria(tipo.tipocarroceria)}}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <div class="text-grey-8 q-gutter-xs">
                      <q-btn color="grey-7" round flat icon="more_vert">
                        <q-menu cover auto-close>
                          <q-list>
                            <q-item clickable :to="'/veiculo/tipo/'+tipo.codveiculotipo+'/edit'">
                              <q-item-section side>
                                <q-icon name="edit" />
                              </q-item-section>
                              <q-item-section>
                                Editar
                              </q-item-section>
                            </q-item>
                            <q-item clickable v-if="tipo.inativo" @click="reativarTipo(tipo)">
                              <q-item-section side>
                                <q-icon name="thumb_up" />
                              </q-item-section>
                              <q-item-section>Reativar</q-item-section>
                            </q-item>
                            <q-item clickable v-else @click="inativarTipo(tipo)">
                              <q-item-section side>
                                <q-icon name="thumb_down" />
                              </q-item-section>
                              <q-item-section>Inativar</q-item-section>
                            </q-item>
                            <q-item clickable @click="excluirTipo(tipo)">
                              <q-item-section side>
                                <q-icon name="delete" />
                              </q-item-section>
                              <q-item-section>Excluir</q-item-section>
                            </q-item>
                          </q-list>
                        </q-menu>
                      </q-btn>
                    </div>
                  </q-item-section>

                </q-item>

                <q-separator inset="item" />

              </template>
            </q-list>
          </q-tab-panel>
        </q-tab-panels>
      </template>

    </template>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" to="/veiculo/tipo/create" v-if="state.tab == 'tipo'" />
      <q-btn fab icon="add" color="primary" to="/veiculo/conjunto/create" v-if="state.tab == 'conjunto'" />
      <q-btn fab icon="add" color="primary" to="/veiculo/create" v-if="state.tab == 'veiculo'" />
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

    inconeVeiculo: function(veiculo) {
      const tipo = this.state.veiculoTipo.find(item => item.codveiculotipo === veiculo.codveiculotipo);
      return this.inconeTracao(tipo);
    },

    descricaoVeiculoTipo: function(veiculo) {
      const tipo = this.state.veiculoTipo.find(item => item.codveiculotipo === veiculo.codveiculotipo);
      return tipo.veiculotipo;
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
      vm.$axios.post('veiculo-tipo/' + tipo.codveiculotipo + '/inativo').then(response => {
        const idx = vm.state.veiculoTipo.findIndex(el => el.codveiculotipo === tipo.codveiculotipo);
        vm.$set(vm.state.veiculoTipo, idx, response.data) //works fine
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
      vm.$axios.delete('veiculo-tipo/' + tipo.codveiculotipo + '/inativo').then(response => {
        const idx = vm.state.veiculoTipo.findIndex(el => el.codveiculotipo === tipo.codveiculotipo);
        vm.$set(vm.state.veiculoTipo, idx, response.data) //works fine
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
      vm.$axios.delete('veiculo-tipo/' + tipo.codveiculotipo).then(response => {
        const idx = vm.state.veiculoTipo.findIndex(el => el.codveiculotipo === tipo.codveiculotipo);
        vm.state.veiculoTipo.splice(idx, 1);
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
      vm.$axios.post('veiculo-conjunto/' + conjunto.codveiculoconjunto + '/inativo').then(response => {
        const idx = vm.state.veiculoConjunto.findIndex(el => el.codveiculoconjunto === conjunto.codveiculoconjunto);
        vm.$set(vm.state.veiculoConjunto, idx, response.data.data) //works fine
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
      vm.$axios.delete('veiculo-conjunto/' + conjunto.codveiculoconjunto + '/inativo').then(response => {
        const idx = vm.state.veiculoConjunto.findIndex(el => el.codveiculoconjunto === conjunto.codveiculoconjunto);
        vm.$set(vm.state.veiculoConjunto, idx, response.data.data) //works fine
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
      vm.$axios.delete('veiculo-conjunto/' + conjunto.codveiculoconjunto).then(response => {
        const idx = vm.state.veiculoConjunto.findIndex(el => el.codveiculoconjunto === conjunto.codveiculoconjunto);
        vm.state.veiculoConjunto.splice(idx, 1);
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

    inativar(veiculo) {
      var vm = this
      vm.$axios.post('veiculo/' + veiculo.codveiculo + '/inativo').then(response => {
        const idx = vm.state.veiculo.findIndex(el => el.codveiculo === veiculo.codveiculo);
        vm.$set(vm.state.veiculo, idx, response.data) //works fine
        vm.$q.notify({
          message: ' inativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao inativar veiculo!',
          type: 'negative',
        });
      });
    },

    reativar(veiculo) {
      var vm = this
      vm.$axios.delete('veiculo/' + veiculo.codveiculo + '/inativo').then(response => {
        const idx = vm.state.veiculo.findIndex(el => el.codveiculo === veiculo.codveiculo);
        vm.$set(vm.state.veiculo, idx, response.data) //works fine
        vm.$q.notify({
          message: ' reativado!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao reativar veiculo!',
          type: 'negative',
        });
      });
    },

    excluir(veiculo) {
      var vm = this
      vm.$axios.delete('veiculo/' + veiculo.codveiculo).then(response => {
        const idx = vm.state.veiculo.findIndex(el => el.codveiculo === veiculo.codveiculo);
        vm.state.veiculo.splice(idx, 1);
        vm.$q.notify({
          message: ' excluído!',
          type: 'positive',
        });
      }).catch(function(error) {
        console.log(error);
        vm.$q.notify({
          message: 'Falha ao excluir veiculo! Já não está em uso?',
          type: 'negative',
        });
      });

    },

    // carrega registros da api
    loadVeiculoTipo: debounce(function(concat, done) {
      var vm = this
      vm.$axios.get('veiculo-tipo').then(response => {
        vm.state.veiculoTipo = response.data
      })
    }, 500),

    loadVeiculoConjunto: debounce(function(concat, done) {
      var vm = this
      vm.$axios.get('veiculo-conjunto').then(response => {
        vm.state.veiculoConjunto = response.data.data
      })
    }, 500),

    loadVeiculo: debounce(function(concat, done) {
      var vm = this
      vm.$axios.get('veiculo').then(response => {
        vm.state.veiculo = response.data
      })
    }, 500),

  },

  // na criacao, busca filtro do Vuex
  created() {
    this.state = this.$store.state.veiculo
    if (this.state.veiculoTipo.length == 0) {
      this.loadVeiculoTipo();
      this.loadVeiculoConjunto();
      this.loadVeiculo();
    }
  }

}
</script>

<style>
</style>
