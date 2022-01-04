<template>
  <q-select
      outlined
      v-model="model"
      use-input
      input-debounce="0"
      :label="label"
      @filter="search"
      clearable
      :options="options"
      @input="selected"
    >
      <template v-slot:no-option>
        <q-item>
          <q-item-section class="text-grey">
            No results
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
  name: 'mg-select-estoque-local',
  props: ['value', 'label'],
  data () {
    return {
      model: null,
      options: [],
      all: [],
    }
  },
  methods: {
    search (val, update) {
      if (val === '') {
        update(() => {
          this.options = this.all
        })
        return
      }
      update(() => {
        const needle = val.toLowerCase()
        this.options = this.all.filter(v => v.label.toLowerCase().indexOf(needle) > -1)
      })
    },
    selected (val) {
      if (!this.model) {
        this.$emit('input', null)
        return
      }
      this.$emit('input', this.model.value)
    },
    getAll: function () {
      let vm = this;
      vm.$axios.get('estoque-local', {params: {fields: 'codestoquelocal,estoquelocal', 'inativo':1, 'controlaestoque':true, sort: 'estoquelocal'}}).then(function (request) {
        vm.all = request.data.data.map(estoquelocal => {
          let item = {
            value: estoquelocal.codestoquelocal,
            label: estoquelocal.estoquelocal
          }
          if (estoquelocal.codestoquelocal == vm.value) {
            vm.model = item
          }
          return item
        })
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  mounted() {
    this.getAll()
  }
}
</script>

<style>
</style>
