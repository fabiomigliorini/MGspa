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
  // fecha a janela e só depois chama o onImprimir (ex.: ele abre a escolha da impressora)
  fecharAoImprimir: { type: Boolean, default: false },
})

defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide } = useDialogPluginComponent()

const cardStyle = computed(() =>
  props.size === 'cupom'
    ? { width: '380px', maxWidth: '90vw', height: '90vh' }
    : { width: '910px', maxWidth: '90vw', height: '90vh' },
)

const imprimindo = ref(false)

const imprimir = async () => {
  try {
    await props.onImprimir()
  } catch (e) {
    Notify.create({
      type: 'negative',
      message: e?.response?.data?.message || e?.message || 'Erro ao imprimir',
    })
  }
}

// Com fecharAoImprimir a janela sai antes e o onImprimir roda quando ela terminou
// de fechar: o q-dialog devolve o foco para quem o abriu ao fechar, e isso
// roubaria o foco do que o onImprimir abrir (o filtro da escolha da impressora).
let imprimirAoFechar = false

const handleImprimir = async () => {
  if (!props.onImprimir) return
  if (props.fecharAoImprimir) {
    imprimirAoFechar = true
    dialogRef.value?.hide()
    return
  }
  imprimindo.value = true
  try {
    await imprimir()
  } finally {
    imprimindo.value = false
  }
}

const aoFechar = () => {
  onDialogHide()
  if (imprimirAoFechar) imprimir()
}
</script>

<template>
  <q-dialog ref="dialogRef" @hide="aoFechar">
    <q-card bordered flat class="column" :style="cardStyle">
      <q-card-section
        class="text-grey-9 text-overline row items-center q-py-sm text-h2 text-uppercase"
      >
        {{ title }}
        <q-space />
        <template v-if="onImprimir">
          <q-btn
            flat
            dense
            icon="print"
            color="primary"
            size="sm"
            :label="imprimirLabel"
            :loading="imprimindo"
            @click="handleImprimir"
          />
        </template>
        <q-btn flat round dense icon="close" size="sm" color="grey-7" v-close-popup />
      </q-card-section>
      <q-card-section class="col q-pt-none">
        <iframe :src="pdfUrl" style="width: 100%; height: 100%; border: none"></iframe>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
