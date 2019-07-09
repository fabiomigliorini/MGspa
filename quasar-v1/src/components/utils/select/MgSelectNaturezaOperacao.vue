<template>
  <q-select v-model="model" :options="options" :label="label" @change="selected" clearable />
</template>

<script>
export default {
  name: 'mg-select-natureza-operacao',
  props: ['label', 'loadData'],
  data () {
    return {
      model: null,
      options: []
    }
  },
  watch:{
    loadData(val){
      if(val === true){
        this.getNaturezaOperacao()
      }
    }
  },
  methods: {
    selected (val) {
      this.$emit('input', val.value)
    },
    getNaturezaOperacao: function () {
      let vm = this;
      let params= {
        fields: 'naturezaoperacao,codnaturezaoperacao',
        sort: 'naturezaoperacao'
      };
      vm.$axios.get('natureza-operacao/autocompletar', {params}).then(function (request) {
        vm.options = request.data.data.map(natop => {
          return {
            value: natop.codnaturezaoperacao,
            label: natop.naturezaoperacao
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
