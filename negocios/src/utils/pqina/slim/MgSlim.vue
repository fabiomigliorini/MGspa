<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import Slim from "./slim.module.js";
import { negocioStore } from "stores/negocio";

const sNegocio = negocioStore();
var cropper = null;

const emit = defineEmits(["upload"]);

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

const inicializar = async () => {
  cropper = new Slim(refSlim.value, {
    ratio: props.ratio,
    mimetypes: "image/jpeg,text/plain",
    instantEdit: true,
    uploadBase64: true,
    forceType: "jpeg",
    label: "Clique para adicionar uma imagem!",
    jpegCompression: 30,
    // forceSize: "400.800",
    willSave: function (data, ready) {
      sNegocio
        .uploadAnexo(props.pasta, data.output.image)
        .then(() => {
          cropper.remove();
        })
        .finally(() => {
          ready(false);
          emit("upload");
        });
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
  <div
    class="slim"
    ref="refSlim"
    style="
      min-width: 250px;
      min-height: 300px;
      max-height: 60vh;
      border: 1px dashed lightgrey;
    "
  >
    <slot></slot>
  </div>
</template>
<style lang="css">
@import "./slim.min.css";
</style>
