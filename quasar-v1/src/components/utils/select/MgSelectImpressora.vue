<template>
  <q-select v-model="model" :options="options" :label="label" clearable @change="selected"/>
</template>

<script>
export default {
  name: 'mg-select-impressora',
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
        this.getImpressoras()
      }
    }
  },
  methods: {
    selected (val) {
      this.$emit('input', val.value)
    },
    getImpressoras: function () {
      let vm = this;
      vm.$axios.get('impressora').then(function (request) {
        vm.options = request.data.map(impressora => {
          return {
            value: impressora.value,
            label: impressora.label
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
