<template>
    <q-card class="no-shadow" bordered>
      <q-card-section>
        <div class="text-h6 text-grey-8">
          Transferências Nota Fiscais
        </div>
      </q-card-section>
      <q-separator></q-separator>
 
      <q-card-section class="q-pa-none">
        <q-table square class="no-shadow"
          title=""
          @row-click="linkscolunas"
          :rows="getvalues"
          :columns="columns"
          no-data-label="Nenhuma nota de transferência encontrada."
          :filter="filter"
          :loading="loading" 
          :separator="separator"
          row-key="index"
          virtual-scroll
          v-model:pagination="pagination"
          :rows-per-page-options="[0]"
        >
       
        <template v-slot:top-left>
            <q-btn color="primary" label="Gerar Notas de Transferencia" @click="GerarNovasTransf"/>
          </template>
          <template v-slot:top-right>
            <q-input v-if="show_filter" filled borderless dense debounce="300" v-model="filter" placeholder="Pesquisar">
              <template v-slot:append>
                <q-icon name="search"/>
              </template>
            </q-input>
            <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter=!show_filter" flat/>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </template>
  
  <script>
  import {defineComponent, ref, onMounted, defineAsyncComponent} from 'vue'
  import { api } from 'boot/axios'
  import { useQuasar } from 'quasar'
  import { Notify } from 'quasar'
  import  moment  from 'moment'
 
  const data = []
  const datelinks = ref('')

  const columns = [
    {name: 'origem', label: 'Origem', field: 'origem', align: 'top-left'},
    {name: 'destino', label: 'Destino', field: 'destino', align: 'top-left'},
    {name: 'natureza_e', label: 'Natureza', field: row => row.natureza_e + ' X' + '  ' +  row.natureza_l, sortable: true, style:'white-space: pre', align: 'top-left'},
    {name: 'emitida', required: true, label: 'Emitida', field: row => row.emitida + ' ' +'('+ row.qtd_e + ')', format: (val, row) => `${parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${row.qtd_e})`, align: 'top-left', sortable: true},
    {name: 'lancada', align: 'center', label: 'Lançada', field: row => row.lancada + ' ' +'('+ row.qtd_l + ')', format: (val, row) => `${parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${row.qtd_l})`},
    {name: 'valor_dif', label: 'Dif', field: row => row.valor_dif + ' ' +'('+ row.qtd_dif + ')', sortable: true, format: (val, row) => `${parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${row.qtd_dif})` , align: 'top-left'},
  ];


  export default defineComponent({
    name: "TabelaNotasTransf",
    props:{
      date: {
        type: String
      }
    },
    watch: { 
          date: function(newVal) { // Fica sempre olhando se a data mudou e chama a API
          var datformat = moment(newVal, 'DD-MM-YYYY').format('YYYY-MM-DD')
          this.getValores(datformat)
          datelinks.value = datformat
      }
    },
    methods: {
      currentDate() {
        var dateformated = moment().subtract(2, 'months').format('YYYY-MM-DD')
       this.getValores(dateformated)
      }
    },
    beforeMount() {
    this.currentDate()
    },
  
    setup() {

        const getvalues = ref([])
        const $q = useQuasar()
        const show_filter = ref(true)
        const loading = ref(true)

        // Faz as listagem das notas 
         const getValores = async (datformat) => {
          loading.value = true
            try {
              const {data} = await api.get('v1/nota-fiscal/dashboard?data=' + datformat)
                loading.value = false
                getvalues.value = data
                if(data == 'Erro! nada encontrado'){
                getvalues.value = []
                }
                getvalues.value.forEach((getvalues) => {
             
                if (getvalues.emitida == null || getvalues.qtd_e == null ){
                  getvalues.emitida = '0'
                  getvalues.qtd_e = '0'
                } 
              }) 
            } catch (error) {
            console.log(error.data)
            }
        }


        // Gera novas transferencias
        const GerarNovasTransf = async () => {
          $q.dialog({
            title: '',
            message: 'Selecione a filial que deseja gerar as notas',
            options: {
            type: 'radio',
            inline: true,
            align:'left',
            items: [
            { label: 'Depósito', value: '101' },
            { label: 'Botânico', value: '102' },
            { label: 'Centro', value: '103' },
            { label: 'Imperial', value: '104' },
            { label: 'André Maggi', value: '105' }
            ]
            },
            cancel: true,
            persistent: false
          }).onOk(valorfilial => {
            if (valorfilial){
              $q.loading.show({
              })
              getapi(valorfilial)
            }else{
              GerarNovasTransf()
              return  Notify.create({
              type: 'warning',
              message: 'Selecione alguma filial!'
              })
            }
          })
        }

        // Gera os links das colunas para o MGSIS
        const linkscolunas = async  (event, row) => { 
          const valorclick = row
          const dateformatada = moment(String(datelinks.value)).format('DD/MM/YY')
          var codpessoa = 0
          var codfilial = 0
          
          if (valorclick.destino == 'Centro'){
              codpessoa = '3556'
          } 
          if (valorclick.destino == 'Imperial'){
              codpessoa = '3555'
          }
          if (valorclick.destino == 'Botanico'){
              codpessoa = '3508'
          }
          if (valorclick.destino == 'Deposito'){
              codpessoa = '1514'
          }
          if (valorclick.destino == 'Andre Maggi'){
              codpessoa = '12410'
          }


          if (valorclick.origem == 'Deposito'){
            codfilial = '101'
          }
          if (valorclick.origem == 'Botanico'){
            codfilial = '102'
          }
          if (valorclick.origem == 'Andre Maggi'){
            codfilial = '105'
          }
          if (valorclick.origem == 'Imperial'){
            codfilial = '104'
          }
          if (valorclick.origem == 'Centro'){
            codfilial = '103'
          }

          if (valorclick.natureza_e && dateformatada !== 'Invalid date'){
            var a = document.createElement('a');
            a.target="_blank";
            a.href=process.env.MGSIS_URL +"index.php?r=notaFiscal%2Findex&NotaFiscal[codnotafiscal]=&NotaFiscal[numero]=&NotaFiscal[codpessoa]="+ codpessoa + "&NotaFiscal[codfilial]=" + codfilial + "&NotaFiscal[codnaturezaoperacao]=" + valorclick.codnaturezaoperacao_e + "&NotaFiscal[codstatus]=&NotaFiscal[modelo]=&NotaFiscal[codoperacao]=&NotaFiscal[emissao_de]=" + dateformatada + "&NotaFiscal[emissao_ate]=&NotaFiscal[saida_de]=&NotaFiscal[saida_ate]=&yt0="
            a.click();
          }else{
            const datemoment = moment().subtract(2, 'months').format('DD/MM/YY')
            var a = document.createElement('a');
            a.target="_blank";
            a.href=process.env.MGSIS_URL +"index.php?r=notaFiscal%2Findex&NotaFiscal[codnotafiscal]=&NotaFiscal[numero]=&NotaFiscal[codpessoa]="+ codpessoa + "&NotaFiscal[codfilial]=" + codfilial + "&NotaFiscal[codnaturezaoperacao]=" + valorclick.codnaturezaoperacao_e + "&NotaFiscal[codstatus]=&NotaFiscal[modelo]=&NotaFiscal[codoperacao]=&NotaFiscal[emissao_de]=" + datemoment + "&NotaFiscal[emissao_ate]=&NotaFiscal[saida_de]=&NotaFiscal[saida_ate]=&yt0="
            a.click();
          }
        }

        // envia para API e gera novas notas de transferencias
        const getapi = async (valorfilial) => {
          try {
            const {data}  = await api.get('v1/nota-fiscal/gera-transferencias/' + valorfilial)
            if(!data == 'Sem dados'){
            
              $q.loading.hide()
              Notify.create({
              type: 'positive',
              message: 'Notas geradas com sucesso!'
              })
            }else {
             
              $q.loading.hide()
              Notify.create({
              type: 'negative',
              message: 'Nenhuma nota gerada!'
              })
            }
          }catch (error) {
            console.log(error)
            }
        }
       
        let rows = []
        for (let i = 0; i < 1000; i++) {
            rows = rows.concat(data.slice(0).map(r => ({ ...r })))
        }
        rows.forEach((row, index) => {
         row.index = index
        })
  
      return {
        filter: ref(''),
        GerarNovasTransf,
        show_filter,
        getValores,
        loading,
        getvalues,
        separator: ref('cell'),
        linkscolunas,
        columns,
        pagination: ref({
        rowsPerPage: 100
      }),
      //Traduzindo o calendario para PT-BR
      brasil: {
        days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
        daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
        months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
        monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
        firstDayOfWeek: 1,
        format24h: true,
        pluralDay: 'dias'
      }
    }
    }
  })
  </script>
  <style scoped>

  </style>  