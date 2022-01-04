<template>
<form>
  <div class="row justify-center">
    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
      <q-list dense>
        <q-item>
          <q-item-section>
            <q-input autofocus outlined type="text" v-model="conjunto.veiculoconjunto" label="Nome" :error='hasError("veiculoconjunto")'>
              <template v-slot:error>
                <template v-for="mensagem in errors.veiculoconjunto">
                  {{mensagem}}
                </template>
              </template>
            </q-input>
          </q-item-section>
        </q-item>


        <template v-for="veiculo in conjunto.veiculos">
          <q-item>
            <q-item-section>
              <mg-select-veiculo outlined v-model="veiculo.codveiculo" label="Veiculo" :error='hasError("codveiculo")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.veiculos">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-veiculo>
            </q-item-section>
            <q-item-section top side style="min-width: 50px; border 1px solid blue">
              <q-btn round flat color="negative" icon="delete" @click="delVeiculo(veiculo)" />
            </q-item-section>
          </q-item>
        </template>

        <q-item>
          <q-item-section>
            <q-btn color="primary" icon="add" label="Adicionar Veiculo" @click="addVeiculo()" />
          </q-item-section>
        </q-item>

      </q-list>
    </div>
  </div>
</form>
</template>

<script>
import MgSelectVeiculo from '../../../utils/select/MgSelectVeiculo'

export default {
  name: 'mg-veiculo-form-conjunto',
  components: {
    MgSelectVeiculo,
  },
  props: [
    'conjunto',
    'errors'
  ],
  data() {
    return {}
  },
  methods: {
    hasError: function(field) {
      const item = this.errors[field];
      if (item == undefined) {
        return false;
      }
      return item.length > 0;
    },
    addVeiculo: function () {
      this.conjunto.veiculos.push({codveiculo: null});
    },
    delVeiculo: function (veiculo) {
      const index = this.conjunto.veiculos.findIndex(el => el.codveiculo === veiculo.codveiculo);
      if (index > -1) {
        this.conjunto.veiculos.splice(index, 1);
      }
    }
  },
  mounted() {},
  created() {
    this.state = this.$store.state.veiculo
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
