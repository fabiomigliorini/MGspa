<script setup>

import { ref, onMounted } from 'vue'
import { pessoaStore } from 'stores/pessoa'
import { useRouter } from 'vue-router';

const sPessoa = pessoaStore();
const router = useRouter();

onMounted(async () => {
    const ret = await sPessoa.selectEstado()
    opcoes.value = ret.data
})

const opcoes = ref([]);

</script>

<template>
   <q-select outlined label="Estados" :options="opcoes"
        map-options emit-value option-label="sigla" :option-value="router.currentRoute._value.name === 'pessoanova' ? 'sigla' : 'value'" clearable />
</template>
  