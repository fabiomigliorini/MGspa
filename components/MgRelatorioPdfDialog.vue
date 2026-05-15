<script setup>
import { useDialogPluginComponent } from "quasar";

defineProps({
  pdfUrl: { type: String, required: true },
  title: { type: String, default: "Relatório" },
});

defineEmits([...useDialogPluginComponent.emits]);

const { dialogRef, onDialogHide } = useDialogPluginComponent();
</script>

<template>
  <q-dialog ref="dialogRef" @hide="onDialogHide">
    <q-card bordered flat style="width: 90vw; height: 90vh; max-width: 90vw">
      <q-card-section
        class="text-grey-9 text-overline row items-center q-py-sm text-h2 text-uppercase"
      >
        {{ title }}
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="close"
          size="sm"
          color="grey-7"
          v-close-popup
        />
      </q-card-section>
      <q-card-section class="q-pt-none" style="height: calc(90vh - 50px)">
        <iframe
          :src="pdfUrl"
          style="width: 100%; height: 100%; border: none"
        ></iframe>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
