<script setup>
// Juntar a maquineta manual (criada por serial errado) com outra: os pagamentos passam para a
// escolhida e esta é excluída. Emite a que ficou.
import { storeToRefs } from 'pinia'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'

const emit = defineEmits(['juntada'])
const store = useMaquinetaStore()
const { dialogJuntar, juntarDestino, registro, salvando } = storeToRefs(store)

const submit = async () => {
  const destino = await store.juntar()
  if (destino) emit('juntada', destino)
}
</script>

<template>
  <q-dialog v-model="dialogJuntar">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">JUNTAR MAQUINETA</q-card-section>
      <q-form @submit.prevent="submit">
        <q-separator inset />
        <q-card-section>
          <div class="text-body2 q-mb-md">
            Os pagamentos de <b>{{ registro?.apelido }}</b> passam para a maquineta escolhida, e
            <b>{{ registro?.apelido }}</b> é excluída.
          </div>
          <MgSelectMaquineta
            v-model="juntarDestino"
            label="Juntar com"
            :codpessoa="registro?.codpessoa"
            :excluir="registro?.codmaquineta"
            autofocus
            lazy-rules
            :rules="[(v) => !!v]"
          />
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Juntar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
