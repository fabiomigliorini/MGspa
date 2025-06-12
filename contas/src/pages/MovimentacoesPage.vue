<template>
  <MGLayout>
    <template #tituloPagina> Movimentações </template>
    <template #content>

      <div class="q-mx-md q-mt-md" v-if="portador">
        <div>
          <p  class="text-caption q-mb-auto"><b>Portador:</b> {{portador.portador}}</p>
          <p  class="text-caption q-mb-auto"><b>Filial:</b> {{portador.filial ? portador.filial : 'Sem Filial'}}</p>
          <p  class="text-caption q-mb-auto"><b>Banco:</b> {{portador.banco}}</p>
        </div>

      </div>
      <div class="flex items-center">
        <q-btn round flat size="md" icon="chevron_left" @click="diaAnterior" :disable="!diaAnteriorHabilitado" />
        <q-select v-model="diaSelecionado" :options="diasDoMes"
                  label="Dia" style="width: 60px" class="q-mx-md" @update:model-value="scrollParaDia" />
        <q-btn round flat size="md" icon="chevron_right" @click="diaSeguinte" :disable="!diaSeguinteHabilitado"/>


        <q-btn round flat size="md" icon="chevron_left" @click="mesAnterior" :disable="!mesAnteriorHabilitado" />
        <q-tabs v-model="mesAnoSelecionado" no-caps active-color="primary" class="q-mx-md">
          <q-tab v-for="mesAno in intervalo" :key="mesAno.name" :name="mesAno.name" :label="mesAno.label" />
        </q-tabs>
        <q-btn round flat size="md" icon="chevron_right" @click="mesSeguinte" :disable="!mesSeguinteHabilitado"/>
      </div>


      <div class="q-pa-md" v-if="!buscandoInfo">
        <q-table ref="tabela"
          class="my-sticky-dynamic"
          flat bordered
          :rows="extratos"
          :columns="columns"
          :loading="isLoading" virtual-scroll
          row-key="codextratobancario" :rows-per-page-options="[0]"
          loading-label="Carregando" hide-bottom>
          <template v-slot:body="props">
            <q-tr :props="props" :class="props.rowIndex % 2 === 0 ? 'bg-white' : 'bg-grey-2'">
              <q-td v-for="col in props.cols" :key="col.name" :props="props"
                :class="moneyTextColor(props, col)">
                {{ col.value }}
              </q-td>
            </q-tr>
          </template>
        </q-table>
      </div>

      <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="portador">
        <q-btn fab icon="cloud_download" color="primary" v-if="portador.codbanco == 1"
               :loading="buscandoApiBb" @click="consultarApiBB()">
          <template v-slot:loading>
            <q-spinner-oval  />
          </template>

          <q-tooltip anchor="center left" self="center right">
            Consultar API
          </q-tooltip>
        </q-btn>
      </q-page-sticky>
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
        { name: 'dia', sortable: true, label: 'Dia', field: 'dia', align: 'left', format: val => date.formatDate(val, 'DD/MM/YYYY') },
        { name: 'observacoes', label: 'Obeservação', field: 'observacoes', align: 'left' },
        { name: 'documento', label: 'Documento', field: 'numero', align: 'left' },
        { name: 'valor', label: 'Valor', field: 'valor', format: val => val !== null ? formatMoney(val) : '' },
        { name: 'saldo', label: 'Saldo', field: 'saldo', format: val => val !== undefined ? formatMoney(val) : '' }
      ],
      isLoading: false,
      buscandoApiBb: false,
      saldos:[],
      saldoAnterior: null,
      buscandoInfo: true,
      buscandoIntervalo: false,
      mesAnoSelecionado: null,
      intervalo: null,
      diaSelecionado: null,
      diasDoMes: [],
    }
  },
  watch:{
    mesAnoSelecionado(value){
      this.$router.push({
        params:{
          mesAno: value,
        }
      })
    },
    '$route.params.mesAno'(value) {
      this.mesAnoSelecionado = value
      this.buscaExtratos();
    },
  },
  computed: {
    diaAnteriorHabilitado(){
      if(!this.diasDoMes){
        return
      }
      const diaAtualIndex = this.diasDoMes.findIndex(dia => dia === this.diaSelecionado)
      return diaAtualIndex > 0
    },
    diaSeguinteHabilitado(){
      if(!this.diasDoMes){
        return
      }
      const diaAtualIndex = this.diasDoMes.findIndex(dia => dia === this.diaSelecionado)
      return diaAtualIndex < this.diasDoMes.length - 1

    },
    mesAnteriorHabilitado(){
      if(!this.intervalo){
        return
      }
      const mesAtualIndex = this.intervalo.findIndex(mes => mes.name === this.mesAnoSelecionado)
      return mesAtualIndex > 0
    },
    mesSeguinteHabilitado(){
      if(!this.intervalo){
        return
      }
      const mesAtualIndex = this.intervalo.findIndex(mes => mes.name === this.mesAnoSelecionado)
      return mesAtualIndex < this.intervalo.length - 1

    }
  },
  methods: {
    diaAnterior(){
      const diaAtualIndex = this.diasDoMes.findIndex(dia => dia === this.diaSelecionado)
      if(diaAtualIndex > 0) {
        this.diaSelecionado = this.diasDoMes[diaAtualIndex - 1];
        this.scrollParaDia(this.diaSelecionado);
      }
    },
    diaSeguinte(){
      const diaAtualIndex = this.diasDoMes.findIndex(dia => dia === this.diaSelecionado)
      if(diaAtualIndex < this.diasDoMes.length - 1) {
        this.diaSelecionado = this.diasDoMes[diaAtualIndex + 1];
        this.scrollParaDia(this.diaSelecionado);
      }
    },
    mesAnterior(){
      const mesAtualIndex = this.intervalo.findIndex(mes => mes.name === this.mesAnoSelecionado)
      if(mesAtualIndex > 0) {
        this.mesAnoSelecionado = this.intervalo[mesAtualIndex - 1].name;
      }
    },
    mesSeguinte(){
      const mesAtualIndex = this.intervalo.findIndex(mes => mes.name === this.mesAnoSelecionado)
      if(mesAtualIndex < this.intervalo.length - 1) {
        this.mesAnoSelecionado = this.intervalo[mesAtualIndex + 1].name;
      }
    },
    scrollParaDia(dataSelecionada) {
      const dataAlvo = `${dataSelecionada}-${this.mesAnoSelecionado}`;

      const index = this.extratos.findIndex(item =>
        this.$moment(item.dia).format('DD-MM-YYYY').startsWith(dataAlvo)
      );

      this.$refs.tabela.scrollTo(index, 'start-force');
    },
    criaListaIntervalo(intervalo){
      let dataInicial = this.$moment(intervalo.primeira_data);
      const dataFinal = this.$moment(intervalo.ultima_data);

      const meses = [];

      while (dataInicial.isSameOrBefore(dataFinal, 'month')) {
        meses.push({
          name: dataInicial.format('MM-YYYY'),
          label: dataInicial.format('MMM/YY').toUpperCase()
        });
        dataInicial = dataInicial.add(1, 'month');
      }
      this.intervalo = meses;
    },
    buscaIntervaloSaldos(){
      this.buscandoIntervalo = true;
      return new Promise(resolve => {
        this.$api
          .get(`v1/portador/intervalo-saldos`, {})
          .then((response) => {
            const intervalo = response.data;
            this.criaListaIntervalo(intervalo);
          })
          .catch((error) => {
            console.error('Erro:', error)
          })
          .finally(() => {
            this.buscandoIntervalo = false;
            resolve()
          })
      });
    },
    moneyTextColor(props, col){
      let classes = ""
      if(col.name === 'valor' || col.name === 'saldo') {
        let value = col.value.replace(/\s/g, '').replace('R$', '').replace(/\./g, '').replace(',', '.');
        if(value > 0){
          classes += ' text-blue'
        }else{
          classes += ' text-red'
        }
      }
      if(props.row.saldo){
        classes += ' text-weight-bold'
      }else{
        classes += ' text-weight-regular'
      }
      return classes
    },
    consultarApiBB(){
      this.buscandoApiBb = true;
      const mesAno = this.$route.params.mesAno;
      this.$api.get(`v1/portador/${this.$route.params.id}/consulta-extrato`, {
        params: {
          mes: mesAno.substring(0,2),
          ano: mesAno.substring(3)
        },
      })
      .then((response) => {
        const data = response.data;
        var mensagem = `
              Importados ${data.registros}
              registros com ${data.falhas} falhas!'`;

        this.$q.notify({ message: mensagem, color: 'positive' })
        this.extratos = [];
      })
      .catch((error) => {
        console.error('Erro:', error)
        this.$q.notify({ message: error.response.data.message, color: 'negative' })
      })
      .finally(() => {
        this.buscandoApiBb = false
        this.buscaExtratos();
      })
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
    buscaSaldos(){
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
    async buscaExtratos(index, done) {
      if (this.isLoading) {
        done?.()
        return
      }
      this.extratos = []
      this.diasDoMes = []
      this.saldos = []
      this.saldoAnterior = null

      await this.buscaIntervaloSaldos();
      await this.buscaSaldos();

      this.isLoading = true
      const mesAno = this.$route.params.mesAno;
      this.$api
        .get(`v1/portador/${this.$route.params.id}/extratos`, {
          params: {
            mes: mesAno.substring(0,2),
            ano: mesAno.substring(3)
          },
        })
        .then((response) => {
          const novosExtratos = response.data

          const extratosComSaldos = [];

          if(this.saldoAnterior){
            extratosComSaldos.push({
              dia: this.saldoAnterior.dia,
              observacoes: 'SALDO ANTERIOR',
              documento: "",
              valor: null,
              saldo: this.saldoAnterior.saldobancario
            })
          }

          let diaAtual = null;
          for(const extrato of novosExtratos){
            const diaExtrato = new Date(extrato.dia);
            diaExtrato.setHours(0, 0, 0, 0)

            //Cria lista de dias
            const diaFormatado = String(diaExtrato.getDate()).padStart(2, '0');
            if(!this.diasDoMes.includes(diaFormatado)){
              this.diasDoMes.push(diaFormatado);
            }

            if(diaAtual == null ||  diaAtual.getTime() === diaExtrato.getTime()){
              extratosComSaldos.push(extrato);
            }else{
              let saldo = this.saldos.find((saldo) => {
                  let saldoDia = new Date(saldo.dia)
                  saldoDia.setHours(0, 0, 0, 0)

                  return saldoDia.getTime() === diaAtual.getTime();
                })

              extratosComSaldos.push({
                dia: saldo.dia,
                observacoes: 'SALDO',
                documento: "",
                valor: null,
                saldo: saldo.saldobancario
              }, extrato);
            }
            diaAtual = diaExtrato;
          }

          let ultimoSaldo =  this.saldos[this.saldos.length - 1];
          if(ultimoSaldo){
            extratosComSaldos.push({
              dia: ultimoSaldo.dia,
              observacoes: 'SALDO',
              documento: "",
              valor: null,
              saldo: ultimoSaldo.saldobancario
            })
          }

          this.extratos = extratosComSaldos
          this.diaSelecionado = this.diasDoMes[0];
        })
        .catch((error) => {
          console.error('Erro:', error)
        })
        .finally(() => {
          this.isLoading = false;
          done?.()
        })
    },
  },
  mounted() {
    this.buscandoInfo = true;
    this.getPortadorInfo().then(() => {
      this.buscandoInfo = false;
      this.buscaExtratos();
    })
    this.mesAnoSelecionado = this.$route.params.mesAno
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
