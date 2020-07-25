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
    <template v-slot:prepend>
      <q-icon name="store" />
    </template>
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
  name: 'mg-select-produto-barra',
  props: {
    label: {
      type: String,
      required: false,
      default: 'Filial'
    },
    value: {
      type: Number,
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
    loadOptions: function (busca) {
      let vm = this;
      vm.loading = true;
      let params = {
        busca: busca
      }
      vm.$axios.get('select/produto-barra', {params}).then(function (request) {
        vm.allOptions = request.data
        vm.loading = false;
        vm.init()
      })
    },

    //filtra o array com todas opcoes (allOptions)
    filterFn (val, update, abort) {
      if (val === '' || val.length < 3) {
        abort();
        return;
      }
      update(() => {
        const busca = val.toLowerCase();
        this.loadOptions(busca);
      })
    }
  },
  mounted() {
    // this.init()
  }
}
</script>

<style>
</style>
