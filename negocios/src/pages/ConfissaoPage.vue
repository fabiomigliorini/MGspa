<script setup>
import MgSlimBatch from "src/utils/pqina/slim/MgSlimBatch.vue";
import { confissaoStore } from "src/stores/confissao";

const sConfissao = confissaoStore();
</script>
<template>
  <q-page>
    <div class="row q-pa-md">
      <div style="max-width: 300px;  margin: auto;">
        <mg-slim-batch ratio="1:2" pasta="confissao" @upload="dialogConfissao = false" />
        <q-input type="number" min="0" class="q-mt-md" input-class="text-right" step="1" outlined
          v-model="sConfissao.codnegocio" :disable="sConfissao.encontrados == 1" />
        <q-input type="number" min="0" class="q-my-md" input-class="text-right" step="0.01" outlined
          v-model="sConfissao.valor" :disable="sConfissao.encontrados == 1" />
      </div>
    </div>
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="upload" color="secondary" :disable="sConfissao.encontrados < 1" @click="sConfissao.upload()"
        v-if="sConfissao.encontrados == 1" />
      <q-btn fab icon="find_in_page" color="accent" :disable="sConfissao.imagem == null" @click="sConfissao.procurar()"
        v-if="sConfissao.encontrados != 1 && sConfissao.imagem != null" />
    </q-page-sticky>
  </q-page>
</template>
