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
  name: 'mg-select-filial',
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
      vm.$axios.get('filial', {params: {fields: 'codfilial,filial', sort: 'filial'}}).then(function (request) {
        vm.data = request.data.data.map(filial => {
          return {
            value: filial.codfilial,
            label: filial.filial
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
