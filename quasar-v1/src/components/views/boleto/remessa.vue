<template>
  <mg-layout back-path="/boleto">

    <!-- Título da Página -->
    <template slot="title">
      Remessa Portador {{codportador}} Remessa {{remessa}}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">
      <q-table
        dense
        @row-click="abreTitulo"
        :columns="columns"
        :data="data"
        :pagination.sync="pagination"
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
      codportador: null,
      remessa: null,
      loading: true,
      pagination: {
        rowsPerPage: 22 // current rows per page being displayed
      },
      columns: [
        // { name: 'filial', align: 'left', label: 'Filial', field: 'filial', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'fantasia', align: 'left', label: 'Fantasia', field: 'fantasia', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'numero', align: 'left', label: 'Número', field: 'numero', sortable: true, headerClasses: 'bg-primary text-white', classes: 'bg-grey-2 ellipsis' },
        { name: 'nossonumero', align: 'left', label: 'NossoNumero', field: 'nossonumero', sortable: true},
        { name: 'emissao', align: 'center', label: 'Emissão', field: 'emissao', sortable: true, format: (val, row) => this.formataData(val, row)},
        { name: 'vencimento', align: 'center', label: 'Vencimento', field: 'vencimento', sortable: true, format: (val, row) => this.formataData(val, row)},
        { name: 'debito', align: 'right', label: 'Débito', field: 'debito', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
        { name: 'saldo', align: 'right', label: 'Saldo', field: 'saldo', format: (val, row) => this.formataValor(val, row), sortable: true, sort: (a, b) => parseInt(a, 10) - parseInt(b, 10) },
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
      vm.$axios.get('boleto/remessa/' + this.codportador + '/' + this.remessa).then(response => {
        vm.data = response.data;
        this.loading = false;
      })
    }, 500),

    abreTitulo: debounce(function(evt, row) {
      if (row.codtitulo != null) {
        var win = window.open(process.env.MGSIS_URL + '/index.php?r=titulo/view&id=' + row.codtitulo, '_blank');
        return;
      }
    }, 500),

  },
  mounted() {
    this.codportador = this.$route.params.codportador;
    this.remessa = this.$route.params.remessa;
    this.loadData();
  }
}
</script>

<style>
</style>
