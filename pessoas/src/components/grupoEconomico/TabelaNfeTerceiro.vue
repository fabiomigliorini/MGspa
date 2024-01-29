<script setup>

import { ref, watch, computed, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from 'src/components/pessoa/SelectPessoas.vue'
import moment from 'moment'

const props = defineProps({
    nfeTerceiro: {}
})

const emit = defineEmits(['update:nfeTerceiro'])

const nfeTerceiro = computed({
    get() {
        return props.nfeTerceiro
    },

    set(value) {
        emit('update:nfeTerceiro', value);
    }
});

const columns = [
    { name: 'nfechave', label: 'Chave Nfe', field: 'nfechave', align: 'top-left', sortable: true },
    { name: 'serie', label: 'Série', field: 'serie', align: 'top-left', sortable: true },
    { name: 'numero', label: 'Número', field: 'numero', align: 'top-left', sortable: true },
    { name: 'emissao', label: 'Emissão', field: 'emissao', align: 'top-left', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
    { name: 'entrada', label: 'Entrada', field: 'entrada', align: 'top-left', format: (val, row) => Documentos.formataDatasemHr(val), sortable: true },
    { name: 'indsituacao', label: 'indsituacao', field: 'indsituacao', align: 'top-left', sortable: true },
    { name: 'indmanifestacao', label: 'indmanifestacao', field: 'indmanifestacao', align: 'top-left', sortable: true },
    { name: 'valortotal', label: 'Valor Total', field: 'valortotal', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, sortable: true },

];

const $q = useQuasar()
const show_filter = ref(true)
const route = useRoute()
const loading = ref(true)
const sPessoa = pessoaStore()
const Documentos = formataDocumetos()
const modelPessoas = ref({
    desde: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD'),
})
const filter = ref('')
const separator = ref('cell')
const pagination = ref({
    rowsPerPage: 5,
    sortBy: 'emissao',
    descending: true
})

const opcoesDesde = ref([])

const filtroNfeTerceiro = debounce(async () => {
   
    if (!modelPessoas.value.codpessoa && route.path.search("pessoa") == 1) {
        modelPessoas.value.codpessoa = route.params.id
    }

    try {
        const ret = await sPessoa.nfeTerceiro(route.params.id, modelPessoas.value)
        emit('update:nfeTerceiro', ret.data)
    } catch (error) {
        $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message
        })
    }
}, 500)

const linkMgSis = async (event, row) => {
    var a = document.createElement('a');
    a.target = "_blank";
    a.href = process.env.MGSIS_URL + "index.php?r=nfeTerceiro/view&id=" + row.codnfeterceiro
    a.click();
}

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
    () => filtroNfeTerceiro(),
    { deep: true }
);

onMounted(() => {
    filtroDesde()
    filtroNfeTerceiro()
})

</script>

<template>
    <q-card class="no-shadow" bordered>
        <q-card-section class="q-pa-none">
            <q-table title="Nfe Terceiro" :filter="filter" :rows="nfeTerceiro" :columns="columns"
                v-model:pagination="pagination" no-data-label="Nenhuma nfe de terceiro encontrada" :separator="separator"
                emit-value @row-click="linkMgSis">

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