<script setup></script>
<template>
  <q-item-label header> Configurações </q-item-label>

  <!-- PagarMe -->
  <q-item clickable tag="a" :to="'/config/pagar-me/'" v-ripple exact-active-class="bg-blue-1">
    <q-item-section avatar>
      <q-avatar icon="mdi-printer-pos-outline" color="primary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis">PagarMe</q-item-label>
    </q-item-section>
  </q-item>
  <q-separator />

  <!-- PRANCHETA -->
  <q-item clickable tag="a" :to="'/config/prancheta/'" v-ripple exact-active-class="bg-blue-1">
    <q-item-section avatar>
      <q-avatar icon="mdi-clipboard-text-outline" color="primary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis">Prancheta</q-item-label>
    </q-item-section>
  </q-item>
  <q-separator />
</template>
