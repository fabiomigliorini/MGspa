<template>
  <q-select v-model="value" :options="options" :label="label" clearable @change="handleChange"/>
</template>

<script>
export default {
  name: 'mg-select-filial',
  props: ['value', 'label', 'loadData'],
  data () {
    return {
      options: []
    }
  },
  watch:{
    loadData(val){
      if(val){
        this.getFiliais()
      }
    }
  },
  methods: {
    handleChange (newVal) {
      this.$emit('input', newVal)
    },
    getFiliais() {
      let vm = this;
      vm.$axios.get('filial', {params: {fields: 'codfilial,filial', sort: 'filial'}}).then(function (request) {
        vm.options = request.data.data.map(filial => {
          return {
            value: filial.codfilial,
            label: filial.filial
          }
        })
      }).catch(function (error) {
        console.log(error.response)
      })
    },
  },
}
</script>

<style>
</style>
