<template>
  <q-select
    :value="value"
    :options="data"
    :float-label="label"
    @change="handleChange"
  />
</template>

<script>

import { QSelect } from 'quasar'

export default {
  name: 'mg-select-estoque-local',
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
      vm.$axios.get('estoque-local', {params: {fields: 'codestoquelocal,estoquelocal', sort: 'estoquelocal'}}).then(function (request) {
        vm.data = request.data.data.map(estoquelocal => {
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
  created () {
    this.loadData()
  }
}
</script>

<style>
</style>
