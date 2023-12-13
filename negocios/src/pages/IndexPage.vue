<template>
  <q-page class="">
    <!-- Sincronizacao  -->
    <q-dialog
      v-model="storeProduto.importacao.rodando"
      seamless
      position="bottom"
    >
      <q-card style="width: 350px">
        <q-linear-progress
          :value="storeProduto.importacao.progresso"
          color="pink"
        />

        <q-card-section class="row items-center no-wrap">
          <div>
            <div class="text-weight-bold">
              {{
                new Intl.NumberFormat("pt-BR").format(
                  storeProduto.importacao.totalSincronizados
                )
              }}
              /
              {{
                new Intl.NumberFormat("pt-BR").format(
                  storeProduto.importacao.totalRegistros
                )
              }}
              Produtos
            </div>
            <div class="text-grey">
              {{ storeProduto.importacao.tempoTotal }} Segundos
            </div>
          </div>

          <q-space />

          <q-btn
            flat
            round
            icon="close"
            v-close-popup
            @click="storeProduto.importacao.rodando = false"
          />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Pesquisa de Produto -->
    <q-dialog
      v-model="dialog.pesquisa"
      maximized
      transition-show="slide-up"
      transition-hide="slide-down"
      @show="$refs.refPesquisa.$el.focus()"
      @hide="$refs.refBarras.$el.focus()"
    >
      <q-card>
        <q-card-section class="bg-primary text-white">
          <q-input
            outlined
            v-model="storeProduto.textoPesquisa"
            label="Pesquisa"
            ref="refPesquisa"
            bg-color="white"
            @keydown.enter.prevent="storeProduto.pesquisar()"
          >
            <template v-slot:append>
              <q-btn
                round
                dense
                flat
                icon="search"
                @click="storeProduto.pesquisar()"
              />
              <q-btn
                round
                dense
                flat
                icon="close"
                @click="dialog.pesquisa = false"
              />
            </template>
          </q-input>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row">
            <template
              v-for="produto in storeProduto.resultadoPesquisa"
              v-bind:key="produto.codprodutobarra"
            >
              <div class="q-pa-sm col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6">
                <q-card
                  flat
                  bordered
                  v-ripple
                  class="cursor-pointer q-hoverable"
                  @click="
                    storeNegocio.adicionarItem(
                      produto.codprodutobarra,
                      produto.barras,
                      produto.codproduto,
                      produto.produto,
                      produto.codimagem,
                      1,
                      produto.preco
                    );
                    dialog.pesquisa = false;
                  "
                >
                  <span class="q-focus-helper"></span>
                  <img :src="urlImagem(produto.codimagem)" />

                  <q-card-section>
                    <div
                      class="absolute"
                      style="top: 0; right: 5px; transform: translateY(-37px)"
                    >
                      <q-chip color="grey-2" text-color="grey-7">
                        {{ produto.sigla }}
                        <template v-if="produto.quantidade > 0">
                          C/{{
                            new Intl.NumberFormat("pt-BR").format(
                              produto.quantidade
                            )
                          }}
                        </template>
                      </q-chip>

                      <!-- <q-btn color="white" text-color="grey-7" icon="edit">
                        teste
                      </q-btn> -->
                      <!-- <q-btn
                        round
                        color="negative"
                        icon="delete"
                        class="q-ma-sm"
                      /> -->
                    </div>

                    <div class="text-h5">
                      <small class="text-grey-7">R$</small>
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "decimal",
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                        }).format(produto.preco)
                      }}
                    </div>

                    <!-- <div class="text-overline text-grey-7">
                      {{ produto.sigla }}
                      <template v-if="produto.quantidade > 0">
                        C/{{
                          new Intl.NumberFormat("pt-BR").format(
                            produto.quantidade
                          )
                        }}
                      </template>
                    </div> -->
                    <div class="text-caption text-grey-7">
                      {{ produto.barras }} |
                      {{ produto.produto }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </template>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Cabecalho adicionar produtos -->
    <div class="row q-pa-md">
      <div class="col-lg-10 q-sm-9 q-xs-12">
        <div class="row">
          <div class="col-sm-3 col-xs-12 q-pb-sm q-pr-sm">
            <q-input
              type="number"
              outlined
              step="0.001"
              label="Quantidade"
              input-class="text-right"
              :model-value="quantidade"
            >
            </q-input>
          </div>
          <div class="col q-pb-sm q-pr-sm">
            <q-input
              type="text"
              outlined
              ref="refBarras"
              v-model="barras"
              label="Barras"
              input-class="text-right"
              @change="buscarBarras()"
            >
              <template v-slot:append>
                <q-btn round dense flat icon="add" @click="buscarBarras()" />
                <q-btn
                  round
                  dense
                  flat
                  icon="search"
                  @click="dialog.pesquisa = true"
                />
                <q-btn
                  round
                  dense
                  flat
                  :loading="storeProduto.importacao.rodando"
                  :percentage="storeProduto.importacao.progresso * 100"
                  @click="storeProduto.sincronizar()"
                  icon="refresh"
                >
                  <template v-slot:loading>
                    <q-spinner-dots />
                  </template>
                </q-btn>
              </template>
            </q-input>
          </div>
        </div>
      </div>
      <div class="col-lg-2 q-sm-3 q-xs-12">DADOS NEGOCIO</div>
    </div>

    <!-- listagem de produtos -->
    <div class="row q-pa-sm" v-if="storeNegocio.negocio">
      <div
        class="col-xs-6 col-sm-4 col-md-3 col-lg-2 col-xl-2 q-pa-sm"
        v-for="(npb, index) in storeNegocio.negocio.NegocioProdutoBarraS.filter(
          (item) => {
            return item.inativo == null;
          }
        )"
        :key="npb.codprodutobarra"
      >
        <q-card>
          <q-img
            ratio="1"
            :src="urlImagem(npb.codimagem)"
            style="width: 100%"
          />
          <q-separator />

          <q-card-section>
            <div
              class="absolute"
              style="top: 0; right: 5px; transform: translateY(-42px)"
            >
              <q-btn color="primary" round icon="edit" />
              <q-btn
                round
                color="negative"
                icon="delete"
                class="q-ma-sm"
                @click="storeNegocio.inativar(index)"
              />
            </div>

            <Transition
              mode="out-in"
              :duration="{ enter: 300, leave: 300 }"
              leave-active-class="animated bounceOut"
              enter-active-class="animated bounceIn"
            >
              <!-- leave-active-class="animated flash" -->
              <div class="text-h5" :key="npb.valortotal">
                <small class="text-grey-7">R$</small>
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(npb.valortotal)
                }}
              </div>
            </Transition>

            <div class="text-overline text-grey-7">
              <q-btn
                size="xs"
                label="-"
                round
                dense
                flat
                @click="storeNegocio.adicionarQuantidade(index, -1)"
              />
              {{ new Intl.NumberFormat("pt-BR").format(npb.quantidade) }}
              <q-btn
                size="xs"
                label="+"
                round
                dense
                flat
                @click="storeNegocio.adicionarQuantidade(index, 1)"
              />
              de
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "currency",
                  currency: "BRL",
                }).format(npb.preco)
              }}
            </div>
            <div class="text-caption text-grey-7">
              {{ npb.barras }} |
              {{ npb.produto }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import {
  defineComponent,
  ref,
  onMounted,
  onUnmounted,
  watch,
  getCurrentInstance,
} from "vue";
import { mainStore } from "stores/main";
import { produtoStore } from "stores/produto";
// import { db } from "boot/db";
// import { api } from "boot/axios";
// import { format } from "quasar";
import { useQuasar } from "quasar";

// export function useKeyDownEvent(handler) {
//   onMounted(() => document.addEventListener("keydown", handler));
//   onUnmounted(() => document.removeEventListener("keydown", handler));
// }

export default defineComponent({
  name: "IndexPage",
  setup() {
    onMounted(() => {
      // storeProduto.resultadoPesquisa=[];

      const inputBarras = getCurrentInstance().ctx.$refs.refBarras.$el;
      inputBarras.focus();

      document.addEventListener("keydown", (event) => {
        switch (event.key) {
          case "F1":
            event.preventDefault();
            dialog.value.pesquisa = true;
            console.log("capturei o F1 pro Alan");
            break;

          // case "F11":
          //   event.preventDefault();
          //   console.log("capturei o F11");
          //   break;

          // case "F12":
          //   event.preventDefault();
          //   console.log("capturei o F12");
          //   break;

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
            if (document.activeElement.tagName.toLowerCase() != "input") {
              inputBarras.focus();
            }
            break;

          default:
            break;
        }
      });
    });
    // useKeyDownEvent((event) => {
    //   switch (event.key) {
    //     case "F11":
    //       event.preventDefault();
    //       console.log("capturei o F11");
    //       break;

    //     case "F12":
    //       event.preventDefault();
    //       console.log("capturei o F12");
    //       break;

    //     default:
    //       break;
    //   }
    // });
    const quantidade = ref("1");
    const barras = ref("");
    const dialog = ref({
      pesquisa: false,
    });
    const storeNegocio = mainStore();
    const storeProduto = produtoStore();
    const $q = useQuasar();

    //verifica se tem uma quantidade digitada nas barras
    watch(barras, (newValue, oldValue) => {
      if (!barras.value instanceof String) {
        return;
      }
      if (barras.value.length < 2) {
        return;
      }
      const ultimo = barras.value.charAt(barras.value.length - 1);
      if (ultimo != "*") {
        return;
      }
      const resto = barras.value
        .substring(0, barras.value.length - 1)
        .replace(",", ".");
      const quant = parseFloat(resto);
      if (isNaN(quant)) {
        return;
      }
      quantidade.value = quant;
      barras.value = "";
    });

    const urlImagem = (codimagem) => {
      if (codimagem == null) {
        return "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg";
      }
      return (
        "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/" +
        codimagem +
        ".jpg"
      );
    };

    // adiciona produtos pelo codigo de barras ou codigo interno
    const buscarBarras = async () => {
      // $q.notify("Message");
      const txt = barras.value;
      if (txt.length == 0) {
        return;
      }
      barras.value = "";

      let ret = await storeProduto.buscarBarras(txt);

      if (ret.length == 1) {
        const qtd = parseFloat(quantidade.value);
        quantidade.value = 1;
        storeNegocio.adicionarItem(
          ret[0].codprodutobarra,
          ret[0].barras,
          ret[0].codproduto,
          ret[0].produto,
          ret[0].codimagem,
          qtd,
          ret[0].preco
        );
        $q.notify({
          type: "positive",
          message: "Código " + txt + " adicionado.",
          timeout: 1500, // 1,5 segundos
        });
        var audio = new Audio("done.m4a");
        audio.play();
      } else {
        $q.notify({
          type: "negative",
          message: "Falha ao buscar código " + txt + "!",
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
        var audio = new Audio("error.m4a");
        audio.play();
      }
    };

    return {
      storeNegocio,
      storeProduto,
      barras,
      quantidade,
      urlImagem,
      buscarBarras,
      dialog,
    };
  },
});
</script>
