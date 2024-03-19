<script setup></script>
<template>
  <q-item-label header> Configurações </q-item-label>

  <!-- ComputerSettings -->
  <q-item clickable tag="a" :to="'/config/padrao/'" v-ripple>
    <q-item-section avatar>
      <q-avatar icon="shopping_cart" color="secondary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis"> Padrão</q-item-label>
    </q-item-section>
  </q-item>

  <q-separator inset />

  <!-- Listagem PDV -->
  <q-item clickable tag="a" :to="'/config/pdv/'" v-ripple>
    <q-item-section avatar>
      <q-avatar icon="shopping_cart" color="secondary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis"> Dispositivos</q-item-label>
    </q-item-section>
  </q-item>
</template>
