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
          <q-item-label v-html="scope.opt.pessoa" ></q-item-label>
          <q-item-label caption>
            {{ scope.opt.fantasia }}
            <span class="float-right">
              {{ formatCpfOrCnpj(scope.opt.cnpj, scope.opt.fisica) }}
            </span>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>

</template>

<script>

export default {
  name: 'mg-autocomplete-pessoa',
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
          let params = { pessoa: val };
          vm.$axios.get('pessoa/autocomplete', { params }).then(response => {
            vm.options = response.data.map(res => {
              res.label = res.fantasia;
              res.value = res.codpessoa;
              return res;
            });
          }).catch(function (error) { });
        });
      }, 500)
    },
    formatCpfOrCnpj(cnpj, fisica){
      if (cnpj == null) {
        return null;
      }
      if (fisica) {
        cnpj = cnpj.padStart(11, '0');
        return cnpj.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
      }else{
        cnpj = cnpj.padStart(14, '0');
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
      }

    },
  }
}
</script>

<style>
</style>
