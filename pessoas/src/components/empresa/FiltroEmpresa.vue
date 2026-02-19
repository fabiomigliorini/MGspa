<script setup>
import { empresaStore } from "src/stores/empresa";

const sEmpresa = empresaStore();

const emit = defineEmits(["buscar"]);
</script>

<template>
  <div class="q-pa-none q-pt-sm">
    <q-card flat>
      <q-list>
        <q-item-label header>
          Filtro Empresa
          <q-btn
            icon="replay"
            @click="emit('buscar')"
            flat
            round
            no-caps
          />
        </q-item-label>
      </q-list>
    </q-card>
    <q-form @change="emit('buscar')">
      <div class="q-pa-md q-gutter-md">
        <q-input
          outlined
          v-model="sEmpresa.filtroPesquisa.codempresa"
          label="#"
          type="number"
        />
        <q-input
          outlined
          v-model="sEmpresa.filtroPesquisa.empresa"
          label="Empresa"
          autofocus
        />
      </div>
    </q-form>
  </div>
</template>
