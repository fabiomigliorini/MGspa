<script setup>
import { onMounted, onUnmounted } from "vue";
import { Dialog } from "quasar";
import emitter from "src/utils/emitter";
import { mercosStore } from "stores/mercos";
import { sincronizacaoStore } from "stores/sincronizacao";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sMercos = mercosStore();
const sSinc = sincronizacaoStore();

const importarPedido = async () => {
  Dialog.create({
    title: "Sincronizar com Mercos",
    message: "Deseja rodar a integração com o Mercos para buscar novas vendas?",
    cancel: true,
  }).onOk(async (codnegocio) => {
    await sMercos.importarPedido();
  });
};

onMounted(() => {
  sMercos.atualizarListagem();
  emitter.on('negocioAlterado', () => {
    sMercos.atualizarListagem();
  });
});

onUnmounted(() => {
  emitter.off('negocioAlterado');
});
</script>
<template>
  <!-- <pre>{{ sMercos.pedidos }}</pre> -->
  <q-item-label header>
    Vendas Abertas Mercos
    <q-btn flat color="primary" @click="importarPedido()" icon="mdi-cloud-download-outline" dense>
      <q-tooltip class="bg-accent"> Sincronizar com Mercos </q-tooltip>
    </q-btn>
    <q-btn flat color="primary" @click="sMercos.atualizarListagem()" icon="sync" dense>
      <q-tooltip class="bg-accent"> Atualizar Listagem </q-tooltip>
    </q-btn>
  </q-item-label>
  <template v-if="sMercos.pedidos">
    <template v-for="n in sMercos.pedidos" :key="n.uuid">
      <q-item clickable tag="a" :to="'/offline/' + n.uuid" v-ripple exact-active-class="bg-blue-1">
        <q-item-section avatar>
          <q-avatar icon="mdi-web" color="primary" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis" v-if="n.fantasia && n.codpessoa != 1">
            {{ n.fantasia }}
          </q-item-label>
          <q-item-label caption v-if="n.condicaopagamento" class="ellipsis">
            {{ n.condicaopagamento }}
          </q-item-label>
          <q-item-label caption>
            #{{ n.numero }} {{ moment(n.ultimaalteracaomercos).fromNow() }}
          </q-item-label>
        </q-item-section>
        <q-item-section side class="text-bold">
          <q-item-label>
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(n.valortotal)
            }}
            <q-badge color="orange" text-color="white" rounded v-if="n.codpdv != sSinc.pdv.codpdv" />
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
  </template>

</template>
