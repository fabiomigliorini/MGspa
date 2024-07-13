<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import Slim from "./slim.module.js";
import { negocioStore } from "stores/negocio";

const sNegocio = negocioStore();
var cropper = null;

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
    // size: props.size,
    instantEdit: true,
    uploadBase64: true,
    forceType: "jpeg",
    label: "!",
    // forceSize: "200.300",
    willSave: function (data, ready) {
      sNegocio
        .uploadAnexo(props.pasta, data.output.image)
        .then(() => {
          cropper.remove();
        })
        .finally(() => {
          ready(false);
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
  <div class="slim" ref="refSlim">
    <slot></slot>
  </div>
</template>
<style lang="css">
@import "./slim.min.css";
</style>
