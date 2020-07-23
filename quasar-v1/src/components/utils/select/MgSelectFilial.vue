<template>
  <q-select
    v-model="model"
    outlined
    :options="options"
    :label="label"
    clearable
    use-input
    input-debounce="200"
    @input="selected"
    @filter="filterFn"
    :error-message="errorMessage"
    :error="error"
    :loading="loading"
    standout
  >
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Sem resultados
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>

<script>
export default {
  name: 'mg-select-impressora',
  props: ['label', 'value', 'errorMessage', 'error'],
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
      vm.$axios.get('select/filial').then(function (request) {
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
        this.options = this.allOptions.filter(v => v.value.toLowerCase().indexOf(needle) > -1)
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
