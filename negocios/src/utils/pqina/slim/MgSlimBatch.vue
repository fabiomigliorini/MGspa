<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import { storeToRefs } from 'pinia'
import Slim from "./slim.module.js";
import { confissaoStore } from "src/stores/confissao.js";

const sConfissao = confissaoStore();
var cropper = null;

const emit = defineEmits(["upload"]);
const { imagem } = storeToRefs(sConfissao)

const props = defineProps({
  ratio: {
    type: String,
    default: "free",
  },

  pasta: {
    type: String,
    default: "imagem",
  },
});

const refSlim = ref(null);

watch(imagem, () => {
  if (imagem.value == null) {
    cropper.remove();
  }
})

const inicializar = async () => {
  cropper = new Slim(refSlim.value, {
    ratio: props.ratio,
    mimetypes: "image/jpeg,text/plain",
    instantEdit: true,
    uploadBase64: true,
    forceType: "jpeg",
    label: "Clique para adicionar uma imagem!",
    jpegCompression: 50,
    willSave: function (data, ready) {
      sConfissao.novaImagem(data.output.image)
      ready(true);
    },
    willRemove: function (data, ready) {
      sConfissao.inicializarSugestao();
      ready(true);
    },
  });
};

onMounted(() => {
  inicializar();
});
onBeforeUnmount(() => {
  cropper.destroy();
});
</script>
<template>
  <div class="slim" ref="refSlim" style="
      min-width: 250px;
      min-height: 300px;
      max-height: 60vh;
      border: 1px dashed lightgrey;
      border-radius: 4px;
    ">
    <slot></slot>
  </div>
</template>
<style lang="css">
@import "./slim.min.css";
</style>
