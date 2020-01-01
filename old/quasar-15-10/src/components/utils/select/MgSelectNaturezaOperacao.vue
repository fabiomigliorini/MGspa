<template>
  <q-select
    :value="value"
    :options="data"
    :float-label="label"
    @change="handleChange"
    clearable
  />
</template>

<script>

import { QSelect } from 'quasar'

export default {
  name: 'mg-select-natureza-operacao',
  props: ['value', 'label'],
  components: {
    QSelect
  },
  data () {
    return {
      data: []
    }
  },
  methods: {
    handleChange (newVal) {
      this.$emit('input', newVal)
    },
    loadData: function () {
      let vm = this
      let params= {
        fields: 'naturezaoperacao,codnaturezaoperacao',
        sort: 'naturezaoperacao'
      }
      vm.$axios.get('natureza-operacao/autocompletar', {params}).then(function (request) {
        vm.data = request.data.data.map(natop => {
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
  created () {
    this.loadData()
  }
}
</script>

<style>
</style>
