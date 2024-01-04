<script setup>

import { ref, onMounted, computed } from 'vue'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico';
import { useRoute } from 'vue-router';

const grupoEconomico = GrupoEconomicoStore();
const route = useRoute();

onMounted(async () => {
    if(route.path.search("grupoeconomico") == 1) {
    pessoas()
    }
})

const pessoas = async () => {
    const ret = await grupoEconomico.getGrupoEconomico(route.params.id)
    opcoes.value = ret.data.data.PessoasdoGrupo
}

const opcoes = ref([]);

</script>

<template>
    <q-select outlined dense :options="opcoes" map-options emit-value option-label="fantasia"
        option-value="codpessoa" clearable />
</template>
  