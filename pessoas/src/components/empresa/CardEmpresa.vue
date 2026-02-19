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
  <router-link
    :to="'/empresa/' + empresa.codempresa"
    class="full-height link-card"
  >
    <q-card bordered flat class="cursor-pointer full-height q-pb-md">
      <!-- HEADER -->
      <q-card-section class="text-grey-9 text-overline q-pb-none">
        <div class="ellipsis-2-lines titulo-empresa">
          #{{ empresa.codempresa }} {{ empresa.empresa }}
        </div>
      </q-card-section>

      <q-separator inset class="q-mt-sm" />

      <!-- DETALHES -->
      <q-list>
        <!-- MODO EMISSÃO NFCe -->
        <q-item>
          <q-icon color="primary" name="receipt" size="xs" class="q-mr-sm" />
          <span class="text-caption text-grey-7"
            >Modo Emissão NFCe:
            <q-badge
              :color="empresa.modoemissaonfce === 1 ? 'green' : 'orange'"
              :label="modoEmissaoLabel"
            />
          </span>
        </q-item>

        <!-- CONTINGÊNCIA -->
        <q-item v-if="contingenciaFormatada" dense>
          <q-icon color="orange" name="warning" size="xs" class="q-mr-sm" />

          <span class="ellipsis text-caption text-grey-7"> Contingência:</span>
          <span class="text-caption q-ml-sm">{{ contingenciaFormatada }}</span>
        </q-item>
      </q-list>
    </q-card>
  </router-link>
</template>

<style scoped>
.titulo-empresa {
  line-height: 1.3;
  font-size: 1rem;
}

.codigo-empresa {
  text-transform: none;
}

.link-card {
  text-decoration: none;
  color: inherit;
}
</style>
