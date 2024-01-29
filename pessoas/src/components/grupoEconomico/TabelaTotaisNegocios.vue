<script setup>

import { ref, watch, computed, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from 'src/components/pessoa/SelectPessoas.vue'
import moment from 'moment'

const props = defineProps({
    totaisNegocios: {}
})

const emit = defineEmits(['update:totaisNegocios'])

const totaisNegocios = computed({
    get() {
        return props.totaisNegocios
    },

    set(value) {
        emit('update:totaisNegocios', value);
    }
});

const columns = [
    { name: 'produto', label: 'Produto', field: 'produto', align: 'top-left', sortable: true },
    { name: 'variacao', label: 'Variação', field: 'variacao', align: 'top-left', sortable: true },
    { name: 'lancamento', label: 'Data', field: 'lancamento', align: 'center', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
    // { name: 'codnegocio', label: 'Cod Negócio', field: 'codnegocio', align: 'top-left' },
    { name: 'negocios', label: 'Negócios', field: 'negocios', align: 'right', sortable: true },
    { name: 'quantidade', label: 'Quantidade', field: 'quantidade', align: 'right', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`, sortable: true },
    { name: 'valortotal', label: 'Valor Total', field: 'valortotal', align: 'right', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, sortable: true },

];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()
const modelPessoas = ref({
   desde: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD')
})
const filter = ref('')
const separator = ref('cell')
const pagination = ref({
    rowsPerPage: 7,
    sortBy: 'negocios',
    descending: true
})
const opcoesDesde = ref([])

const filtroTotaisNegocioPessoa = debounce(async () => {

    if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
        modelPessoas.value.codpessoa = route.params.id
    }

    try {
        const ret = await sPessoa.totaisNegocios(route.params.id, modelPessoas.value)
        emit('update:totaisNegocios', ret.data)
    } catch (error) {
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
        })
    }
}, 500)

const filtroDesde = async () => {
    opcoesDesde.value = [
        {
          label: 'Este Ano',
          value: moment().startOf('year').format('YYYY-MM-DD')
        },
        {
          label: '1 Ano',
          value: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD')
        },
        {
          label: '2 Anos',
          value: moment().subtract(2, 'year').startOf('month').format('YYYY-MM-DD')
        },
        {
          label: 'Tudo',
          value: null
        },
      ]
}

watch(
    () => modelPessoas.value,
    () => filtroTotaisNegocioPessoa(),
    { deep: true }
);

onMounted(() => {
    filtroDesde()
    filtroTotaisNegocioPessoa()
})

const linkMgLara = async (event, row) => {
    var a = document.createElement('a');
    a.target = "_blank";
    a.href = process.env.MGLARA_URL + "produto/" + row.codproduto
    a.click();
}
</script>

<template>
    <q-card class="no-shadow" bordered>
        <q-card-section class="q-pa-none">
            <q-table title="Produtos" :filter="filter" :rows="totaisNegocios" :columns="columns" @row-click="linkMgLara"
                no-data-label="Nenhum negócio encontrado" :separator="separator" emit-value v-model:pagination="pagination">

                <template v-slot:top-right>
                    <select-pessoas class="q-pa-sm" label="Filtrar por pessoa" v-model="modelPessoas.codpessoa">
                    </select-pessoas>

                    <div class="col-md-6 q-pl-md q-pr-md">
                        <q-select outlined v-model="modelPessoas.desde" map-options emit-value :options="opcoesDesde" label="Data" dense />
                    </div>

                    <q-input v-if="show_filter" outlined dense debounce="300" class="q-pa-sm" v-model="filter"
                        placeholder="Pesquisar">
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