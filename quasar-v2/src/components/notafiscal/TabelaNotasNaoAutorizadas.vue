<template>
  <q-card class="no-shadow" bordered>
    <q-card-section>
      <div class="text-h6 text-grey-8">

      </div>
    </q-card-section>
    <q-separator></q-separator>
    <q-card-section class="q-pa-none">
      <q-table square class="no-shadow" title="Notas Não Autorizadas" @row-click="linksnaoautorizadas"
        :rows="valuenaoautorizadas" :columns="columns" :loading="loading"
        no-data-label="Nenhuma nota não autorizada encontrada." :filter="filter" :separator="separator" row-key="index"
        virtual-scroll v-model:pagination="pagination" :rows-per-page-options="[0]">

        <template v-slot:top-right>
          <q-input v-if="show_filter" filled borderless dense debounce="300" v-model="filter" placeholder="Pesquisar">
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
          <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter = !show_filter" flat />
        </template>
      </q-table>
    </q-card-section>
  </q-card>
</template>
  
<script>
import { defineComponent, ref, onMounted } from 'vue'
import { api } from 'boot/axios'



const data = []

const columns = [
  { name: 'filial', label: 'Filial', field: 'filial', align: 'top-left' },
  { name: 'naturezaoperacao', label: 'Natureza Operacao', field: 'naturezaoperacao', align: 'top-left' },
  { name: 'quant', label: 'Quantidade', field: 'quant', sortable: true, style: 'white-space: pre', align: 'top-left' },
  { name: 'valor', required: true, label: 'Valor', field: 'valor', format: (val, row) => `${parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, align: 'top-left', sortable: true },
];

export default defineComponent({
  name: "TabelaNotasNaoAutorizadas",
  setup() {
    const valuenaoautorizadas = ref([])
    const show_filter = ref(true)
    const loading = ref(true)

    // Faz as listagem das notas por emitir 
    const arrayEmitir = async () => {
      try {
        const { data } = await api.get('v1/nota-fiscal/notas-nao-autorizadas')
        loading.value = false
        valuenaoautorizadas.value = data
        if (data == 'Erro! nada encontrado') {
          valuenaoautorizadas.value = []
        }
      } catch (error) {
        console.log(error.data)
      }
    }

    // gera os links para o MGsis
    const linksnaoautorizadas = async (event, row) => {

      var a = document.createElement('a');
      a.target = "_blank";
      a.href = process.env.MGSIS_URL + "index.php?r=notaFiscal%2Findex&NotaFiscal%5Bcodnotafiscal%5D=&NotaFiscal%5Bnumero%5D=&NotaFiscal%5Bcodpessoa%5D=&NotaFiscal%5Bcodfilial%5D=" + row.codfilial + "&NotaFiscal%5Bcodnaturezaoperacao%5D=" + row.codnaturezaoperacao + "&NotaFiscal%5Bcodstatus%5D=&NotaFiscal%5Bmodelo%5D=&NotaFiscal%5Bcodoperacao%5D=&NotaFiscal%5Bemissao_de%5D=&NotaFiscal%5Bemissao_ate%5D=&NotaFiscal%5Bsaida_de%5D=&NotaFiscal%5Bsaida_ate%5D=&yt0="
      a.click();
    }

    onMounted(() => {
      arrayEmitir()
    })

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
      valuenaoautorizadas,
      linksnaoautorizadas,
      separator: ref('cell'),
      columns,
      pagination: ref({
        rowsPerPage: 100
      })
    }
  }
})
</script>
<style scoped></style>  