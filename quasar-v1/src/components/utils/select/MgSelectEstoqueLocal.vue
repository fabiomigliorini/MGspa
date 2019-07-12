<template>
  <q-select v-model="model" :options="options" :label="label" clearable @input="selected"/>
</template>

<script>
export default {
  name: 'mg-select-estoque-local',
  props: ['label'],
  data () {
    return {
      model: null,
      options: []
    }
  },
  methods: {
    selected (val) {
      let vm = this;
      vm.$emit('input', val.value)
    },
    getEstoqueLocal: function () {
      let vm = this;
      vm.$axios.get('estoque-local', {params: {fields: 'codestoquelocal,estoquelocal', sort: 'estoquelocal'}}).then(function (request) {
        vm.options = request.data.data.map(estoquelocal => {
          return {
            value: estoquelocal.codestoquelocal,
            label: estoquelocal.estoquelocal
          }
        })
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  mounted() {
    this.getEstoqueLocal()
  }
}
</script>

<style>
</style>
