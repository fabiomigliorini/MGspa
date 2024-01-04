<script setup>

import { ref, watch, computed } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import SelectPessoas from './SelectPessoas.vue'

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
    //   { name: 'codproduto', label: '#Cod Produto', field: 'codproduto', align: 'top-left' },
    { name: 'produto', label: 'Produto', field: 'produto', align: 'top-left' },
    { name: 'variacao', label: 'Variação', field: 'variacao', align: 'top-left' },
    { name: 'lancamento', label: 'Lançamento', field: 'lancamento', align: 'top-left', format: (val, row) => Documentos.formataData(val) },
    // { name: 'codnegocio', label: 'Cod Negócio', field: 'codnegocio', align: 'top-left' },
    { name: 'negocios', label: 'Negócios', field: 'negocios', align: 'top-left' },
    { name: 'quantidade', label: 'Quantidade', field: 'quantidade', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}` },
    { name: 'valortotal', label: 'Valor Total', field: 'valortotal', align: 'top-left', format: (val, row) => `${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },

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


const filtroTotaisNegocioPessoa = debounce(async () => {
    var date = modelPessoas.value.desde
    if (modelPessoas.value.desde) {
        modelPessoas.value.desde = Documentos.dataFormatoSql(modelPessoas.value.desde)
    }
    
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

watch(
    () => modelPessoas.value.codpessoa,
    () => filtroTotaisNegocioPessoa(),
    { deep: true }
);
</script>

<template>
    <q-card class="no-shadow" bordered>
        <q-card-section class="q-pa-none">
            <q-table title="Totais Negócios" :filter="filter" :rows="totaisNegocios" :columns="columns"
                no-data-label="Nenhum negócio encontrado" :separator="separator" emit-value>

                <template v-slot:top-right>
                    <select-pessoas class="q-pa-sm" label="Filtrar por pessoa" v-model="modelPessoas.codpessoa">
                    </select-pessoas>

                    <q-input outlined dense v-model="modelPessoas.desde" @change="filtroTotaisNegocioPessoa()"
                        class="q-pa-sm" mask="##/##/####" label="Desde">
                    </q-input>

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