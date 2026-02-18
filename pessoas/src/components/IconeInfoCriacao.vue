<script setup>
import { formataData } from "src/utils/formatador";

defineProps({
  usuariocriacao: String,
  criacao: String,
  usuarioalteracao: String,
  alteracao: String,
});
</script>

<template>
  <q-icon color="grey-7" name="info" class="q-ml-xs">
    <q-tooltip>
      <div>
        Criado por: {{ usuariocriacao }} em
        {{ formataData(criacao) }}
      </div>
      <div v-if="alteracao">
        Alterado por: {{ usuarioalteracao }} em
        {{ formataData(alteracao) }}
      </div>
    </q-tooltip>
  </q-icon>
</template>
