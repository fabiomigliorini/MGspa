<script setup>
import { negocioStore } from "stores/negocio";
import { useRouter } from "vue-router";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const router = useRouter();

const criar = async () => {
  const n = await sNegocio.carregarPrimeiroVazioOuCriar();
  router.push("/offline/" + n.id);
  var audio = new Audio("registradora.mp3");
  audio.play();
};

const formataTempoPercorridoDesde = (desde) => {
  if (!desde) {
    return null;
  }
  return moment(desde).fromNow();
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
    <template v-for="n in sNegocio.negocios" :key="n.id">
      <q-item clickable tag="a" :to="'/offline/' + n.id" v-ripple>
        <q-item-section avatar>
          <q-avatar
            icon="shopping_cart"
            :color="n.sincronizado == true ? 'green' : 'red'"
            text-color="white"
          />
          <!-- <q-avatar color="primary" text-color="white">{{ n.uid }}</q-avatar> -->
          <!-- <q-icon round color="primary" name="delete" /> -->
        </q-item-section>

        <q-item-section>
          <q-item-label>
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(n.valortotal)
            }}
          </q-item-label>
          <q-item-label caption>
            {{ formataTempoPercorridoDesde(n.lancamento) }}
            <!-- {{ formataTempoPercorridoDesde(n.lancamento) }} -->
          </q-item-label>
        </q-item-section>
        <q-item-section side> {{ n.itens.length }} itens </q-item-section>
      </q-item>
      <q-separator inset />
    </template>
  </template>
</template>
