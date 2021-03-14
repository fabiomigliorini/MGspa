<template>
  <q-select
    outlined
    v-model="model"
    :options="options"
    :label="label"
    clearable
    @change="selected"
    >
    <template v-slot:prepend>
      <q-icon name="share" />
    </template>
    <template v-slot:error>
        <slot name="error"/>
    </template>
  </q-select>
</template>

<script>
export default {
  name: 'mg-select-ind-manifestacao',
  props: ['label', 'loadData'],
  data () {
    return {
      model: null,
      options: [
        {value: 0, label: 'Sem Manifestação'},
        {value: 210200, label: 'Operação Realizada'},
        {value: 210220, label: 'Operação Desconhecida'},
        {value: 210240, label: 'Operação Não Realizada'},
        {value: 210210, label: 'Ciência da Operação'},
      ]
    }
  },
  methods: {
    selected (val) {
      this.$emit('input', val.value)
    },
  },
  mounted() {
    this.getFiliais()
  }
}
</script>

<style>
</style>
