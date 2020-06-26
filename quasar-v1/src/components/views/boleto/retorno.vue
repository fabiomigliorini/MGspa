<template>
  <mg-layout back-path="/boleto">

    <!-- Título da Página -->
    <template slot="title">
      Retorno Portador {{codportador}} Arquivo {{arquivo}} Data {{dataretorno}}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">
      <q-table
        dense
        :columns="columns"
        :data="data"
        :pagination.sync="pagination"
        @row-click="abreTitulo"
      />
    </div>
  </mg-layout>
</template>

<script>
import { debounce } from 'quasar'
import { throttle } from 'quasar'
import { Notify } from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
export default {
  components: {
    debounce,
    MgLayout,
  },
  data () {
    return {
      loading: true,
      pagination: {
        rowsPerPage: 22 // current rows per page being displayed
      },
      columns: [
        { name: 'codboletoretorno', align: 'right', label: '#', field: 'codboletoretorno', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'linha', align: 'right', label: 'Linha', field: 'linha', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'nossonumero', align: 'right', label: 'NossoNumero', field: 'nossonumero', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'numero', align: 'right', label: 'Número', field: 'numero', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'fantasia', align: 'left', label: 'Fantasia', field: 'fantasia', sortable: true},

        { name: 'pagamento', align: 'right', label: 'Pagamento', field: 'pagamento', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'jurosatraso', align: 'right', label: 'Atraso', field: 'jurosatraso', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'jurosmora', align: 'right', label: 'Mora', field: 'jurosmora', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'abatimento', align: 'right', label: 'Abatimento', field: 'abatimento', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'desconto', align: 'right', label: 'Desconto', field: 'desconto', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'protesto', align: 'right', label: 'Protesto', field: 'protesto', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'valor', align: 'right', label: 'Valor', field: 'valor', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },

        { name: 'ocorrencia', align: 'left', label: 'Ocorrencia', field: 'ocorrencia', sortable: true},
        { name: 'motivo', align: 'left', label: 'Motivo', field: 'motivo', sortable: true},
        { name: 'despesas', align: 'right', label: 'Despesas', field: 'despesas', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'outrasdespesas', align: 'right', label: 'Outras', field: 'outrasdespesas', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },

        { name: 'codbancocobrador', align: 'right', label: 'Banco', field: 'codbancocobrador'},
        { name: 'agenciacobradora', align: 'right', label: 'Agencia', field: 'agenciacobradora'},
      ],
      data: [],
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

    formataData: function(val, row) {
      if (val == null) {
        return null;
      }
      return this.moment(val).format('DD/MM/YYYY');
    },

    formataValor: function(val, row) {
      if (val == null) {
        return null;
      }
      return this.numeral(parseFloat(val)).format('0,0.00');
    },

    loadData: debounce(function () {
      // inicializa variaveis
      let vm = this;
      this.loading = true;
      vm.$axios.get('boleto/retorno/' + this.codportador + '/' + this.arquivo + '/' + this.dataretorno).then(response => {
        vm.data = response.data;
        this.loading = false;
      })
    }, 500),

    abreTitulo: debounce(function(evt, row) {
      if (row.codtitulo != null) {
        var win = window.open(process.env.MGSIS_URL + '/index.php?r=titulo/view&id=' + row.codtitulo, '_blank');
        return;
      }
      this.$q.notify({
        color: 'negative',
        message: 'Retorno não está vinculado à nenhum titulo!'
      });
    }, 500),

  },
  mounted() {
    this.codportador = this.$route.params.codportador;
    this.arquivo = this.$route.params.arquivo;
    this.dataretorno = this.$route.params.dataretorno;
    this.loadData();
  }
}
</script>

<style>
</style>
