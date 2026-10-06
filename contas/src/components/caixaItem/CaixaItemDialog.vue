<script setup>
// Criar e editar o item do caixa (cadastro mínimo: o nome).
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { dialog, model, isNovo, salvando } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="dialog">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        {{ isNovo ? 'NOVO ITEM DO CAIXA' : 'EDITAR ITEM DO CAIXA' }}
      </q-card-section>
      <q-form @submit.prevent="store.salvar">
        <q-separator inset />
        <q-card-section>
          <MgInput
            v-model="model.item"
            label="Item"
            maxlength="50"
            autofocus
            :rules="[(v) => !!v]"
            lazy-rules
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
