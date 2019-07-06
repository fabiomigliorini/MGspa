<template>
  <q-select v-model="value" :options="options" :label="label" clearable @change="handleChange"/>
</template>

<script>
export default {
  name: 'mg-select-impressora',
  props: ['value', 'label', 'loadData'],
  data () {
    return {
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
    handleChange (newVal) {
      this.$emit('input', newVal)
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
