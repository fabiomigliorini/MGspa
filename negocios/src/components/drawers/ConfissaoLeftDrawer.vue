<script setup></script>
<template>
  <q-item-label header> Confissões de Dívida </q-item-label>

  <!-- DIGITALIZAR -->
  <q-item clickable tag="a" :to="'/confissao/'" v-ripple exact-active-class="bg-blue-1">
    <q-item-section avatar>
      <q-avatar icon="photo_camera" round color="primary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis"> Digitalizar </q-item-label>
    </q-item-section>
  </q-item>
  <q-separator />

  <!-- FALTANDO DIGITALIZAR -->
  <q-item clickable tag="a" :to="'/confissao/faltando'" v-ripple exact-active-class="bg-blue-1">
    <q-item-section avatar>
      <q-avatar icon="checklist" color="primary" text-color="white" />
    </q-item-section>
    <q-item-section>
      <q-item-label class="ellipsis"> Faltando Digitalizar </q-item-label>
    </q-item-section>
  </q-item>
  <q-separator />
</template>
