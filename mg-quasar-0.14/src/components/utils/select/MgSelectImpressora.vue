<template>
  <q-select
    :value="value"
    :options="impressoras"
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
      impressoras: []
    }
  },
  methods: {
    handleChange (newVal) {
      this.$emit('input', newVal)
    },
    loadImpressoras: function () {
      let vm = this
      window.axios.get('usuario/impressoras').then(function (request) {
        vm.impressoras = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.loadImpressoras()
  }
}
</script>

<style>
</style>
