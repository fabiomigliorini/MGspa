<script setup>
import { ref, watch } from "vue";
import { debounce } from "quasar";

import { listagemStore } from "src/stores/listagem";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sListagem = listagemStore();
const scrollRef = ref(null);

const onLoad = async (index, done) => {
  await sListagem.getNegociosPaginacao();
  if (sListagem.paginacao.current_page >= sListagem.paginacao.last_page) {
    done(true);
  } else {
    done(false);
  }
};

const inicializa = debounce(async () => {
  await sListagem.getNegocios();
  try {
    scrollRef.value.reset();
    scrollRef.value.resume();
  } catch (error) {}
});

watch(
  () => sListagem.filtro,
  () => {
    inicializa();
  },
  { deep: true }
);

const statusClass = (codnegociostatus) => {
  switch (codnegociostatus) {
    case 1:
      return "bg-teal-1 text-teal-10";
    case 2:
      // return "bg-indigo-1 text-indigo-10";
      return "";
    case 3:
      return "bg-deep-orange-1 text-deep-orange-10";
    default:
      return "bg-amber-1 text-amber-10";
  }
};
</script>
<template>
  <q-page>
    <div
      v-if="sListagem.negocios.length == 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" color="" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>
    <q-list v-else>
      <q-infinite-scroll @load="onLoad" ref="scrollRef">
        <template :key="index" v-for="(item, index) in sListagem.negocios">
          <q-item
            :to="'/negocio/' + item.codnegocio"
            :class="statusClass(item.codnegociostatus)"
            class="row"
          >
            <q-item-section class="col-xs-2 col-sm-1">
              <q-item-label class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(item.valortotal)
                }}
              </q-item-label>
              <q-item-label class="text-right" caption>
                {{ item.estoquelocal }}
              </q-item-label>
            </q-item-section>

            <q-item-section>
              <q-item-label> {{ item.fantasia }} </q-item-label>
              <q-item-label
                caption
                class="ellipsis"
                v-if="item.fantasiavendedor"
              >
                {{ item.fantasiavendedor }}
              </q-item-label>

              <q-item-label class="ellipsis" caption>
                #{{ String(item.codnegocio).padStart(8, "0") }}
                {{ item.naturezaoperacao }}
              </q-item-label>
            </q-item-section>
            <q-item-section class="gt-sm">
              <q-item-label caption> {{ item.usuario }} </q-item-label>
              <q-item-label caption> {{ item.pdv }} </q-item-label>
            </q-item-section>
            <q-item-section class="gt-md ellipsis">
              <q-item-label caption> {{ item.uuid }} </q-item-label>
            </q-item-section>
            <q-item-section
              class="col-xs-4 col-sm-3 col-md-2 col-lg-1 ellipsis"
              side
            >
              <q-item-label caption>
                {{ moment(item.lancamento).format("DD/MM/YY HH:mm:ss") }}
              </q-item-label>
              <q-item-label caption>
                {{ item.negociostatus }}
              </q-item-label>
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
  </q-page>
</template>
