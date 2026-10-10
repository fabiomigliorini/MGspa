<script setup>
import { ref, onMounted } from 'vue'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico'
import { useRoute } from 'vue-router'
import MgSelect from '@components/MgSelect.vue'

const grupoEconomico = GrupoEconomicoStore()
const route = useRoute()

onMounted(async () => {
  if (route.path.search('grupoeconomico') == 1) {
    pessoas()
  }
})

const pessoas = async () => {
  const ret = await grupoEconomico.getGrupoEconomico(route.params.id)
  opcoes.value = ret.data.data.PessoasdoGrupo
}

const opcoes = ref([])
</script>

<template>
  <MgSelect
    outlined
    dense
    :options="opcoes"
    map-options
    emit-value
    option-label="fantasia"
    option-value="codpessoa"
    clearable
  />
</template>
