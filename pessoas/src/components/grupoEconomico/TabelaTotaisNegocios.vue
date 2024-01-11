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
    { name: 'lancamento', label: 'Data', field: 'lancamento', align: 'top-left', format: (val, row) => Documentos.formataData(val), sortable: true },
    // { name: 'codnegocio', label: 'Cod Negócio', field: 'codnegocio', align: 'top-left' },
    { name: 'negocios', label: 'Negócios', field: 'negocios', align: 'top-left', sortable: true },
    { name: 'quantidade', label: 'Quantidade', field: 'quantidade', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`, sortable: true },
    { name: 'valortotal', label: 'Valor Total', field: 'valortotal', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, sortable: true },

];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()
const modelPessoas = ref({
    desde: '1 Ano',
    date: '1 Ano'
})
const filter = ref('')
const separator = ref('cell')
const pagination = ref({
    rowsPerPage: 7,
    sortBy: 'valortotal',
    descending: true
})

const filtroTotaisNegocioPessoa = debounce(async () => {
    var date = modelPessoas.value.desde

    if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
        modelPessoas.value.codpessoa = route.params.id
    }

    try {
        const ret = await sPessoa.totaisNegocios(route.params.id, modelPessoas.value)
        emit('update:totaisNegocios', ret.data)
        modelPessoas.value.desde = date
    } catch (error) {
        modelPessoas.value.desde = date
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
        })
    }
}, 500)

const filtroDesde = async () => {
    if (modelPessoas.value.date === 'Este ano') {
        var anoAtual = moment().year();
        var inicio = new Date("1/1/" + anoAtual);
        var primeiroDia = moment(inicio.valueOf()).format('YYYY-MM-DD');
        modelPessoas.value.desde = primeiroDia
        filtroTotaisNegocioPessoa()
    }
    if (modelPessoas.value.date === '1 Ano') {
        let desde = moment().subtract(1, 'year').format('YYYY-MM-DD')
        modelPessoas.value.desde = desde
        filtroTotaisNegocioPessoa()
    }
    if (modelPessoas.value.date === '2 Anos') {
        let desde = moment().subtract(2, 'year').format('YYYY-MM-DD')
        modelPessoas.value.desde = desde
        filtroTotaisNegocioPessoa()
    }
    if (modelPessoas.value.date === 'Tudo') {
        modelPessoas.value.desde = null
        filtroTotaisNegocioPessoa()
    }
}

watch(
    () => modelPessoas.value.codpessoa,
    () => filtroTotaisNegocioPessoa(),
    { deep: true }
);

watch(
    () => modelPessoas.value.date,
    () => filtroDesde(),
    { deep: true }
);

onMounted(() => {
    filtroDesde()
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
                        <q-select outlined v-model="modelPessoas.date" :options="[
                            'Este ano', '1 Ano', '2 Anos', 'Tudo']" label="Data" dense />
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