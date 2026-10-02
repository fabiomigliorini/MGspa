<script setup>
// Recorte de imagem (Slim, pqina) para anexar foto: confissão assinada, imagem do negócio, borderô
// da maquineta. Só recorta e devolve o JPEG em base64 pelo evento `imagem`; quem usa decide o que
// fazer (enviar na hora ou guardar até confirmar). Com `manter`, a imagem fica na tela até alguém
// chamar `remover()`; sem ele, sai assim que é entregue.
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Slim from './anexo/slim/slim.module.js'

const props = defineProps({
  ratio: { type: String, default: 'free' },
  label: { type: String, default: 'Clique para adicionar uma imagem!' },
  // tamanho máximo { width, height }; nulo = o da foto
  size: { type: Object, default: null },
  jpegCompression: { type: Number, default: 50 },
  manter: { type: Boolean, default: false },
})

const emit = defineEmits(['imagem', 'removida'])

const refSlim = ref(null)
let cropper = null

const inicializar = () => {
  cropper = new Slim(refSlim.value, {
    ratio: props.ratio,
    mimetypes: 'image/jpeg,text/plain',
    instantEdit: true,
    uploadBase64: true,
    forceType: 'jpeg',
    label: props.label,
    ...(props.size ? { size: props.size } : {}),
    jpegCompression: props.jpegCompression,
    willSave: (data, ready) => {
      emit('imagem', data.output.image)
      ready(props.manter)
      if (!props.manter) {
        cropper.remove()
      }
    },
    willRemove: (data, ready) => {
      emit('removida')
      ready(true)
    },
  })
}

const remover = () => cropper?.remove()

defineExpose({ remover })

onMounted(inicializar)
onBeforeUnmount(() => cropper?.destroy())

watch(
  () => props.ratio,
  () => {
    cropper.destroy()
    inicializar()
  },
)
</script>

<template>
  <div
    ref="refSlim"
    class="slim"
    style="
      min-width: 250px;
      min-height: 300px;
      max-height: 60vh;
      border: 1px dashed lightgrey;
      border-radius: 4px;
    "
  />
</template>

<style lang="css">
@import './anexo/slim/slim.min.css';
</style>
