<template>
  <q-select v-model="model"
            clearable
            use-input
            input-debounce="200"
            :label="label"
            :options="options"
            @filter="search"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps" v-on="scope.itemEvents">
        <q-item-section avatar>
          <q-icon :name="scope.opt.icon" />
        </q-item-section>
        <q-item-section>
          <q-item-label v-html="scope.opt.label" />
          <q-item-label caption>{{ scope.opt.description }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>

</template>

<script>

export default {
  name: 'mg-autocomplete-pessoa',
  props: ['label'],
  components: {
  },
  data () {
    return {
      model: null,
      options: [],
    }
  },
  methods: {
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
            vm.options = vm.parseResults(response.data);
          }).catch(function (error) { });
        });
      }, 500)
    },
    parseResults(data){
      return data.map(res => {
        return {
          value: res.codpessoa,
          label: (res.pessoa)?res.pessoa:res.fantasia,
          description: this.formatCpfOrCnpj(res.cnpj)
        }
      });
    },
    formatCpfOrCnpj(cnpjOrCpf){
      if(cnpjOrCpf.length > 14){
        return cnpjOrCpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
      }else{
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
      }

    },
  }
}
</script>

<style>
</style>
