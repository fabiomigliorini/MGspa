<template>
  <q-select
    v-model="model"
    outlined
    :options="options"
    :label="label"
    clearable
    use-input
    input-debounce="200"
    @input="selected"
    @filter="filterFn"
    :error-message="errorMessage"
    :error="error"
    :loading="loading"
    standout
  >
    <template v-slot:prepend>
      <q-icon name="store" />
    </template>
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Sem resultados
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>

<script>
export default {
  name: 'mg-select-produto-barra',
  props: {
    label: {
      type: String,
      required: false,
      default: 'Filial'
    },
    value: {
      type: Number,
      required: false,
      default: null
    },
    errorMessage: {
      type: String,
      required: false,
      default: null
    },
    error: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      model: null,
      loading: true,
      // options: [],
      options: [{"id":41809,"codproduto":9375,"barras":"7897185970593","descricao":"Caneta Goller Testa Nota Falsa G742","sigla":"UN","preco":"3.75","marca":"Goller","referencia":"7059"},{"id":41807,"codproduto":9375,"barras":"2340000418077","descricao":"Caneta Goller Testa Nota Falsa G742 C\/12.0000","sigla":"CX","preco":"45.000000000000","marca":"Goller","referencia":"7059"},{"id":30021088,"codproduto":315150,"barras":"7897256261223","descricao":"Caneta Leonora Testa Nota Falsa","sigla":"UN","preco":"4.15","marca":"Leonora","referencia":"96059"},{"id":30021089,"codproduto":315150,"barras":"7897256262329","descricao":"Caneta Leonora Testa Nota Falsa C\/24.0000","sigla":"CX","preco":"99.00","marca":"Leonora","referencia":"96059"},{"id":945706,"codproduto":21944,"barras":"7896657802028","descricao":"Caneta Newpen Testa Nota Falsa","sigla":"UN","preco":"5.75","marca":"Newpen","referencia":"2233"},{"id":945707,"codproduto":21944,"barras":"7896657802233","descricao":"Caneta Newpen Testa Nota Falsa C\/12.0000","sigla":"PT","preco":"69.000000000000","marca":"Newpen","referencia":"2233"},{"id":70155,"codproduto":302160,"barras":"7897146001816","descricao":"Caneta Testa Nota Magic","sigla":"UN","preco":"10.50","marca":"Magic","referencia":"001816"},{"id":30003082,"codproduto":303082,"barras":"7898302070042","descricao":"Caneta Testa Nota Money Test","sigla":"UN","preco":"6.00","marca":"Diversos","referencia":"007042"},{"id":947309,"codproduto":22687,"barras":"7898504147511","descricao":"Jogo Importado Xadrez Contest Dsc02831-1-","sigla":"UN","preco":"14.50","marca":"Importado","referencia":"DSC02831-1"},{"id":947310,"codproduto":22688,"barras":"7898504147528","descricao":"Jogo Importado Xadrez Contest Dsc02831-2","sigla":"UN","preco":"26.50","marca":"Importado","referencia":"DSC02831-2"},{"id":10006591,"codproduto":103152,"barras":"7897710171433","descricao":"Revista Receitas Testadas e Aprovadas","sigla":"UN","preco":"9.99","marca":"Diversos","referencia":null}]
      allOptions: [],
      pagina: 1,
      pais: false,
    }
  },
  methods: {

    // ao selecionar retorna value
    selected (val) {
      if (!val) {
        this.$emit('input', null)
        return
      }
      this.$emit('input', val.value)
    },

    init: function () {
      let vm = this
      if (this.value) {
        vm.model = this.allOptions.find(function (el) {
          return el.value == vm.value;
        });
      }
    },

    // carrega todas opcoes
    loadOptions: function (busca) {
      let vm = this;
      vm.loading = true;
      let params = {
        busca: busca
      }
      vm.$axios.get('select/produto-barra', {params}).then(function (request) {
        vm.allOptions = request.data
        vm.itens = request.data.itens;
        vm.pagina = request.data.pagina;
        vm.mais = request.data.mais;
        vm.loading = false;
        vm.init()
      })
    },

    //filtra o array com todas opcoes (allOptions)
    filterFn (val, update, abort) {
      if (val === '' || val.length < 3) {
        abort();
        return;
      }
      update(() => {
        const busca = val.toLowerCase();
        // this.loadOptions(busca);
      })
    }
  },
  mounted() {
    // this.init()
  }
}
</script>

<style>
</style>
