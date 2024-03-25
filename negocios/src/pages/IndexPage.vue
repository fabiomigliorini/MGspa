<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { negocioStore } from "stores/negocio";
import { produtoStore } from "stores/produto";
import { usuarioStore } from "stores/usuario";
import { pagarMeStore } from "stores/pagar-me";
import { pixStore } from "stores/pix";
import ListagemProdutos from "components/offline/ListagemProdutos.vue";
import InputBarras from "components/offline/InputBarras.vue";
import DialogSincronizacao from "components/offline/DialogSincronizacao.vue";
import ListagemTitulos from "src/components/offline/ListagemTitulos.vue";
import ListagemNotas from "src/components/offline/ListagemNotas.vue";
import { api } from "boot/axios";
import { Dialog, Notify } from "quasar";
import { db } from "boot/db";

const route = useRoute();
const router = useRouter();
const sNegocio = negocioStore();
const sProduto = produtoStore();
const sUsuario = usuarioStore();
const sPagarMe = pagarMeStore();
const sPix = pixStore();
const dialogRomaneio = ref(false);
const dialogComanda = ref(false);
const listagemNotasRef = ref(null);
const dialogOrcamento = ref(false);
const iFrameOrcamentoRef = ref(null);

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

    case "F1": // Pesquisa
      event.preventDefault();
      sProduto.dialogPesquisa = true;
      break;

    case "F2": // Novo
      event.preventDefault();
      vazioOuCriar();
      break;

    case "F3": // Fechar
      event.preventDefault();
      fechar();
      break;

    case "F4": // Comanda
      event.preventDefault();
      comanda();
      break;

    case "F6": // Dinheiro
      event.preventDefault();
      dinheiro();
      break;

    case "F7": // PagarMe
      event.preventDefault();
      pagarMe();
      break;

    case "F8": // PIX
      event.preventDefault();
      pix();
      break;

    case "F9": // Nota Fiscal
      event.preventDefault();
      romaneioOuNota();
      break;

    default:
      break;
  }
};

const vazioOuCriar = async () => {
  await fecharDialogs();
  const neg = await sNegocio.carregarPrimeiroVazioOuCriar();
  try {
    var audio = new Audio("novo.mp3");
    audio.play();
  } catch (error) { }
  router.push("/offline/" + sNegocio.negocio.uuid);
};

const duplicar = async () => {
  await fecharDialogs();
  Dialog.create({
    title: "Duplicar",
    message: "Tem certeza que deseja duplicar esse negócio?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const neg = await sNegocio.duplicar();
      router.push("/offline/" + sNegocio.negocio.uuid);
      var audio = new Audio("novo.mp3");
      audio.play();
    } catch (error) { }
  });
};

const carregareOuCriarNegocio = async () => {
  const uuid = route.params.uuid;
  if (uuid) {
    const ret = await sNegocio.carregarPeloUuid(uuid);
    if (ret != undefined) {
      return;
    }
  }
  const codnegocio = route.params.codnegocio;
  if (codnegocio) {
    const ret = await sNegocio.carregarPeloCodnegocio(parseInt(codnegocio));
    if (ret != undefined) {
      router.push("/offline/" + sNegocio.negocio.uuid);
      return;
    }
  }
  vazioOuCriar();
};

const fecharDialogs = async () => {
  sNegocio.dialog.pagamentoDinheiro = false;
  sNegocio.dialog.valores = false;
  sNegocio.dialog.pagamentoPix = false;
  sNegocio.dialog.pagamentoPagarMe = false;
  sNegocio.dialog.pagamentoCartaoManual = false;
  sUsuario.dialog.login = false;
  sPagarMe.dialog.detalhesPedido = false;
  dialogRomaneio.value = false;
  dialogComanda.value = false;
  dialogOrcamento.value = false;
};

const abrirDocumentoSeFechado = async () => {
  if (sNegocio.negocio.codnegociostatus == 2) {
    var audio = new Audio("registradora.mp3");
    audio.play();
    romaneioOuNota();
  }
};

const fechar = async () => {
  if (sNegocio.negocio.codnegociostatus != 1) {
    return;
  }
  await fecharDialogs();
  await sNegocio.fechar();
  abrirDocumentoSeFechado();
};

const cancelar = async () => {
  if (
    sNegocio.negocio.codnegociostatus != 1 &&
    sNegocio.negocio.codnegociostatus != 2
  ) {
    return;
  }
  Dialog.create({
    title: "Justificativa de Cancelamento",
    message:
      "Descreva o motivo do cancelamento desse negócio. Precisa conter no mínimo 15 caracteres!",
    prompt: {
      model: "",
      isValid: (val) => val.length > 14,
      outlined: true,
      type: "text", // optional
      placeholder: "Justificativa de Cancelamento...",
      persistent: true,
    },
    cancel: true,
  }).onOk(async (justificativa) => {
    await sNegocio.cancelar(justificativa);
  });
};

