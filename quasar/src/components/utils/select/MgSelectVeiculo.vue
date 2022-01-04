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
      <q-icon name="fas fa-truck-pickup" />
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
  name: 'mg-select-veiculo',
  props: {
    label: {
      type: String,
      required: false,
      default: 'Veiculo'
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
      loading: true,
      options: [],
      allOptions: [],
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
      let vm = this
      if (this.value) {
        vm.model = this.allOptions.find(function (el) {
          return el.value == vm.value;
        });
      }
    },

    // carrega todas opcoes
    loadAllOptions: function () {
      let vm = this;
      vm.loading = true;
      vm.$axios.get('select/veiculo').then(function (request) {
        vm.allOptions = request.data
        vm.loading = false;
        vm.init()
      })
    },

    //filtra o array com todas opcoes (allOptions)
    filterFn (val, update) {
      if (val === '') {
        update(() => {
          this.options = this.allOptions
        })
        return
      }
      update(() => {
        const needle = val.toLowerCase()
        this.options = this.allOptions.filter(v => v.label.toLowerCase().indexOf(needle) > -1)
      })
    }
  },
  mounted() {
    this.loadAllOptions()
    this.init()
  }
}
</script>

<style>
</style>
