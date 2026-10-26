<script setup>
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import { requisicaoGeradorStore, SITUACOES, ABCS, ORDENS } from 'stores/requisicaoGerador'

// Os filtros das listas do gerador (TASK-258), os mesmos nas marcas e nos produtos: a pesquisa,
// a urgência, o ABC e a ordem. Urgência, ABC e ordem ficam no rascunho (valem nas duas listas e
// sobrevivem a fechar a tela); a pesquisa é de cada lista.
defineProps({
  rotuloBusca: { type: String, default: 'Pesquisar' },
})
const busca = defineModel('busca', { type: String, default: '' })
const emit = defineEmits(['pesquisar'])

const sGerador = requisicaoGeradorStore()
</script>

<template>
  <div class="row q-col-gutter-md">
    <div class="col-12 col-md-4">
      <MgInput
        v-model="busca"
        :label="rotuloBusca"
        clearable
        autofocus
        @keydown.enter.prevent="emit('pesquisar')"
        @clear="emit('pesquisar')"
      >
        <template #append>
          <q-icon name="search" class="cursor-pointer" @click="emit('pesquisar')" />
        </template>
      </MgInput>
    </div>
    <div class="col-12 col-sm-4 col-md-3">
      <MgSelect
        v-model="sGerador.situacao"
        :options="SITUACOES"
        label="Urgência"
        emit-value
        map-options
      />
    </div>
    <div class="col-6 col-sm-4 col-md-2">
      <MgSelect v-model="sGerador.abc" :options="ABCS" label="ABC" emit-value map-options />
    </div>
    <div class="col-6 col-sm-4 col-md-3">
      <MgSelect v-model="sGerador.ordem" :options="ORDENS" label="Ordem" emit-value map-options />
    </div>
  </div>
</template>
