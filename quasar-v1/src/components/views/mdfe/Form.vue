<template>
<div>
  <form>
    <div class="row justify-center" >
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" >
        <q-list dense>

          <q-item>
            <q-item-section>
              <mg-select-filial outlined v-model="mdfe.codfilial" label="Estado" :error='hasError("codfilial")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codfilial">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-filial>
            </q-item-section>
          </q-item>


          <q-item>
            <q-item-section>
              <q-input autofocus outlined type="text" v-model="mdfe.mdfe" label="Apelido" :error='hasError("mdfe")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.mdfe">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>


          <q-item>
            <q-item-section>
              <mg-autocomplete-pessoa label="Proprietário" v-model="mdfe.codpessoaproprietario" :error='hasError("codpessoaproprietario")'>
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
              <q-input outlined type="text" v-model="mdfe.renavam" label="Renavam" mask="###########" :error='hasError("renavam")'>
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
              <q-input outlined type="text" v-model="mdfe.placa" label="Placa" mask="AAA#X##" :error='hasError("placa")'>
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
              <mg-select-estado outlined v-model="mdfe.codestado" :error='hasError("codestado")'>
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
              <q-input outlined type="number" v-model="mdfe.tara" label="Tara (KG)" step="1" min="0" max="999999" :error='hasError("tara")'>
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
              <q-input outlined type="number" v-model="mdfe.capacidade" label="Capacidade (KG)" step="1" min="0" max="999999" :error='hasError("capacidade")'>
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
              <q-input outlined type="number" v-model="mdfe.capacidadem3" label="Capacidade (M3)" step="1" min="0" max="999999" :error='hasError("capacidadem3")'>
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
import MgSelectFilial from '../../utils/select/MgSelectFilial'
import MgSelectEstado from '../../utils/select/MgSelectEstado'

export default {
  name: 'mg-mdfe-form-tipo',
  components: {
    MgAutocompletePessoa,
    MgSelectFilial,
    MgSelectEstado,
  },
  props: [
    'mdfe',
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
    this.state = this.$store.state.mdfe
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
