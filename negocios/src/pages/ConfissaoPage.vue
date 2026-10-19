<script setup>
import MgConfissaoScanner from '@components/MgConfissaoScanner.vue'
import { confissaoStore } from '@components/stores/confissaoStore'

// o PDV se identifica pelo cabecalho X-Pdv (boot/axios)
confissaoStore().configurar()
</script>
<template>
  <q-page>
    <div class="row q-pa-md">
      <div style="width: 500px; max-width: 90vw; margin: auto">
        <q-card flat bordered>
          <q-card-section>
            <MgConfissaoScanner />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>
