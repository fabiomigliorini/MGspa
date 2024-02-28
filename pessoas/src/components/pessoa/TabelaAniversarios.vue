<script setup>

import { ref, watch, computed, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from 'src/components/pessoa/SelectPessoas.vue'
import moment from 'moment'


const columns = [
    { name: 'mes', label: 'Mês', field: 'mes', align: 'top-left', sortable: true },
    { name: 'dia', label: 'Dia', field: 'dia', align: 'top-left', sortable: true },
    { name: 'idade', label: 'Aniversário', field: 'idade', align: 'top-left', sortable: true },
    { name: 'pessoa', label: 'Pessoa', field: 'pessoa', align: 'top-left', },
    { name: 'data', label: 'Data', field: 'data', align: 'top-left', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()

const filter = ref('')
const separator = ref('cell')
const pagination = ref({
    rowsPerPage: 5,
    sortBy: 'emissao',
    descending: true
})

const options = ref([])

const buscaAniversarios = async () => {

    try {
        const ret = await sPessoa.buscaAniversarios({ todos: true })
        options.value = ret.data
    } catch (error) {

    }

}

const linkPessoa = async (codpessoa) => {

    
   
    var a = document.createElement('a');
    a.target = "_blank";
    a.href = "/#/pessoa/" + codpessoa
    a.click();
}

// watch(
//     () => modelPessoas.value,
//     () => filtroNfeTerceiro(),
//     { deep: true }
// );

onMounted(() => {
    buscaAniversarios()
})

</script>

<template>
    <q-card class="no-shadow" bordered>
        <q-card-section class="q-pa-none">
            <q-table title="Todos" :filter="filter" :rows="options" :columns="columns"
                v-model:pagination="pagination" no-data-label="Nenhum aniversário encontrado" :separator="separator"
                emit-value @row-click="linkPessoa">

                <template v-slot:top-right>
                    <q-input v-if="show_filter" outlined dense debounce="300" class="q-pa-sm" v-model="filter"
                        placeholder="Pesquisar">
                        <template v-slot:append>
                            <q-icon name="search" />
                        </template>
                    </q-input>
                    <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter = !show_filter" flat />
                </template>
                <template v-slot:body="options">

                    <q-tr :props="options" @click="linkPessoa(options.row.codpessoa)" class="cursor-pointer">
                        <q-td key="mes" :props="options">
                            {{ options.row.mes }}
                        </q-td>
                        <q-td key="dia" :props="options">
                            {{ options.row.dia }}
                        </q-td>
                        <q-td key="idade" :props="options">
                            {{ options.row.idade }} Anos de idade
                        </q-td>
                        <q-td key="pessoa" :props="options">
                            {{ options.row.pessoa }}
                        </q-td>
                        <q-td key="data" :props="options">
                            {{ moment(options.row.data).format('DD/MM/YYYY') }}
                        </q-td>
                    </q-tr>
                </template>
            </q-table>
        </q-card-section>
    </q-card>
</template>