<template>
  <q-select v-model="value" :options="options" :label="label" clearable @change="handleChange"/>
</template>

<script>
export default {
  name: 'mg-select-estoque-local',
  props: ['value', 'label', 'loadData'],
  data () {
    return {
      options: []
    }
  },
  watch:{
    loadData(val){
      if(val){
        this.getEstoqueLocal()
      }
    }
  },
  methods: {
    handleChange (newVal) {
      this.$emit('input', newVal)
    },
    getEstoqueLocal: function () {
      let vm = this
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
}
</script>

<style>
</style>
