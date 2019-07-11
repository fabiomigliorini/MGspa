<template>
  <q-select v-model="model" :options="options" :label="label" clearable @change="selected"/>
</template>

<script>
export default {
  name: 'mg-select-filial',
  props: ['label', 'loadData'],
  data () {
    return {
      model: null,
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
    selected (val) {
      this.$emit('input', val.value)
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
  mounted() {
    this.getFiliais()
  }
}
</script>

<style>
</style>
