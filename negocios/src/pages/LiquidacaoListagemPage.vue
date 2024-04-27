<script setup>
import { ref, watch } from "vue";
import { debounce } from "quasar";
import { iconeNegocio, corIconeNegocio } from "../utils/iconeNegocio.js";
import { liquidacaoStore } from "src/stores/liquidacao";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sLiquidacao = liquidacaoStore();
const scrollRef = ref(null);

const onLoad = async (index, done) => {
  await sLiquidacao.getLiquidacoesPaginacao();
  if (sLiquidacao.paginacao.current_page >= sLiquidacao.paginacao.last_page) {
    done(true);
  } else {
    done(false);
  }
};

const inicializa = debounce(async () => {
  await sLiquidacao.getLiquidacoes();
  try {
    scrollRef.value.reset();
    scrollRef.value.resume();
  } catch (error) {}
});

watch(
  () => sLiquidacao.filtro,
  () => {
    inicializa();
  },
  { deep: true }
);

const statusClass = (liq) => {
  if (liq.estornado) {
    return "bg-deep-orange-1 text-deep-orange-10";
  }
  return "";
};

const iconeLiquidacao = (liq) => {
  if (liq.debito > liq.credito) {
    return "mdi-checkbook-arrow-left";
  } else if (liq.debito < liq.credito) {
    return "mdi-checkbook-arrow-right";
  }
  return "mdi-checkbook";
};

const corIconeLiquidacao = (liq) => {
  if (liq.debito > liq.credito) {
    return "secondary";
  } else if (liq.debito < liq.credito) {
    return "negative";
  }
  return "grey";
};
</script>
<template>
  <q-page>
    <!-- <pre>
      {{ sLiquidacao.listagem[0] }}
    </pre> -->
    <div
      v-if="sLiquidacao.listagem.length == 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" color="" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>
    <q-list v-else>
      <q-infinite-scroll @load="onLoad" ref="scrollRef">
        <template :key="index" v-for="(item, index) in sLiquidacao.listagem">
          <!-- <q-item
            :to="'/liquidacao/' + item.codliquidacaotitulo"
            :class="statusClass(item)"
            class="row"
          > -->
          <q-item :class="statusClass(item)" class="row">
            <!-- ICONE -->
            <q-item-section avatar>
              <q-avatar
                :icon="iconeLiquidacao(item)"
                :color="corIconeLiquidacao(item)"
                text-color="white"
              />
            </q-item-section>

            <!-- PORTADOR -->
            <q-item-section class="col-2">
              <q-item-label lines="1">
                {{ item.portador }}
              </q-item-label>
              <q-item-label class="ellipsis" caption>
                #{{ String(item.codliquidacaotitulo).padStart(8, "0") }}
              </q-item-label>
            </q-item-section>

            <!-- CREDITO -->
            <q-item-section class="col-xs-2 col-sm-1">
              <q-item-label class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(item.credito)
                }}
              </q-item-label>
              <q-item-label class="ellipsis text-right" caption>
                Recebido
              </q-item-label>
            </q-item-section>

            <!-- DEBITO -->
            <q-item-section class="col-xs-2 col-sm-1">
              <q-item-label class="text-right">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(item.debito)
                }}
              </q-item-label>
              <q-item-label class="ellipsis text-right" caption>
                Pago
              </q-item-label>
            </q-item-section>

            <!-- PESSOA/VENDEDOR/COD/NATUREZA -->
            <q-item-section>
              <q-item-label>
                {{ item.fantasia }}
              </q-item-label>
              <q-item-label caption>
                <span v-if="item.codpix"> PIX </span>
                <span v-if="item.codpagarmepedido"> PagarMe </span>
                <span v-if="item.codcheque"> Cheque </span>
                <span v-if="item.tipo"> {{ item.nometipo }} </span>
                <span v-if="item.parcelas > 1">
                  {{ item.parcelas }} Parcelas
                </span>
                <span v-if="item.autorizacao"> {{ item.autorizacao }} </span>
                <span v-if="item.bandeira"> {{ item.nomebandeira }} </span>
                <span v-if="item.integracao"> Pagamento Integrado </span>
                <span v-if="item.codpessoacartao"> {{ item.parceiro }} </span>
              </q-item-label>
            </q-item-section>

            <!-- OBSERVACAO -->
            <q-item-section class="gt-sm col-sm-3 col-md-2 col-lg-1">
              <q-item-label caption lines="3" style="white-space: pre-line">
                {{ item.observacao }}
              </q-item-label>
            </q-item-section>

            <!-- DATA/STATUS -->
            <q-item-section
              class="col-xs-4 col-sm-3 col-md-2 col-lg-1 ellipsis"
              side
            >
              <q-item-label caption>
                {{ moment(item.transacao).format("DD/MM/YY") }}
              </q-item-label>
              <q-item-label caption>
                {{ item.usuario }}
              </q-item-label>
              <q-item-label caption v-if="item.codpdv">
                {{ item.pdv }}
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
