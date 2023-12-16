<template>
  <q-list>
    <q-item-label header> Essential Links </q-item-label>

    <q-item>
      <q-btn
        color="primary"
        label="Novo"
        @click="criar()"
        icon="add"
        class="q-mr-sm"
      />
      <q-btn
        color="primary"
        label="Sinc"
        :loading="sProduto.importacao.rodando"
        :percentage="sProduto.importacao.progresso * 100"
        @click="sProduto.sincronizar()"
        icon="refresh"
      >
        <template v-slot:loading>
          <q-spinner-dots />
        </template>
      </q-btn>
    </q-item>

    <q-item-label header> Negocios Abertos </q-item-label>
    <template v-if="sNegocio.negocios">
      <template v-for="n in sNegocio.negocios" :key="n.uid">
        <q-item clickable tag="a" :to="'/uid/' + n.uid">
          <q-item-section avatar>
            <q-avatar icon="shopping_cart" color="primary" text-color="white" />
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
            </q-item-label>
          </q-item-section>
          <q-item-section side> {{ n.itens.length }} itens </q-item-section>
        </q-item>
      </template>
    </template>

    <!-- <EssentialLink
      v-for="link in essentialLinks"
      :key="link.title"
      v-bind="link"
    /> -->
  </q-list>

  <!-- Sincronizacao  -->
  <q-dialog v-model="sProduto.importacao.rodando" seamless position="bottom">
    <q-card style="width: 350px">
      <q-linear-progress :value="sProduto.importacao.progresso" color="pink" />

      <q-card-section class="row items-center no-wrap">
        <div>
          <div class="text-weight-bold">
            {{
              new Intl.NumberFormat("pt-BR").format(
                sProduto.importacao.totalSincronizados
              )
            }}
            /
            {{
              new Intl.NumberFormat("pt-BR").format(
                sProduto.importacao.totalRegistros
              )
            }}
            Produtos
          </div>
          <div class="text-grey">
            {{ sProduto.importacao.tempoTotal }} Segundos
          </div>
        </div>

        <q-space />

        <q-btn
          flat
          round
          icon="close"
          v-close-popup
          @click="sProduto.abortarSincronizacao()"
        />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script>
import { defineComponent } from "vue";
import { negocioStore } from "stores/negocio";
import { produtoStore } from "stores/produto";
import { useRouter } from "vue-router";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

export default defineComponent({
  name: "LeftDrawer",
  props: {
    title: {
      type: String,
      // required: true
    },

    caption: {
      type: String,
      default: "",
    },

    link: {
      type: String,
      default: "#",
    },

    icon: {
      type: String,
      default: "",
    },
  },
  setup() {
    // const d = new date();
    const sNegocio = negocioStore();
    const sProduto = produtoStore();
    sProduto.abortarSincronizacao();
    const router = useRouter();

    const formataTempoPercorridoDesde = (desde) => {
      if (!desde) {
        return null;
      }
      return moment(desde).fromNow();
    };

    const criar = () => {
      const n = sNegocio.criar();
      router.replace("/uid/" + n.id);
      var audio = new Audio("registradora.mp3");
      audio.play();
    };

    return {
      sNegocio,
      sProduto,
      criar,
      formataTempoPercorridoDesde,
    };
  },
});
</script>
