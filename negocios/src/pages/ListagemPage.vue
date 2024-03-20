<script setup>
import { onMounted, ref, watch } from "vue";
import { debounce } from "quasar";

import { listagemStore } from "src/stores/listagem";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sListagem = listagemStore();
const scrollTargetRef = ref(null);
const scrollRef = ref(null);

const onLoad = (index, done) => {
  console.log("onLoad");
  sListagem.getNegociosPaginacao().then(() => {
    if (sListagem.paginacao.current_page >= sListagem.paginacao.last_page) {
      done(true);
    } else {
      done();
    }
  });
};

const inicializa = debounce(async () => {
  console.log("inicializa");
  await sListagem.getNegocios();
  scrollRef.value.reset();
  scrollRef.value.resume();
});

watch(
  () => sListagem.filtro,
  () => {
    console.log("watrch");
    inicializa();
  },
  { deep: true }
);

onMounted(() => {
  // sListagem.getNegocios();
});
</script>
<template>
  <q-page>
    <!-- <q-btn label="buscar" @click="inicializa"></q-btn> -->

    <q-list
      ref="scrollTargetRef"
      class="scroll q-ma-md"
      style="max-height: 100vh; overflow: auto; max-width: 600px"
      bordered
    >
      <q-infinite-scroll
        @load="onLoad"
        :offset="250"
        :scroll-target="scrollTargetRef"
        ref="scrollRef"
      >
        <template :key="index" v-for="(item, index) in sListagem.negocios">
          <q-item :to="'/negocio/' + item.codnegocio">
            <q-item-section>
              <q-item-label> {{ item.fantasia }} </q-item-label>
              <q-item-label
                caption
                class="ellipsis"
                v-if="item.fantasiavendedor"
              >
                {{ item.fantasiavendedor }}
              </q-item-label>
              <q-item-label caption>
                {{ moment(item.lancamento).format("DD/MM/YY HH:mm") }}
              </q-item-label>
              <q-item-label caption>
                #{{ String(item.codnegocio).padStart(8, "0") }}
              </q-item-label>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(item.valortotal)
                }}
              </q-item-label>
              <q-item-label class="text-right ellipsis" caption>
                {{ item.naturezaoperacao }}
              </q-item-label>
              <q-item-label class="text-right" caption>
                {{ item.estoquelocal }}
              </q-item-label>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ item.negociostatus }}
              </q-item-label>
              <q-item-label caption> {{ item.usuario }} </q-item-label>
              <q-item-label caption> {{ item.pdv }} </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator />
        </template>

        <template v-slot:loading>
          <div class="row justify-center q-my-md">
            <q-spinner-dots color="primary" size="40px" />
          </div>
        </template>
      </q-infinite-scroll>
    </q-list>

    <pre>{{ sListagem.paginacao }}</pre>
    <pre>{{ sListagem.negocios }}</pre>
    <pre>{{ sListagem.filtro }}</pre>
  </q-page>
</template>
