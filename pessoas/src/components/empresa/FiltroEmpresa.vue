<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { empresaStore } from 'src/stores/empresa'

const sEmpresa = empresaStore()

const emit = defineEmits(['buscar'])
</script>

<template>
  <div class="q-pa-none q-pt-sm">
    <q-card flat>
      <q-list>
        <q-item-label header>
          Filtro Empresa
          <q-btn icon="replay" @click="emit('buscar')" flat round no-caps />
        </q-item-label>
      </q-list>
    </q-card>
    <q-form @change="emit('buscar')">
      <div class="q-pa-md q-gutter-md">
        <MgInputValor
          v-model="sEmpresa.filtroPesquisa.codempresa"
          :decimals="0"
          :grouping="false"
          label="#"
        />
        <MgInput outlined v-model="sEmpresa.filtroPesquisa.empresa" label="Empresa" autofocus />
      </div>
    </q-form>
  </div>
</template>
