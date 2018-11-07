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
  name: 'mg-select-impressora',
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
      window.axios.get('impressora').then(function (request) {
        vm.data = request.data
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
