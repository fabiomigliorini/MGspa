<script setup>
import MgConfissaoScanner from '@components/MgConfissaoScanner.vue'
import { confissaoStore } from '@components/stores/confissaoStore'
import { sincronizacaoStore } from 'src/stores/sincronizacao'

const sSinc = sincronizacaoStore()
confissaoStore().configurar({ fixos: { pdv: sSinc.pdv.uuid } })
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
