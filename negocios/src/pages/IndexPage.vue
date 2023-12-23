<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { negocioStore } from "stores/negocio";
import { produtoStore } from "stores/produto";
import ListagemProdutos from "components/offline/ListagemProdutos.vue";
import InputBarras from "components/offline/InputBarras.vue";
import DialogSincronizacao from "components/offline/DialogSincronizacao.vue";

const route = useRoute();
const router = useRouter();
const sNegocio = negocioStore();
const sProduto = produtoStore();

const hotkeys = (event) => {
  switch (event.key) {
    // Joga foco no codigo de barras
    case "1":
    case "2":
    case "3":
    case "4":
    case "5":
    case "6":
    case "7":
    case "8":
    case "9":
    case "0":
    case "V": // Comanda Vendedor (Ex VDD00010022)
    case "N": // Comanda Negocio (Ex NEG03386672)
      if (
        document.activeElement.tagName.toLowerCase() != "input" &&
        document.activeElement.tagName.toLowerCase() != "textarea"
      ) {
        const element = document.getElementById("inputBarras");
        if (element) {
          element.focus();
        }
      }
      break;

    case "F1":
      event.preventDefault();
      sProduto.dialogPesquisa = true;
      break;

    case "F2":
      event.preventDefault();
      vazioOuCriar();
      break;

    default:
      break;
  }
};

const vazioOuCriar = async () => {
  const neg = await sNegocio.carregarPrimeiroVazioOuCriar();
  try {
    var audio = new Audio("registradora.mp3");
    audio.play();
  } catch (error) {}
  router.push("/offline/" + sNegocio.negocio.id);
};

const carregareOuCriarNegocio = async () => {
  const id = route.params.id;
  if (id) {
    const ret = await sNegocio.carregar(id);
    if (ret != undefined) {
      return;
    }
  }
  vazioOuCriar();
};

onMounted(() => {
  carregareOuCriarNegocio();
  document.addEventListener("keydown", hotkeys);
});
onUnmounted(() => {
  document.removeEventListener("keydown", hotkeys);
});
</script>

<template>
  <q-page v-if="sNegocio.negocio">
    <div class="q-pa-md row q-col-gutter-lg">
      <div class="col-12">
        <input-barras></input-barras>
      </div>
    </div>

    <div class="col-lg-10 q-sm-9 q-xs-12">
      <div class="row">
        <div class="col q-pb-sm q-pr-sm"></div>
      </div>
    </div>
    <listagem-produtos />
    <dialog-sincronizacao />

    <q-page-scroller
      position="bottom-right"
      :scroll-offset="150"
      :offset="[18, 18]"
    >
      <q-btn fab icon="keyboard_arrow_up" color="secondary" />
    </q-page-scroller>
  </q-page>
</template>
