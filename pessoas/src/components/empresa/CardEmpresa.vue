<script setup>
import { computed } from "vue";
import moment from "moment";

const props = defineProps({
  empresa: {
    type: Object,
    required: true,
  },
});

const modoEmissaoLabel = computed(() => {
  const modos = {
    1: "Normal",
    9: "Offline",
  };
  return modos[props.empresa.modoemissaonfce] || "-";
});

const contingenciaFormatada = computed(() => {
  if (!props.empresa.contingenciadata) return null;
  return moment(props.empresa.contingenciadata).format("DD/MM/YYYY HH:mm");
});
</script>

<template>
  <router-link :to="'/empresa/' + empresa.codempresa">
    <q-card class="cursor-pointer" flat bordered>
      <q-card-section>
        <div class="row items-center q-gutter-md">
          <div class="text-h6" style="width: 100px">{{ empresa.empresa }}</div>
          <div class="text-caption text-grey">
            Código: {{ empresa.codempresa }}
          </div>
          <q-badge
            :color="empresa.modoemissaonfce === 1 ? 'green' : 'orange'"
            :label="'NFCe: ' + modoEmissaoLabel"
          />
          <div v-if="contingenciaFormatada" class="q-pa-none">
            <q-badge icon="warning" color="orange" text-color="white">
              Contingência: {{ contingenciaFormatada }}
            </q-badge>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </router-link>
</template>

<style scoped>
a {
  text-decoration: none;
  color: inherit;
}
</style>
