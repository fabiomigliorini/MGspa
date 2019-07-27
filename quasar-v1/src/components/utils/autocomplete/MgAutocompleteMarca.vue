<template>
  <q-select
      outlined
      v-model="model"
      use-input
      input-debounce="200"
      :label="label"
      @filter="search"
      clearable
      :options="options"
      @input="selected"
      :rules="rules"
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
  props: ['value', 'label', 'rules'],
  data () {
    return {
      model: null,
      options: [],
    }
  },
  methods: {
    selected (val) {
      if (!this.model) {
        this.$emit('input', null)
        return
      }
      this.$emit('input', this.model.value)
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

    init () {
      if (!this.value) {
        return
      }
      let vm = this;
      let params = { codmarca: this.value };
      vm.$axios.get('marca/autocompletar', { params }).then(response => {
        vm.model = {
          label: response.data[0].label,
          value: response.data[0].id,
        }
      }).catch(function (error) {})
    }
  },
  mounted() {
    this.init()
  }
}
</script>

<style>
</style>
