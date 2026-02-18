<script setup>
import { empresaStore } from "src/stores/empresa";

const sEmpresa = empresaStore();

</script>

<template>
  <q-list>
    <q-item-label header>Filtros</q-item-label>
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sEmpresa.filtroPesquisa.empresa"
          label="Nome da Empresa"
          clearable
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </q-item-section>
    </q-item>
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sEmpresa.filtroPesquisa.codempresa"
          label="Código da Empresa"
          clearable
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </q-item-section>
    </q-item>
  </q-list>
</template>
