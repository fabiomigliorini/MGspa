<script setup>

import { ref, watch, computed } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from 'src/components/pessoa/SelectPessoas.vue'

const props = defineProps({
    titulosAbertos: {}
})

const emit = defineEmits(['update:titulosAbertos'])

const titulosAbertos = computed({
    get() {
        return props.titulosAbertos
    },

    set(value) {
        emit('update:titulosAbertos', value);
    }
});

const columns = [
    { name: 'numero', label: 'Número', field: 'numero', align: 'top-left', sortable: true },
    { name: 'fatura', label: 'Fatura', field: 'fatura', align: 'top-left', sortable: true },
    { name: 'tipotitulo', label: 'Tipo Titulo', field: 'tipotitulo', align: 'top-left', sortable: true },
    { name: 'contacontabil', label: 'Conta Contabil', field: 'contacontabil', align: 'top-left', sortable: true },
    { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, sortable: true },
    { name: 'emissao', label: 'Emissão', field: 'emissao', align: 'top-left', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
    { name: 'vencimento', label: 'Vencimento', field: 'vencimento', align: 'top-left', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()
const modelPessoas = ref({})
const filter = ref('')
const separator = ref('cell')
const pagination = ref({
    rowsPerPage: 5,
    sortBy: 'vencimento',
    ascending: true
})

const filtroTotaisNegocioPessoa = debounce(async () => {

    if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
        modelPessoas.value.codpessoa = route.params.id
    }
    try {
        const ret = await sPessoa.titulosAbertos(route.params.id, modelPessoas.value)
        emit('update:titulosAbertos', ret.data)
    } catch (error) {
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
        })
    }
}, 500)

watch(
    () => modelPessoas.value.codpessoa,
    () => filtroTotaisNegocioPessoa(),
    { deep: true }
);

const linkMgSis = async (event, row) => {
    var a = document.createElement('a');
    a.target = "_blank";
    a.href = process.env.MGSIS_URL + "index.php?r=titulo/view&id=" + row.codtitulo
    a.click();
}

</script>

<template>
    <q-card class="no-shadow" bordered>
        <q-card-section class="q-pa-none">
            <q-table title="Titulos Abertos" :filter="filter" @row-click="linkMgSis" :rows="titulosAbertos"
                :columns="columns" no-data-label="Nenhum titulo encontrado" :separator="separator" emit-value
                v-model:pagination="pagination">

                <template v-slot:top-right>
                    <select-pessoas class="q-pa-sm" label="Filtrar por pessoa" v-model="modelPessoas.codpessoa">
                    </select-pessoas>

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