<template>
  <MGLayout>
    <template #tituloPagina> Extrato </template>
    <template #content>
      <div class="q-mx-md q-mt-md"
           style="display: flex; align-items: center; justify-content: space-between;"
           v-if="portador">
        <div>
          <p  class="text-caption q-mb-auto"><b>Portador:</b> {{portador.portador}}</p>
          <p  class="text-caption q-mb-auto"><b>Filial:</b> {{portador.filial}}</p>
          <p  class="text-caption q-mb-auto"><b>Banco:</b> {{portador.banco}}</p>
        </div>

        <q-btn label="Consultar API" color="primary" icon="cloud_download"
               v-if="portador.codbanco == 1"
               :loading="buscandoApiBb" @click="consultarApiBB()" >
          <template v-slot:loading>
            <q-spinner-oval class="on-left" />
            Carregando...
          </template>
        </q-btn>
      </div>
      <div class="q-pa-md" v-if="!buscandoInfo">
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
          >
          <template v-slot:body="props">
            <q-tr :props="props" :class="props.rowIndex % 2 === 0 ? 'bg-white' : 'bg-grey-2'">
              <q-td
                v-for="col in props.cols"
                :key="col.name"
                :props="props" :class="props.row.saldo ? 'text-weight-bold' : 'text-weight-regular'"
              >
                {{  }}{{ col.value }}
              </q-td>
            </q-tr>
          </template>
        </q-table>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import MGLayout from 'layouts/MGLayout.vue'
import { date } from 'quasar'
import { formatMoney } from 'src/utils/formatters.js'


export default {
  components: { MGLayout },
  data() {
    return {
      portador: null,
      extratos: [],
      columns: [
        { name: 'lancamento', label: 'Data', field: 'lancamento', align: 'left', format: val => date.formatDate(val, 'DD/MM/YYYY') },
        { name: 'observacoes', label: 'Obeservação', field: 'observacoes', align: 'left' },
        { name: 'documento', label: 'Documento', field: 'numero' },
        { name: 'valor', label: 'Valor', field: 'valor', format: val => val !== null ? formatMoney(val) : '' },
        { name: 'saldo', label: 'Saldo', field: 'saldo', format: val => val !== undefined ? formatMoney(val) : '' }
      ],
      page: 1,
      perPage: 50,
      isLastPage: false,
      isLoading: false,
      pagination: { rowsPerPage: 0 },
      buscandoApiBb: false,
      saldos:[],
      saldoAnterior: null,
      buscandoInfo: true,
    }
  },
  methods: {
    consultarApiBB(){
      this.buscandoApiBb = true;

      this.$api.get(`v1/portador/${this.$route.params.id}/consulta-extrato`, {
        params: {
          mes: this.$route.params.mes,
          ano: this.$route.params.ano
        },
      })
      .then((response) => {
        console.log("consultarApiBB.response", response)
        const data = response.data.data;
        var mensagem = `
              Importados ${data.registros}
              registros com ${data.falhas} falhas!'`;

        this.$q.notify({ message: mensagem, color: 'positive' })
        this.extratos = [];
        this.buscaInfo();
      })
      .catch((error) => {
        console.error('Erro:', error)
        this.$q.notify({ message: error.response.data.message, color: 'negative' })
      })
      .finally(() => {
        this.buscandoApiBb = false
      })
    },
    buscaInfo(){
      this.buscandoInfo = true;
      this.getPortadorInfo().then(() =>
        this.listaSaldos().then(() => {
          this.buscandoInfo = false
        })
      )
    },
    getPortadorInfo(){
      return new Promise(resolve => {
        this.$api.get(`v1/portador/${this.$route.params.id}/info`)
          .then((response) => {
            this.portador = response.data
          })
          .catch((error) => {
            console.error('Erro:', error)
          })
          .finally(() => {
            resolve();
          })
      })

    },
    getFilial(){
      return new Promise(resolve => {
        if(!this.portador.codfilial){
          resolve();
          return;
        }
        this.$api.get(`v1/filial/${this.portador.codfilial}`, {
          params: {
            fields: 'filial',
          }
        })
          .then((response) => {
            this.filial = response.data
          })
          .catch((error) => {
            console.error('Erro:', error)
          })
          .finally(() => {
            resolve();
          })
      })
    },
    listaSaldos(){
      return new Promise(resolve => {
        const mesAno = this.$route.params.mesAno;
        this.$api
          .get(`v1/portador/${this.$route.params.id}/saldos-portador`, {
            params: {
              mes: mesAno.substring(0,2),
              ano: mesAno.substring(3)
            },
          })
          .then((response) => {
            this.saldos = response.data.saldos;
            this.saldoAnterior = response.data.saldoAnterior;
          }).catch((error) => {
            console.error('Erro:', error)
          })
          .finally(() => {
            //this.isLoading = false;
            resolve();
          })
      })
    },
    listaExtratos(index, done) {
      if (this.isLoading || this.isLastPage) {
        done?.()
        return
      }

      this.isLoading = true
      const mesAno = this.$route.params.mesAno;
      this.$api
        .get(`v1/portador/${this.$route.params.id}/extratos`, {
          params: {
            page: this.page,
            limit: this.perPage,
            mes: mesAno.substring(0,2),
            ano: mesAno.substring(3)
          },
        })
        .then((response) => {
          const novosExtratos = response.data.data

          this.isLastPage = response.data.current_page >= response.data.last_page
          const extratosComSaldos = [];

          if(this.page === 1 && this.saldoAnterior){
            extratosComSaldos.push({
              lancamento: this.saldoAnterior.dia,
              observacoes: 'SALDO ANTERIOR',
              documento: "",
              valor: null,
              saldo: this.saldoAnterior.saldobancario
            })
          }

          let diaAtual = null;
          for(const extrato of novosExtratos){
            const diaExtrato = new Date(extrato.lancamento);
            diaExtrato.setHours(0, 0, 0, 0)

            if(diaAtual == null ||  diaAtual.getTime() === diaExtrato.getTime()){
              extratosComSaldos.push(extrato);
            }else{
              let saldo = this.saldos.find((saldo) => {
                  let saldoDia = new Date(saldo.dia)
                  saldoDia.setHours(0, 0, 0, 0)

                  return saldoDia.getTime() === diaAtual.getTime();
                })

              extratosComSaldos.push({
                lancamento: saldo.dia,
                observacoes: 'SALDO',
                documento: "",
                valor: null,
                saldo: saldo.saldobancario
              }, extrato);
            }
            diaAtual = diaExtrato;
          }

          if(this.isLastPage){
            let ultimoSaldo =  this.saldos[this.saldos.length - 1];
            if(ultimoSaldo){
              extratosComSaldos.push({
                lancamento: ultimoSaldo.dia,
                observacoes: 'SALDO',
                documento: "",
                valor: null,
                saldo: ultimoSaldo.saldobancario
              })
            }
          }

          this.extratos.push(...extratosComSaldos)
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
    this.buscaInfo()
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
