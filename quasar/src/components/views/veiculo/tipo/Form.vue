<template>
<form>
  <div class="row justify-center">
    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
      <q-list dense>
        <q-item-label header>Detalhes do Tipo</q-item-label>
        <q-item clickable v-ripple>
          <q-item-section>
            <q-input autofocus outlined type="text" v-model="tipo.veiculotipo" label="Nome" :error='hasError("veiculotipo")'>
              <template v-slot:error>
                <template v-for="mensagem in errors.veiculotipo">
                  {{mensagem}}
                </template>
              </template>
            </q-input>
          </q-item-section>
        </q-item>

        <q-separator spaced />

        <q-item tag="label" v-ripple>
          <q-item-section>
            <q-item-label>Tração</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-toggle color="blue" v-model="tipo.tracao" :error='hasError("tracao")' />
          </q-item-section>
        </q-item>

        <q-item tag="label" v-ripple>
          <q-item-section>
            <q-item-label>Reboque</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-toggle color="blue" v-model="tipo.reboque" />
          </q-item-section>
        </q-item>

        <q-separator spaced />

        <q-item-label header>Rodado</q-item-label>
        <q-item tag="label" v-ripple>
          <q-item-section>
            <q-option-group v-model="tipo.tiporodado" :options="state.optionsTipoRodado" color="primary" />
          </q-item-section>
        </q-item>

        <q-separator spaced />

        <q-item-label header>Carroceria</q-item-label>
        <q-item tag="label" v-ripple>
          <q-item-section>
            <q-option-group v-model="tipo.tipocarroceria" :options="state.optionsTipoCarroceria" color="primary" />
          </q-item-section>
        </q-item>

      </q-list>
    </div>
  </div>
</form>
</template>

<script>
export default {
  name: 'mg-veiculo-form-tipo',
  components: {},
  props: [
    'tipo',
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
