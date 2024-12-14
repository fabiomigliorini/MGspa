<script setup>
import { Dialog, Notify } from "quasar";
import { api } from "src/boot/axios";
import { negocioStore } from "stores/negocio";
import { sincronizacaoStore } from "src/stores/sincronizacao";

const sNegocio = negocioStore();
const sSinc = sincronizacaoStore();

const informarFaturamento = async (mp) => {
  try {
    const { data } = await api.post(
      "/api/v1/pdv/negocio/" +
      sNegocio.negocio.codnegocio +
      "/mercos/" +
      mp.codmercospedido +
      "/faturamento",
      { pdv: sSinc.pdv.uuid }
    );
    sNegocio.atualizarNegocioPeloObjeto(data.data);
    Notify.create({
      type: 'positive',
      message: 'Faturamento Informado ao Mercos!',
      actions: [{ icon: "close", color: "white" }],
    });
  } catch (error) {
    console.log(error);
    var message = error?.response?.data?.message;
    if (!message) {
      message = error?.message;
    }
    Notify.create({
      type: "negative",
      message: message,
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }
}

const confirmarReimportar = (mp) => {
  Dialog.create({
    title: 'Reimportar',
    message: 'Deseja mesmo reimportar o pedido do Mercos? Todas as alterações feitas poderão ser perdidas.',
    cancel: true,
    // persistent: true
  }).onOk(() => {
    reimportar(mp);
    // console.log('>>>> OK')
  }).onOk(() => {
    // console.log('>>>> second OK catcher')
  }).onCancel(() => {
    // console.log('>>>> Cancel')
  }).onDismiss(() => {
    // console.log('I am triggered on both OK and Cancel')
  })
}

const reimportar = async (mp) => {
  try {
    const { data } = await api.post(
      "/api/v1/pdv/negocio/" +
      sNegocio.negocio.codnegocio +
      "/mercos/" +
      mp.codmercospedido +
      "/reimportar",
      { pdv: sSinc.pdv.uuid }
    );
    await sNegocio.atualizarNegocioPeloObjeto(data.data);
    sNegocio.recalcularValorTotal();
    Notify.create({
      type: 'positive',
      message: 'Pedido reimportado do Mercos!',
      actions: [{ icon: "close", color: "white" }],
    });
  } catch (error) {
    console.log(error);
    var message = error?.response?.data?.message;
    if (!message) {
      message = error?.message;
    }
    Notify.create({
      type: "negative",
      message: message,
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }
}

const urlMercosPedido = (mp) => {
  return process.env.MERCOS_URL_ADM + "pedidos/" + mp.pedidoid + "/detalhar/";
}

const colorIconUpload = (mp) => {
  if (mp.faturamentoid) {
    return 'secondary';
  }
  return 'negative';
}
</script>
<template>
  <template v-if="sNegocio.negocio">
    <q-list>
      <template v-for="mp in sNegocio.negocio.MercosPedidoS" :key="mp.codmercospedido">
        <q-separator />
        <q-item>
          <q-item-section avatar top>
            <template v-if="sNegocio.negocio.codnegociostatus == 2">
              <q-btn @click="informarFaturamento(mp)" round :color="colorIconUpload(mp)"
                icon="mdi-cloud-upload-outline" />
            </template>
            <template v-else>
              <q-btn @click="confirmarReimportar(mp)" round color="secondary" icon="mdi-cloud-download-outline" />
            </template>
          </q-item-section>
          <q-item-section>
            <q-item-label lines="1">
              Mercos #{{ mp.numero }}
            </q-item-label>
            <q-item-label caption v-if="mp.condicaopagamento">
              {{ mp.condicaopagamento }}
            </q-item-label>
            <q-item-label caption v-if="mp.enderecoentrega">
              {{ mp.enderecoentrega }}
            </q-item-label>
          </q-item-section>
          <q-item-section side top>
            <q-btn :href="urlMercosPedido(mp)" round color="secondary" target="_blank" icon="launch" />
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </template>
</template>