const dinheiro = async () => {
  if (sNegocio.negocio.codnegociostatus != 1) {
    return;
  }
  await fecharDialogs();
  sNegocio.dialog.pagamentoDinheiro = true;
};

const pagarMe = async () => {
  if (sNegocio.negocio.codnegociostatus != 1) {
    return;
  }
  if (sPagarMe.dialog.detalhesPedido) {
    await sPagarMe.consultarPedido();
    if (sPagarMe.pedido.status == 2) {
      sPagarMe.dialog.detalhesPedido = false;
    }
    abrirDocumentoSeFechado();
    return;
  }
  await fecharDialogs();
  sNegocio.dialog.pagamentoPagarMe = true;
  return;
};

const pix = async () => {
  if (sNegocio.negocio.codnegociostatus != 1) {
    return;
  }
  if (sPix.dialog.detalhesPixCob) {
    await sPix.consultarPixCob();
    if (sPix.pixCob.status == "CONCLUIDA") {
      sPix.dialog.detalhesPixCob = false;
    }
    abrirDocumentoSeFechado();
    return;
  }
  await fecharDialogs();
  sNegocio.dialog.pagamentoPix = true;
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

const urlComanda = computed({
  get() {
    return (
      process.env.API_BASE_URL +
      "/api/v1/pdv/negocio/" +
      sNegocio.negocio.codnegocio +
      "/comanda"
    );
  },
});

const romaneio = async () => {
  fecharDialogs();
  dialogRomaneio.value = true;
};

const orcamento = async () => {
  fecharDialogs();
  dialogOrcamento.value = true;
};

const comanda = async () => {
  if (!sNegocio.negocio.sincronizado) {
    Notify.create({
      type: "negative",
      message: "Impossível imprimir comanda de um negócio não sincronizado!",
    });
    return;
  }
  if (sNegocio.itensAtivos.length == 0) {
    Notify.create({
      type: "negative",
      message: "Nenhum item!",
    });
    return;
  }
  fecharDialogs();
  imprimirComanda();
  dialogComanda.value = true;
};

const imprimirRomaneio = async () => {
  await api.post(
    "/api/v1/pdv/negocio/" +
    sNegocio.negocio.codnegocio +
    "/romaneio/" +
    sNegocio.padrao.impressora
  );
  Notify.create({
    type: "positive",
    message: "Impressão Solicitada!",
  });
  // dialogRomaneio.value = false;
};

const imprimirComanda = async () => {
  await api.post(
    "/api/v1/pdv/negocio/" +
    sNegocio.negocio.codnegocio +
    "/comanda/" +
    sNegocio.padrao.impressora
  );
  Notify.create({
    type: "positive",
    message: "Impressão Solicitada!",
  });
  // dialogComanda.value = false;
};

const imprimirAbrirRomaneio = async () => {
  await imprimirRomaneio();
  await romaneio();
};

const imprimirOrcamento = () => {
  iFrameOrcamentoRef.value.contentWindow.print();
};


const novaNota = async (modelo) => {
  let nota = null;
  nota = await listagemNotasRef.value.nova(modelo);
  if (nota) {
    await listagemNotasRef.value.enviar(nota);
  }
};

const romaneioOuNotaVendaPadrao = async (modelo) => {
  const integracao = sNegocio.negocio.pagamentos.filter(
    (p) => p.integracao
  ).length;
  const tiposEmitir = [
    //1, //Dinheiro
    //2, //Cheque
    3, //Cartão de Crédito
    4, //Cartão de Débito
    //05=Crédito Loja
    //10=Vale Alimentação
    //11=Vale Refeição
    //12=Vale Presente
    //13=Vale Combustível
    15, //Boleto Bancário
    // 16, //Depósito Bancário
    17, //Pagamento Instantâneo (PIX)
    //18=Transferência bancária, Carteira Digital
    //19=Programa de fidelidade, Cashback, Crédito Virtual
    //90= Sem pagamento
    //99=Outros
  ];
  const emitir = sNegocio.negocio.pagamentos.filter((p) =>
    tiposEmitir.includes(p.tipo)
  ).length;
  // se foi pago por integracao ou por
  if (integracao > 0 || emitir > 0) {
    novaNota(modelo);
    return;
  }
  imprimirAbrirRomaneio();
};

const romaneioOuNotaVenda = async () => {
  if (sNegocio.negocio.codpessoa == 1) {
    // consumidor decide em outra funcao se emite cupom
    romaneioOuNotaVendaPadrao(65);
    return;
  } else {
    // busca a pessoa
    const p = db.pessoa.get(sNegocio.negocio.codpessoa);

    // age de acordo com o cadastro
    switch (p.notafiscal) {
      case 0: // 1 - Sempre
        novaNota(55);
        return;

      case 9: // 9 - Nunca Emitir
        romaneioOuNotaVendaPadrao(65);
        return;

      case 2: // 2 - Somente no Fechamento
      case 0: // 0 - Padrão
      default:
        romaneioOuNotaVendaPadrao(55);
        return;
    }
  }
};

const romaneioOuNota = async () => {
  // se nao estiver fechado cai fora
  if (sNegocio.negocio.codnegociostatus != 2) {
    return;
  }

  // busca natureza
  const nat = await db.naturezaOperacao.get(
    sNegocio.negocio.codnaturezaoperacao
  );

  if (nat.venda) {
    // se for venda decide em outra funcao
    romaneioOuNotaVenda();
    return;
  } else if (nat.transferencia) {
    // se for transferencia já emite a nf
    novaNota(55);
    return;
  }

  // senao romaneio
  imprimirAbrirRomaneio();
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
    <div class="q-pa-md q-col-gutter-md">
      <input-barras v-if="sNegocio.podeEditar" />
      <listagem-notas ref="listagemNotasRef" />
      <listagem-produtos />
      <dialog-sincronizacao />
      <listagem-titulos />
    </div>
    <div style="padding-bottom: 75px"></div>

    <!-- ROMANEIO -->
    <q-dialog v-model="dialogRomaneio" full-height>
      <q-card style="height: 100%">
        <!-- <q-card-section>
          <div class="text-h6">Romaneio</div>
        </q-card-section> -->

        <q-card-section style="height: 91%" class="q-pb-none">
          <iframe style="width: 100%; height: 100%; border: none" :src="urlRomaneio"></iframe>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="primary" flat label="Imprimir" @click="imprimirRomaneio()"
            :disable="sNegocio.padrao.impressora == null" />
          <q-btn color="primary" flat label="Fechar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- COMANDA -->
    <q-dialog v-model="dialogComanda" full-height @hide="vazioOuCriar()">
      <q-card style="height: 100%">
        <!-- <q-card-section>
          <div class="text-h6">Romaneio</div>
        </q-card-section> -->

        <q-card-section style="height: 91%" class="q-pb-none">
          <iframe style="width: 100%; height: 100%; border: none" :src="urlComanda"></iframe>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="primary" flat label="Imprimir" @click="imprimirComanda()"
            :disable="sNegocio.padrao.impressora == null" />
          <q-btn color="primary" flat label="Fechar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ORCAMENTO -->
    <q-dialog v-model="dialogOrcamento" full-height full-width>
      <q-card style="height: 100%">

        <q-card-section style="height: 91%" class="q-pb-none">
          <iframe ref="iFrameOrcamentoRef" style="width: 100%; height: 100%; border: none"
            :src="'/#/offline/' + sNegocio.negocio.uuid + '/orcamento'"></iframe>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="primary" flat label="Imprimir" @click="imprimirOrcamento()" />
          <q-btn color="primary" flat label="Fechar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-page-scroller position="bottom-left" :scroll-offset="150" :offset="[18, 18]">
      <q-btn fab icon="keyboard_arrow_up" color="secondary" />
    </q-page-scroller>

    <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="sNegocio.negocio">
      <div class="q-gutter-sm">
        <!-- DUPLICAR -->
        <q-btn fab icon="content_copy" color="secondary" @click="duplicar()">
          <q-tooltip class="bg-accent">Duplicar</q-tooltip>
        </q-btn>

        <!-- ORCAMENTO -->
        <q-btn fab icon="print" color="secondary" @click="orcamento()" v-if="sNegocio.negocio.codnegociostatus != 3">
          <q-tooltip class="bg-accent">Orçamento</q-tooltip>
        </q-btn>

        <!-- ROMANEIO -->
        <q-btn fab icon="receipt" color="secondary" @click="comanda()" v-if="sNegocio.negocio.codnegociostatus == 1">
          <q-tooltip class="bg-accent">Comanda (F4)</q-tooltip>
        </q-btn>

        <!-- ROMANEIO -->
        <q-btn fab icon="print" color="secondary" @click="romaneio()" v-if="sNegocio.negocio.codnegociostatus == 2">
          <q-tooltip class="bg-accent">Romaneio</q-tooltip>
        </q-btn>

        <!-- FECHAR -->
        <q-btn fab icon="send" color="primary" @click="fechar()" v-if="sNegocio.negocio.codnegociostatus == 1">
          <q-tooltip class="bg-accent">Fechar (F3)</q-tooltip>
        </q-btn>

        <!-- CANCELAR -->
        <q-btn fab icon="delete" color="negative" @click="cancelar()" v-if="sNegocio.negocio.codnegociostatus == 1 ||
    sNegocio.negocio.codnegociostatus == 2
    ">
          <q-tooltip class="bg-accent">Cancelar Negócio</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
