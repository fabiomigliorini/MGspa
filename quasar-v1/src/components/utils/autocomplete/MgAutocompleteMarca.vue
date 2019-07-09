<template>
  <q-select v-model="model"
            clearable
            use-input
            input-debounce="200"
            :label="label"
            :options="options"
            @filter="search"
            @input="selected"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps" v-on="scope.itemEvents">
        <q-item-section>
          <q-item-label v-html="scope.opt.label" ></q-item-label>
        </q-item-section>
      </q-item>
    </template>

  </q-select>
</template>

<script>

export default {
  name: 'mg-autocomplete-marca',
  props: ['label'],
  data () {
    return {
      model: null,
      options: [],
    }
  },
  methods: {
    selected (val) {
      let vm = this;
      vm.$emit('input', val.value)
    },
    search (val, update, abort) {
      let vm = this;
      if (val.length < 3) {
        abort();
        return
      }
      setTimeout(() => {
        update(() => {
          let params = { marca: val };
          vm.$axios.get('marca/autocompletar', { params }).then(response => {
            vm.options = response.data.map(res => {
              res.label = res.label;
              res.value = res.id;
              return res;
            });
          }).catch(function (error) {})
        });
      }, 500)
    },
  }
}
</script>

<style>
</style>
