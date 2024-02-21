
<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.buscagrupoCliente()
    opcoes.value = ret.data.data
})

const opcoes = ref([]);


const filterFn = async (val, update) => {
    if (val === '') {
        update(async() => {
            const ret = await sPessoa.buscagrupoCliente()
            opcoes.value = ret.data.data
        })
        return
    }

    update(() => {
        const needle = val.toLowerCase()
        opcoes.value = opcoes.value.filter(v => v.grupocliente.toLowerCase().indexOf(needle) > -1)
    })
}

</script>

<template>
    <q-select outlined use-input input-debounce="0" label="Grupo Cliente" :options="opcoes" option-label="grupocliente"
        option-value="codgrupocliente" map-options emit-value clearable behavior="menu" @filter="filterFn">

        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Nenhum resultado encontrado.
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>