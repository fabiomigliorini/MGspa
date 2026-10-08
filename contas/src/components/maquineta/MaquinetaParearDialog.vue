<script setup>
// Parear de novo (SafraPay): QR Code para o pinpad novo; a maquineta do pinpad anterior é
// inativada.
import { storeToRefs } from 'pinia'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'

const store = useMaquinetaStore()
const { dialogParear, registro, salvando, qr } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="dialogParear">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        PAREAR {{ registro?.apelido }}
      </q-card-section>
      <q-separator inset />
      <q-card-section class="column items-center">
        <img v-if="qr.qrcode" :src="qr.qrcode" style="width: 220px; height: 220px" alt="QR Code" />
        <q-spinner-dots v-else-if="salvando" color="primary" size="32px" />
        <div v-else class="text-negative text-center">Não foi possível gerar o QR Code.</div>
        <div class="text-caption text-grey-7 q-mt-sm text-center">
          Leia o QR Code no pinpad novo e clique em Confirmar leitura. A maquineta do pinpad
          anterior é inativada.
        </div>
      </q-card-section>
      <q-separator inset />
      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
        <q-btn
          v-if="!qr.qrcode"
          flat
          label="Gerar QR Code"
          :loading="salvando"
          @click="store.gerarQrCode(registro.codmaquineta)"
        />
        <q-btn
          v-else
          flat
          label="Confirmar leitura"
          :loading="salvando"
          @click="store.confirmarLeitura()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
