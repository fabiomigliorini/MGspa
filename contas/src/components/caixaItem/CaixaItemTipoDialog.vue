<script setup>
// Troca a descrição de um tipo do item (descrição + preço) em tudo: entradas, saídas e contagens de
// todos os caixas, fechados inclusive. Só o texto; preço, quantidade e valores não mudam. Se já
// existe o tipo com a descrição nova no mesmo preço, os dois viram um só.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import { formataNumero } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { tipos, tipoDialog, tipoModel, salvando } = storeToRefs(store)

const chave = (d) => (d ?? '').trim().toLowerCase()
const junta = computed(
  () =>
    chave(tipoModel.value.nova) !== chave(tipoModel.value.descricao) &&
    tipos.value.some(
      (t) =>
        t.preco === tipoModel.value.preco && chave(t.descricao) === chave(tipoModel.value.nova),
    ),
)
</script>

<template>
  <q-dialog v-model="tipoDialog">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">EDITAR DESCRIÇÃO</q-card-section>
      <q-form @submit.prevent="store.salvarTipo">
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          {{ tipoModel.descricao || '(sem descrição)' }} de R$ {{ formataNumero(tipoModel.preco) }}:
          a descrição muda em todas as entradas e contagens, de todos os caixas. Preço e quantidades
          continuam iguais.
        </q-card-section>
        <q-card-section>
          <MgInput
            v-model="tipoModel.nova"
            label="Descrição"
            maxlength="50"
            autofocus
            :rules="[(v) => !!v?.trim() || 'Obrigatório']"
            :hint="junta ? 'Já existe com este preço: os dois vão virar um só' : undefined"
          />
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
