<script setup>
import { computed } from 'vue'
import { formataCnpjCpf } from "@components/formatters"

const props = defineProps({
  pessoa: { type: Object, required: true },
})

const codigo = computed(() => '#' + String(props.pessoa.codpessoa).padStart(8, '0'))
const documento = computed(() => formataCnpjCpf(props.pessoa.cnpj, props.pessoa.fisica))
</script>

<template>
  <q-item-label class="ellipsis">{{ pessoa.fantasia }}</q-item-label>
  <q-item-label caption class="ellipsis">
    {{ codigo }}
    <span v-if="pessoa.cidade"> · {{ pessoa.cidade }}/{{ pessoa.uf }}</span>
  </q-item-label>
  <q-item-label caption class="ellipsis">
    {{ documento }}
    <span v-if="pessoa.ie"> · IE {{ pessoa.ie }}</span>
  </q-item-label>
</template>
