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

          <!-- <q-btn flat round icon="play_arrow" /> -->
          <!-- <q-btn flat round icon="pause" /> -->
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
        <!-- <q-bar class="bg-primary text-white">
          <q-space />
          <q-btn dense flat icon="close" v-close-popup>
            <q-tooltip class="bg-white text-primary">Close</q-tooltip>
          </q-btn>
        </q-bar> -->

        <q-card-section class="bg-primary text-white">
          <!-- <div class="text-h6">Alert</div> -->
          <q-input
            outlined
            v-model="storeProduto.textoPesquisa"
            label="Pesquisa"
            ref="refPesquisa"
            bg-color="white"
            @change="storeProduto.pesquisar()"
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
                  <q-card-section
                    class="q-pa-xs text-center bg-grey-2 text-primary"
                  >
                    <!-- <div class="text-h6">Our Changing Planet</div> -->
                    <div class="text-subtitle1">
                      <q-icon name="bar_chart" />{{ produto.barras }}
                    </div>
                  </q-card-section>

                  <img
                    :src="
                      'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' +
                      produto.codimagem +
                      '.jpg'
                    "
                    v-if="produto.codimagem"
                  />
                  <img
                    src="https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg"
                    v-else
                  />

                  <q-card-section
                    class="q-pa-xs text-center bg-grey-2 text-primary"
                  >
                    <q-chip
                      size="md"
                      class="absolute text-bold"
                      square
                      color="primary"
                      text-color="white"
                      style="top: 0; right: 5px; transform: translateY(-42px)"
                    >
                      {{ produto.sigla }}
                      <template v-if="produto.quantidade > 1">
                        C/{{
                          new Intl.NumberFormat("pt-BR").format(
                            produto.quantidade
                          )
                        }}
                      </template>
                      {{
                        new Intl.NumberFormat("pt-BR", {
                          style: "currency",
                          currency: "BRL",
                        }).format(produto.preco)
                      }}
                    </q-chip>
                    <div class="text-caption">
                      #{{ String(produto.codproduto).padStart(6, "0") }} -
                      {{ produto.produto }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </template>
            aqui no fim mostrar mensagem se pesquisa muito comprida
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

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
          <div class="col-sm-4 col-xs-12 q-pb-sm q-pr-sm">
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
              </template>
            </q-input>
          </div>

          <div class="col-sm-5 col-xs-12 q-pb-sm q-pr-sm">
            <div class="row">
              <div class="col q-pr-sm">
                <q-btn
                  :loading="storeProduto.importacao.rodando"
                  :percentage="storeProduto.importacao.progresso * 100"
                  stack
                  color="primary"
                  @click="storeProduto.sincronizar()"
                  icon="refresh"
                  label="Sincronizar"
                  style="width: 100%"
                  class="q-ml-sm"
                >
                  <template v-slot:loading>
                    <q-spinner-dots />
                  </template>
                </q-btn>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-2 q-sm-3 q-xs-12">DADOS NEGOCIO</div>
    </div>

    <div class="row q-pa-sm" v-if="storeNegocio.negocio">
      <div
        class="col-sm-2 q-pa-sm"
        v-for="npb in storeNegocio.negocio.NegocioProdutoBarraS"
        v-bind:key="npb.codprodutobarra"
      >
        <Transition enter-active-class="animated fadeIn">
          <q-card :key="npb.valortotal">
            <q-img
              :src="
                'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' +
                npb.codimagem +
                '.jpg'
              "
              v-if="npb.codimagem"
              style="width: 100%"
            >
              <div class="absolute-top text-right">
                <q-btn flat icon="edit" />
                <q-btn flat icon="delete" />
              </div>
              <!-- leave-active-class="animated slide" -->
              <div class="absolute-bottom text-right">
                <div class="text-h6">
                  <span>
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "currency",
                        currency: "BRL",
                      }).format(npb.valortotal)
                    }}
                  </span>
                </div>
                <div class="text-subtitle2">
                  {{ new Intl.NumberFormat("pt-BR").format(npb.quantidade) }} x
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "currency",
                      currency: "BRL",
                    }).format(npb.preco)
                  }}
                </div>
              </div>
            </q-img>
            <q-img
              src="https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg"
              style="width: 100%"
              v-else
            >
              <div class="absolute-bottom">
                <div class="text-h6">{{ npb.quantidade }}</div>
                <div class="text-subtitle2">by John Doe</div>
              </div>
            </q-img>
            <q-card-section class="q-pa-xs text-center bg-grey-2 text-primary">
              <!-- <div class="text-h6">Our Changing Planet</div> -->
              <div class="text-subtitle1">
                <q-icon name="bar_chart" />{{ npb.barras }}
              </div>
              <div class="text-caption">
                {{ npb.produto }}
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none"> </q-card-section>

            <q-card-actions> </q-card-actions>
          </q-card>
        </Transition>
      </div>
    </div>
    <!--
    <div class="row" v-if="storeNegocio.negocio">
      <div class="col">
        <div class="q-pa-md">
          <q-list bordered class="rounded-borders">
            <q-item-label header>Itens Adicionados</q-item-label>

            <template
              v-for="npb in storeNegocio.negocio.NegocioProdutoBarraS"
              v-bind:key="npb.codprodutobarra"
            >
              <q-item>
                <q-item-section class="col-1">
                  <img
                    :src="
                      'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' +
                      npb.codimagem +
                      '.jpg'
                    "
                    v-if="npb.codimagem"
                    style="width: 100%"
                  />
                  <img
                    src="https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg"
                    style="width: 100%"
                    v-else
                  />
                </q-item-section>

                <q-item-section top class="col-2 gt-sm">
                  <q-item-label class="q-mt-sm">{{ npb.barras }}</q-item-label>
                  <q-item-label caption class="q-mt-sm">
                    #{{ String(npb.codproduto).padStart(6, "0") }}
                  </q-item-label>
                </q-item-section>

                <q-item-section top>
                  <q-item-label lines="1">
                    <span class="text-weight-medium">
                      {{ npb.produto }}
                    </span>
                  </q-item-label>
                  <q-item-label caption lines="1">
                    @rstoenescu in #3: > Generic type parameter for props
                  </q-item-label>
                  <q-item-label
                    lines="1"
                    class="q-mt-xs text-body2 text-weight-bold text-primary text-uppercase"
                  >
                    <span class="cursor-pointer">Open in GitHub</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section top side>
                  <div class="text-grey-8 q-gutter-xs">
                    <q-btn
                      class="gt-xs"
                      size="12px"
                      flat
                      dense
                      round
                      icon="delete"
                    />
                    <q-btn
                      class="gt-xs"
                      size="12px"
                      flat
                      dense
                      round
                      icon="done"
                    />
                    <q-btn size="12px" flat dense round icon="more_vert" />
                  </div>
                </q-item-section>
              </q-item>

              <q-separator inset />
            </template>

            <q-item>
              <q-item-section avatar top>
                <q-icon name="account_tree" color="black" size="34px" />
              </q-item-section>

              <q-item-section top class="col-2 gt-sm">
                <q-item-label class="q-mt-sm">GitHub</q-item-label>
              </q-item-section>

              <q-item-section top>
                <q-item-label lines="1">
                  <span class="text-weight-medium"
                    >[quasarframework/quasar]</span
                  >
                  <span class="text-grey-8"> - GitHub repository</span>
                </q-item-label>
                <q-item-label caption lines="1">
                  @rstoenescu in #1: > The build system
                </q-item-label>
                <q-item-label
                  lines="1"
                  class="q-mt-xs text-body2 text-weight-bold text-primary text-uppercase"
                >
                  <span class="cursor-pointer">Open in GitHub</span>
                </q-item-label>
              </q-item-section>

              <q-item-section top side>
                <div class="text-grey-8 q-gutter-xs">
                  <q-btn
                    class="gt-xs"
                    size="12px"
                    flat
                    dense
                    round
                    icon="delete"
                  />
                  <q-btn
                    class="gt-xs"
                    size="12px"
                    flat
                    dense
                    round
                    icon="done"
                  />
                  <q-btn size="12px" flat dense round icon="more_vert" />
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </div>
      </div>
    </div>

    <div class="row" v-if="storeNegocio.negocio">
      <div class="col">
        <div class="q-pa-md">
          <q-table
            title="Adicionados"
            :rows="storeNegocio.negocio.NegocioProdutoBarraS"
            row-key="codprodutobarra"
          />
        </div>
      </div>
    </div>
    -->
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
    const indeterminado = ref(false);
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
      indeterminado,
      buscarBarras,
      dialog,
    };
  },
});
</script>
