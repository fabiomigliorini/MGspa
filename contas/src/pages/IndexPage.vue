<template>
  <MGLayout>
    <template #tituloPagina> Extrato </template>
    <template #content>
      <div class="q-pa-md">
        <q-table
          class="my-sticky-dynamic"
          flat bordered
          :rows="extratos"
          :columns="columns"
          :loading="isLoading"
          row-key="codextratobancario"
          virtual-scroll
          :virtual-scroll-item-size="48"
          :virtual-scroll-sticky-size-start="48"
          :pagination="pagination"
          :rows-per-page-options="[0]"
          @virtual-scroll="onScroll"
          loading-label="Carregando"
        />
      </div>
    </template>
  </MGLayout>
</template>

<script>
import MGLayout from 'layouts/MGLayout.vue'
import { date } from 'quasar'
import { formatMoney } from 'src/utils/formatters.js'

const  formatIndicadorTipoLancamento = (val) =>{
  switch (val){
    case "1":
      return "lançamento contabilizado"
    case "2":
      return "lançamento futuro"
    case "3":
      return "lançamento em processamento"
    case "S":
      return "saldo atual"
    case "R":
      return "saldo investimento resgate automático"
    case "E":
      return "lim. extra cartão utilizado"
    case "A":
      return "saldo aprovisionado do dia"
    case "D":
      return "saldo disponível"
    case "L":
      return "limite disponível"
    case "C":
      return "limite contratado"
    case "U":
      return "limite utilizado"
    default:
      return val
  }
}

const formatIndicadorSinalLancamento = (val) =>{
  switch (val){
    case "C":
      return "crédito"
    case "D":
      return "débito"
    case "*":
      return "valor bloqueado"
    default:
      return val
  }
}

const formatIndicadorTipoPessoaContrapartida = (val) =>{
  switch (val){
    case "F":
      return "pessoa física"
    case "J":
      return "pessoa jurídica"
    default:
      return val
  }
}

const formatCpfCnpj = (val) => {
  if(!val){
    return val
  }
  const limpo = val.replace(/\D/g, '');

  if (limpo.length === 11) {
    return limpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  } else if (limpo.length === 14) {
    return limpo.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
  } else {
    return val;
  }
}

export default {
  components: { MGLayout },
  data() {
    return {
      extratos: [],
      columns: [
        { name: 'fitid', label: 'Nº Doc', field: 'fitid' },
        { name: 'indicadortipolancamento', label: 'Tipo Lançamento', field: 'indicadortipolancamento', format: formatIndicadorTipoLancamento },
        { name: 'lancamento', label: 'Data Lançamento', field: 'lancamento', format: val => date.formatDate(val, 'DD-MM-YYYY') },
        { name: 'movimento', label: 'Data Movimento', field: 'movimento', format: val => date.formatDate(val, 'DD-MM-YYYY') },
        { name: 'codigoagenciaorigem', label: 'Ag Origem', field: 'codigoagenciaorigem' },
        { name: 'numerolote', label: 'Nº Lote', field: 'numerolote' },
        { name: 'codigohistorico', label: 'Cod Histórico', field: 'codigohistorico' },
        { name: 'textodescricaohistorico', label: 'Descrição Histórico', field: 'textodescricaohistorico' },
        { name: 'valor', label: 'Valor', field: 'valor', format: formatMoney },
        { name: 'indicadorsinallancamento', label: 'Ind. Sinal Lançamento', field: 'indicadorsinallancamento', format: formatIndicadorSinalLancamento },
        { name: 'textoinformacaocomplementar', label: 'Texto Complementar', field: 'textoinformacaocomplementar' },
        { name: 'numerocpfcnpjcontrapartida', label: 'CPF/CNPJ Contrapartida', field: 'numerocpfcnpjcontrapartida', format: formatCpfCnpj },
        { name: 'indicadortipopessoacontrapartida', label: 'Tipo Pessoa Contrapartida', field: 'indicadortipopessoacontrapartida', format: formatIndicadorTipoPessoaContrapartida },
        { name: 'codigobancocontrapartida', label: 'Cod. Banco Contrapartida', field: 'codigobancocontrapartida' },
        { name: 'codigoagenciacontrapartida', label: 'Ag. Contrapartida', field: 'codigoagenciacontrapartida' },
        { name: 'numerocontacontrapartida', label: 'Conta Contrapartida', field: 'numerocontacontrapartida' },
        { name: 'textodvcontacontrapartida', label: 'Digito Verificador', field: 'textodvcontacontrapartida' },
      ],
      page: 1,
      perPage: 50,
      isLastPage: false,
      isLoading: false,
      pagination: { rowsPerPage: 0 }
    }
  },
  methods: {
    listaExtratos(index, done) {
      if (this.isLoading || this.isLastPage) {
        done?.()
        return
      }

      this.isLoading = true
      this.$api
        .get(`v1/portador/${this.$route.params.id}/extratos`, {
          params: {
            page: this.page,
            limit: this.perPage,
            data_inicial: this.$route.query.data_inicial,
            data_final: this.$route.query.data_final
          },
        })
        .then((response) => {
          const novosExtratos = response.data.data
          this.extratos.push(...novosExtratos)

          this.isLastPage = response.data.current_page >= response.data.last_page
          this.page = this.isLastPage ? this.page : this.page + 1
        })
        .catch((error) => {
          console.error('Erro:', error)
          this.isLastPage = true
        })
        .finally(() => {
          this.isLoading = false;
          done?.()
        })
    },
    onScroll({ to, ref }) {
      const lastIndex = this.extratos.length - 1
      if (to < lastIndex - 5 || this.isLoading) return

      this.listaExtratos(() => {
        this.$nextTick(() => ref.refresh())
      })
    }
  },

  mounted() {
  },
}
</script>
<style lang="sass">
.my-sticky-dynamic
  height: calc(100vh - 80px)

  .q-table__top,
  .q-table__bottom,
  thead tr:first-child th
    background-color: white !important

  thead tr th
    position: sticky
    z-index: 1

  thead tr:last-child th
    top: 48px

  thead tr:first-child th
    top: 0

  /* prevent scrolling behind sticky top row on focus */
  tbody
    /* height of all previous header rows */
    scroll-margin-top: 48px
</style>
