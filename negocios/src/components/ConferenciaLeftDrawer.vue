<script setup>
import { onMounted } from "vue";
import { conferenciaStore } from "src/stores/conferencia";
import SelectPdv from "./selects/SelectPdv.vue";
const sConferencia = conferenciaStore();

onMounted(() => {
  sConferencia.inicializaFiltro();
});
</script>
<template>
  <q-list>
    <q-item-label header>Filtro </q-item-label>

    <!-- PDV -->
    <q-item>
      <q-item-section>
        <select-pdv outlined v-model="sConferencia.filtro.codpdv" label="PDV" clearable />
      </q-item-section>
    </q-item>

    <!-- DIA -->
    <q-item>
      <q-item-section>
        <q-input outlined v-model="sConferencia.filtro.dia" input-class="text-center" label="Dia"
          mask="##/##/#### ##:##">
          <template v-slot:prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="sConferencia.filtro.dia" mask="DD/MM/YYYY HH:mm">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </q-item-section>
    </q-item>
  </q-list>
</template>
