<template>
  <q-select
    v-model="model"
    outlined
    :options="options"
    :label="label"
    use-input
    input-debounce="200"
    @input="selected"
    @filter="filterFn"
    :error-message="errorMessage"
    :error="error"
    :loading="loading"
    standout
  >
    <template v-slot:prepend>
      <q-icon name="location_city" />
    </template>
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Sem resultados
        </q-item-section>
      </q-item>
    </template>
    <template v-slot:error>
        <slot name="error"/>
    </template>
  </q-select>
</template>

<script>
export default {
  name: 'mg-select-cidade',
  props: {
    label: {
      type: String,
      required: false,
      default: 'Cidade'
    },
    value: {
      required: false,
      default: null
    },
    errorMessage: {
      type: String,
      required: false,
      default: null
    },
    error: {
      type: Boolean,
      required: false,
      default: false
    },
    filtroDfe: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      model: null,
      loading: false,
      options: [],
    }
  },
  methods: {

    // ao selecionar retorna value
    selected (val) {
      if (!val) {
        this.$emit('input', null)
        return
      }
      this.$emit('input', val.value)
    },

    init: function () {
      if (this.value) {
        let vm = this
        vm.loading = true;
        vm.$axios.get('select/cidade', {params: {codcidade: this.value}}).then(function (request) {
          if (request.data.length > 0) {
            vm.model = request.data[0];
          }
          vm.loading = false;
        })
      }
    },

    //pede para o backend as opcoes
    filterFn (val, update) {
      if (val === '') {
        return
      }
      update(() => {
        let vm = this;
        vm.loading = true;
        vm.$axios.get('select/cidade', {params: {cidade: val}}).then(function (request) {
          vm.options = request.data
          vm.loading = false;
        })
      })
    }
  },
  mounted() {
    this.init()
  }
}
</script>

<style>
</style>
