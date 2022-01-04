<template>
  <q-select v-model="model" :options="options" :label="label" @change="selected" clearable>
    <template v-slot:error>
      <slot name="error"/>
    </template>
  </q-select>
</template>

<script>
export default {
  name: 'mg-select-natureza-operacao',
  props: ['label'],
  data () {
    return {
      model: null,
      options: []
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
  mounted() {
    this.getNaturezaOperacao()
  }
}
</script>

<style>
</style>
