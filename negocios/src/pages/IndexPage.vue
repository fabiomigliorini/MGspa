<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { negocioStore } from "stores/negocio";
import { produtoStore } from "stores/produto";
import ListagemProdutos from "components/offline/ListagemProdutos.vue";
import InputBarras from "components/offline/InputBarras.vue";
import DialogSincronizacao from "components/offline/DialogSincronizacao.vue";
import { api } from "boot/axios";
import { Notify } from "quasar";

const route = useRoute();
const router = useRouter();
const sNegocio = negocioStore();
const sProduto = produtoStore();
const dialogRomaneio = ref(false);

const hotkeys = (event) => {
  // TODO: Anexar F das formas de pagamentos
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

    case "F3":
      event.preventDefault();
      fechar();
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
  router.push("/offline/" + sNegocio.negocio.uuid);
};

const carregareOuCriarNegocio = async () => {
  const uuid = route.params.uuid;
  if (uuid) {
    const ret = await sNegocio.carregar(uuid);
    if (ret != undefined) {
      return;
    }
  }
  vazioOuCriar();
};

// TODO: Oferecer pra Gerar Nota/Imprimir Romaneio/Etc
const fechar = async () => {
  sNegocio.fechar();
};

const urlRomaneio = computed({
  get() {
    return (
      process.env.API_BASE_URL +
      "/api/v1/pdv/negocio/" +
      sNegocio.negocio.codnegocio +
      "/romaneio"
    );
  },
});

const romaneio = async () => {
  dialogRomaneio.value = true;
};

const imprimirRomaneio = async () => {
  await api.post(
    "/api/v1/pdv/negocio/" +
      sNegocio.negocio.codnegocio +
      "/romaneio/imprimir/" +
      sNegocio.padrao.impressora
  );
  Notify.create({
    type: "positive",
    message: "Impressão Solicitada!",
  });
  dialogRomaneio.value = false;
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
    <div class="q-pa-md row q-col-gutter-lg" v-if="sNegocio.podeEditar">
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

    <q-dialog v-model="dialogRomaneio" full-height full-width>
      <q-card style="height: 100%">
        <!-- <q-card-section>
          <div class="text-h6">Romaneio</div>
        </q-card-section> -->

        <q-card-section style="height: 91%" class="q-pb-none">
          <iframe
            style="width: 100%; height: 100%; border: none"
            :src="urlRomaneio"
          ></iframe>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            color="primary"
            flat
            label="Imprimir"
            @click="imprimirRomaneio()"
            :disable="sNegocio.padrao.impressora == null"
          />
          <q-btn color="primary" flat label="Fechar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-page-scroller
      position="bottom-left"
      :scroll-offset="150"
      :offset="[18, 18]"
    >
      <q-btn fab icon="keyboard_arrow_up" color="secondary" />
    </q-page-scroller>

    <q-page-sticky
      position="bottom-right"
      :offset="[18, 18]"
      v-if="sNegocio.negocio"
    >
      <div class="q-gutter-sm">
        <q-btn
          fab
          icon="print"
          color="primary"
          @click="romaneio()"
          v-if="sNegocio.negocio.codnegociostatus == 2"
        >
          <q-tooltip class="bg-accent">Romaneio</q-tooltip>
        </q-btn>
        <q-btn
          fab
          icon="send"
          color="primary"
          @click="fechar()"
          v-if="sNegocio.negocio.codnegociostatus == 1"
        >
          <q-tooltip class="bg-accent">Fechar (F3)</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
