<template>
    <q-select
    v-model="model"
    outlined
    clearable
    use-input
    input-debounce="500"
    option-value="codpessoa"
    option-label="fantasia"
    :label="label"
    :options="options"
    @filter="search"
    @input="selected"
    :error="error"
    :loading="loading"
    >
    <template v-slot:prepend>
      <q-icon name="person" />
    </template>
    <template v-slot:selected-item="scope">
      <div class="ellipsis">{{scope.opt.fantasia}}</div>
    </template>
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps" v-on="scope.itemEvents">
        <q-item-section>
          <q-item-label>
            {{ scope.opt.fantasia }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.pessoa }}
          </q-item-label>
        </q-item-section>
        <q-item-section side top>
          <q-item-label caption>{{ formatCpfOrCnpj(scope.opt.cnpj, scope.opt.fisica) }}</q-item-label>
          <q-item-label caption>{{ scope.opt.ie }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-slot:error>
    </template>
  </q-select>
</template>

<script>

import {
  debounce
} from 'quasar'

export default {

  name: 'mg-autocomplete-pessoa',

  props: {
    error: {
      required: false,
      default: false
    },
    label: {
      required: false,
      default: 'Pessoa'
    },
    value: {
      required: false,
      default: null
    }
  },

  data() {
    return {
      model: null,
      options: [],
      loading: false,
    }
  },

  // observa alteracoes no valor fora deste componente
  watch: {
    value: debounce(function(newVal, oldVal) { // watch it
      this.init();
    }, 500)
  },

  methods: {

    // ao selecionar um item emite aviso ao componente pai
    selected(val) {
      if (val == null) {
        this.$emit('input', null)
        return
      }
      this.$emit('input', val.codpessoa)
    },

    // inicializa valores do combo
    init: function() {
      if (!this.value) {
        return;
      }
      let vm = this
      vm.loading = true;
      vm.$axios.get('pessoa/autocomplete', {
        params: { codpessoa: vm.value }
      }).then(response => {
        vm.options = response.data;
        vm.model = vm.options[0];
        vm.loading = false;
      });
    },

    // pesquisa na api valores
    search(val, update, abort) {
      if (val.length < 3) {
        abort()
        return;
      }
      update(() => {
        let vm = this;
        vm.loading = true;
        vm.$axios.get('pessoa/autocomplete', {
          params: { pessoa: val }
        }).then(response => {
          vm.options = response.data;
          vm.loading = false;
        });
      });
    },

    // formata o CNPJ
    formatCpfOrCnpj(cnpj, fisica) {
      if (cnpj == null) {
        return null;
      }
      var ret = String(cnpj)
      if (fisica) {
        ret = ret.padStart(11, '0');
        return ret.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
      } else {
        ret = ret.padStart(14, '0');
        return ret.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
      }
    },

  },

  // inicializa
  mounted() {
    this.init()
  }
}
</script>

<style>
</style>
