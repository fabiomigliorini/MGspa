<script setup>
import { computed, ref } from 'vue'
import { useDialogPluginComponent, Notify } from 'quasar'

const props = defineProps({
  pdfUrl: { type: String, required: true },
  title: { type: String, default: 'Relatório' },
  size: {
    type: String,
    default: 'a4',
    validator: (v) => ['a4', 'cupom'].includes(v),
  },
  onImprimir: { type: Function, default: null },
  imprimirLabel: { type: String, default: 'Imprimir' },
})

defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide } = useDialogPluginComponent()

const cardStyle = computed(() =>
  props.size === 'cupom'
    ? { width: '500px', maxWidth: '90vw', height: '90vh' }
    : { width: '250px', maxWidth: '90vw', height: '90vh' },
)

const imprimindo = ref(false)

const handleImprimir = async () => {
  if (!props.onImprimir) return
  imprimindo.value = true
  try {
    await props.onImprimir()
  } catch (e) {
    Notify.create({
      type: 'negative',
      message: e?.response?.data?.message || e?.message || 'Erro ao imprimir',
    })
  } finally {
    imprimindo.value = false
  }
}
</script>

<template>
  <q-dialog ref="dialogRef" @hide="onDialogHide">
    <q-card bordered flat class="column" :style="cardStyle">
      <q-card-section
        class="text-grey-9 text-overline row items-center q-py-sm text-h2 text-uppercase"
      >
        {{ title }}
        <q-space />
        <q-btn flat round dense icon="close" size="sm" color="grey-7" v-close-popup />
      </q-card-section>
      <q-card-section class="col q-pt-none">
        <iframe :src="pdfUrl" style="width: 100%; height: 100%; border: none"></iframe>
      </q-card-section>
      <q-card-actions v-if="onImprimir" align="right">
        <q-btn
          flat
          color="primary"
          :label="imprimirLabel"
          icon="print"
          :loading="imprimindo"
          @click="handleImprimir"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
