<template>
    <q-card class="no-shadow" bordered>
      <q-card-section>
        <div class="text-h6 text-grey-8">
          
        </div>
      </q-card-section>
      <q-separator></q-separator>
      <q-card-section class="q-pa-none">
        <q-table square class="no-shadow"
          title="Notas Lançadas"
          @row-click="linkslancadas"
          :rows="valuelancadas"
          :columns="columns"
          :loading="loading"
          no-data-label="Nenhuma nota lançada encontrada."
          :filter="filter"
          :separator="separator"
          row-key="index"
          virtual-scroll
          v-model:pagination="pagination"
          :rows-per-page-options="[0]"
        >
        
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
  import {defineComponent, ref, onMounted} from 'vue'
  import { api } from 'boot/axios'
  import  moment  from 'moment'
  
 
  const data = []
  const datelinks = ref('')

  const columns = [
    {name: 'filial', label: 'Filial', field: 'filial', align: 'top-left'},
    {name: 'naturezaoperacao', label: 'Natureza Operacao', field: 'naturezaoperacao', align: 'top-left'},
    {name: 'quant', label: 'Quantidade', field: 'quant', sortable: true, style:'white-space: pre', align: 'top-left'},
    {name: 'valor', required: true, label: 'Valor', field: 'valor', format: (val, row) => `${parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, align: 'top-left', sortable: true},
  ];
  
  export default defineComponent({
    name: "TabelaNotasLancadas",
    props:{
      date: {
        type: String
      }
    },
    watch: { 
          date: function(newVal) { // Fica sempre olhando se a data mudou e chama a API
          var datformat = moment(newVal, 'DD-MM-YYYY').format('YYYY-MM-DD')
          datelinks.value = datformat
          this.ListagemLancadas(datformat)
      }
    },
    methods: {
      currentDate() {
        var dateformated = moment().subtract(2, 'months').format('YYYY-MM-DD')
       this.ListagemLancadas(dateformated)
      }   
    },
    beforeMount() {
    this.currentDate()
    },
    setup() {
        const valuelancadas = ref([])
        const show_filter = ref(true)
        const loading = ref(true)
      

        // Faz as listagem das notas lancadas 
        const ListagemLancadas = async (datformat) => {
            loading.value = true
            try {
               const {data} = await api.get('v1/nota-fiscal/notas-lancadas?data=' + datformat)
               loading.value = false
               valuelancadas.value = data
               if(data == 'Erro! nada encontrado'){
                valuelancadas.value = []
                }
            } catch (error) {
            console.log(error.data)
            }
        }

        // gera os links para o MGsis
        const linkslancadas = async  (event, row) => { 
          const dateformatada = moment(String(datelinks.value)).format('DD/MM/YY')
          if (dateformatada !== 'Invalid date'){
            var a = document.createElement('a');
            a.target="_blank";
            a.href=process.env.MGSIS_URL +"index.php?r=notaFiscal%2Findex&NotaFiscal%5Bcodnotafiscal%5D=&NotaFiscal%5Bnumero%5D=&NotaFiscal%5Bcodpessoa%5D=&NotaFiscal%5Bcodfilial%5D="+ row.codfilial +"&NotaFiscal%5Bcodnaturezaoperacao%5D="+ row.codnaturezaoperacao +"&NotaFiscal%5Bcodstatus%5D=202&NotaFiscal%5Bmodelo%5D=&NotaFiscal%5Bcodoperacao%5D=&NotaFiscal%5Bemissao_de%5D=" + dateformatada + "&NotaFiscal%5Bemissao_ate%5D=&NotaFiscal%5Bsaida_de%5D=&NotaFiscal%5Bsaida_ate%5D=&yt0="
            a.click();
          }else{
            const datemoment = moment().subtract(2, 'months').format('DD/MM/YY')
            var a = document.createElement('a');
            a.target="_blank";
            a.href=process.env.MGSIS_URL +"index.php?r=notaFiscal%2Findex&NotaFiscal%5Bcodnotafiscal%5D=&NotaFiscal%5Bnumero%5D=&NotaFiscal%5Bcodpessoa%5D=&NotaFiscal%5Bcodfilial%5D="+ row.codfilial +"&NotaFiscal%5Bcodnaturezaoperacao%5D="+ row.codnaturezaoperacao +"&NotaFiscal%5Bcodstatus%5D=202&NotaFiscal%5Bmodelo%5D=&NotaFiscal%5Bcodoperacao%5D=&NotaFiscal%5Bemissao_de%5D="+ datemoment + "&NotaFiscal%5Bemissao_ate%5D=&NotaFiscal%5Bsaida_de%5D=&NotaFiscal%5Bsaida_ate%5D=&yt0="
            a.click();
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
        show_filter,
        loading,
        data,
        valuelancadas,
        linkslancadas,
        ListagemLancadas,
        separator: ref('cell'),
        columns,
        pagination: ref({
        rowsPerPage: 100
      })
      }
    }
  })
  </script>
  <style scoped>

  </style>  