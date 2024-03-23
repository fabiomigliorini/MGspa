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
    Abertos
    <q-btn flat label="F2" color="primary" @click="criar()" icon="add" dense>
      <q-tooltip class="bg-accent">> Novo </q-tooltip>
    </q-btn>
  </q-item-label>
  <template v-if="sNegocio.negocios">
    <template v-for="n in sNegocio.negocios" :key="n.uuid">
      <q-item
        clickable
        tag="a"
        :to="'/offline/' + n.uuid"
        v-ripple
        exact-active-class="bg-blue-1"
      >
        <q-item-section avatar>
          <q-avatar
            icon="shopping_cart"
            :color="n.sincronizado == true ? 'secondary' : 'negative'"
            text-color="white"
          />
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
      <q-separator />
    </template>
  </template>

  <q-item-label header>
    Últimos
    <q-btn to="/listagem" icon="checklist_rtl" flat dense color="primary">
      <q-tooltip class="bg-accent"> Listagem </q-tooltip>
    </q-btn>
  </q-item-label>
  <template v-if="sNegocio.ultimos">
    <template v-for="n in sNegocio.ultimos" :key="n.uuid">
      <q-item
        clickable
        tag="a"
        :to="'/offline/' + n.uuid"
        v-ripple
        exact-active-class="bg-blue-1"
      >
        <q-item-section avatar>
          <q-avatar
            icon="shopping_cart"
            :color="n.sincronizado == true ? 'secondary' : 'negative'"
            text-color="white"
          />
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
          <q-item-label class="ellipsis" v-if="n.codnegociostatus == 3">
            <q-chip
              square
              color="negative"
              text-color="white"
              icon="warning"
              size="sm"
              dense
            >
              Cancelado
            </q-chip>
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
  </template>
</template>
