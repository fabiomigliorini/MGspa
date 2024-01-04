
<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'

const sPessoa = pessoaStore();

onMounted(async () => {
    const ret = await sPessoa.buscagrupoCliente()
    opcoes.value = ret.data.data
})

const opcoes = ref([]);

</script>

<template>
    <q-select outlined use-input input-debounce="0" label="Grupo Cliente" :options="opcoes"
        option-label="grupocliente" option-value="codgrupocliente" map-options emit-value clearable
        behavior="menu">

        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                    Nenhum resultado encontrado.
                </q-item-section>
            </q-item>
        </template>
    </q-select>
</template>