<template>
<div>
  <form>
    <div class="row justify-center" >
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" >
        <q-list dense>
          <q-item>
            <q-item-section>
              <q-input autofocus outlined type="text" v-model="veiculo.veiculo" label="Apelido" :error='hasError("veiculo")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.veiculo">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>


          <q-item>
            <q-item-section>
              <mg-autocomplete-pessoa label="Proprietário" v-model="veiculo.codpessoaproprietario" :error='hasError("codpessoaproprietario")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codpessoaproprietario">
                    {{mensagem}}
                  </template>
                </template>
              </mg-autocomplete-pessoa>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-veiculo-tipo-proprietario outlined v-model="veiculo.tipoproprietario" :error='hasError("tipoproprietario")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.tipoproprietario">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-veiculo-tipo-proprietario>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-veiculo-tipo outlined v-model="veiculo.codveiculotipo" :error='hasError("codveiculotipo")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codveiculotipo">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-veiculo-tipo>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="text" v-model="veiculo.renavam" label="Renavam" mask="###########" :error='hasError("renavam")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.renavam">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

        </q-list>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" >
        <q-list dense>
          <q-item>
            <q-item-section>
              <q-input outlined type="text" v-model="veiculo.placa" label="Placa" mask="AAA#X##" :error='hasError("placa")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.placa">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-estado outlined v-model="veiculo.codestado" label="Estado" :error='hasError("codestado")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codestado">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-estado>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="veiculo.tara" label="Tara (KG)" step="1" min="0" max="999999" :error='hasError("tara")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.tara">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="veiculo.capacidade" label="Capacidade (KG)" step="1" min="0" max="999999" :error='hasError("capacidade")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.capacidade">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="veiculo.capacidadem3" label="Capacidade (M3)" step="1" min="0" max="999999" :error='hasError("capacidadem3")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.capacidadem3">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

        </q-list>
      </div>
    </div>
  </form>
</div>
</template>

<script>
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'
import MgSelectEstado from '../../utils/select/MgSelectEstado'
import MgSelectVeiculoTipo from '../../utils/select/MgSelectVeiculoTipo'
import MgSelectVeiculoTipoProprietario from '../../utils/select/MgSelectVeiculoTipoProprietario'

export default {
  name: 'mg-veiculo-form-tipo',
  components: {
    MgAutocompletePessoa,
    MgSelectEstado,
    MgSelectVeiculoTipo,
    MgSelectVeiculoTipoProprietario,
  },
  props: [
    'veiculo',
    'errors'
  ],
  data() {
    return {}
  },
  computed: {
    // a computed getter
  },
  methods: {
    hasError: function(field) {
      const item = this.errors[field];
      if (item == undefined) {
        return false;
      }
      return item.length > 0;
    },
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
