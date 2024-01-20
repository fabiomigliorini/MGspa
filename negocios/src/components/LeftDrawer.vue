<script setup>
import { negocioStore } from "stores/negocio";
import { useRouter } from "vue-router";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const router = useRouter();

const criar = async () => {
  const n = await sNegocio.carregarPrimeiroVazioOuCriar();
  router.push("/offline/" + n.uuid);
  var audio = new Audio("novo.mp3");
  audio.play();
};
</script>
<template>
  <q-item-label header>
    Negocios Abertos
    <q-btn
      flat
      label="F2"
      color="primary"
      @click="criar()"
      icon="add"
      size="md"
      dense
    />
  </q-item-label>
  <template v-if="sNegocio.negocios">
    <template v-for="n in sNegocio.negocios" :key="n.uuid">
      <q-item clickable tag="a" :to="'/offline/' + n.uuid" v-ripple>
        <q-item-section avatar>
          <q-avatar
            icon="shopping_cart"
            :color="n.sincronizado == true ? 'secondary' : 'negative'"
            text-color="white"
          />
          <!-- <q-avatar color="primary" text-color="white">{{ n.uid }}</q-avatar> -->
          <!-- <q-icon round color="primary" name="delete" /> -->
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis" v-if="n.fantasia && n.codpessoa != 1">
            {{ n.fantasia }}
          </q-item-label>
          <q-item-label v-if="n.naturezaoperacao" class="ellipsis">
            {{ n.naturezaoperacao }}
          </q-item-label>
          <q-item-label caption v-if="n.fantasiavendedor" class="ellipsis">
            {{ n.fantasiavendedor }}
          </q-item-label>
          <q-item-label caption v-if="n.lancamento">
            {{ moment(n.lancamento).fromNow() }}
          </q-item-label>
        </q-item-section>
        <q-item-section side class="text-bold">
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(n.valortotal)
          }}
        </q-item-section>
      </q-item>
      <q-separator inset />
    </template>
  </template>
</template>
