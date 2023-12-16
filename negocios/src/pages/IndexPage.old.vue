<template>
  <q-page class="">
    <!-- Pesquisa de Produto -->
    <q-dialog
      v-model="dialog.pesquisa"
      maximized
      transition-show="slide-up"
      transition-hide="slide-down"
      @hide="$refs.refBarras.$el.focus()"
    >
      <q-card>
        <q-card-section class="bg-primary text-white">
          <q-input
            outlined
            autofocus
            v-model="sProduto.textoPesquisa"
            label="Pesquisa"
            ref="refPesquisa"
            bg-color="white"
            @keydown.enter.prevent="sProduto.pesquisar()"
          >
            <template v-slot:append>
              <q-btn
                round
                dense
                flat
                icon="search"
                @click="sProduto.pesquisar()"
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
              v-for="produto in sProduto.resultadoPesquisa"
              v-bind:key="produto.codprodutobarra"
            >
              <div class="q-pa-sm col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6">
                <q-card
                  flat
                  bordered
                  v-ripple
                  class="cursor-pointer q-hoverable"
                  @click="
                    adicionarPelaListagem(
                      produto.codprodutobarra,
                      produto.barras,
                      produto.codproduto,
                      produto.produto,
                      produto.codimagem,
                      1,
                      produto.preco
                    )
                  "
                >
                  <span class="q-focus-helper"></span>
                  <q-img ratio="1" :src="urlImagem(produto.codimagem)" />

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

    <!-- Cabecalho adicionar produto -->
    <div class="row q-pa-md">
      <div class="col-lg-10 q-sm-9 q-xs-12">
        <div class="row">
          <div class="col q-pb-sm q-pr-sm">
            <q-input
              type="text"
              outlined
              ref="refBarras"
              v-model="barras"
              label="Barras"
              input-class="text-right"
              @change="buscarBarras()"
              :prefix="labelQuantidade"
            >
              <!-- <template v-slot:prepend>
                <span
                  class="text-caption ellipsis text-right"
                  style="width: 50px"
                >
                  {{  }} x
                </span>
              </template> -->
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
        </div>
      </div>
      <div class="col-xl-5 col-lg-2 col-sm-3 col-xs-12" v-if="sNegocio.negocio">
        <div class="row">
          <q-list bordered padding class="rounded-borders">
            <q-item-label header>Folders</q-item-label>

            <q-item>
              <Transition
                mode="out-in"
                :duration="{ enter: 300, leave: 300 }"
                leave-active-class="animated bounceOut"
                enter-active-class="animated bounceIn"
              >
                <!-- leave-active-class="animated flash" -->
                <div
                  class="text-h5 text-right"
                  :key="sNegocio.negocio.valortotal"
                >
                  <small class="text-grey-7">R$</small>
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(sNegocio.negocio.valortotal)
                  }}
                </div>
              </Transition>
            </q-item>

            <q-item>
              <q-item-section avatar top>
                <q-avatar icon="folder" color="primary" text-color="white" />
              </q-item-section>

              <q-item-section>
                <q-item-label lines="1"> </q-item-label>
                <q-item-label caption>February 22nd, 2019</q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-icon name="info" color="green" />
              </q-item-section>
            </q-item>

            <q-item clickable v-ripple>
              <q-item-section avatar top>
                <q-avatar icon="folder" color="orange" text-color="white" />
              </q-item-section>

              <q-item-section>
                <q-item-label lines="1">Movies</q-item-label>
                <q-item-label caption>March 1st, 2019</q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-icon name="info" />
              </q-item-section>
            </q-item>

            <q-item clickable v-ripple>
              <q-item-section avatar top>
                <q-avatar icon="folder" color="teal" text-color="white" />
              </q-item-section>

              <q-item-section>
                <q-item-label lines="1">Photos</q-item-label>
                <q-item-label caption>January 15th, 2019</q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-icon name="info" />
              </q-item-section>
            </q-item>

            <q-separator spaced />
          </q-list>
        </div>
        <div class="row">
          / offline:
          {{ id }}
          / Codnegocio:
          {{ codnegocio }}
          / valortotal:
        </div>
      </div>

      <div class="col-lg-2 col-sm-3 col-xs-12" v-if="false">
        {{
          sNegocio.negocio.itens.filter((item) => {
            return item.inativo == null;
          }).length
        }}
        teste /
        {{ sNegocio.negocio.itens.length }}
        / offline:
        {{ id }}
        Codnegocio:
        {{ codnegocio }}
      </div>
    </div>

    <!-- Editar Item -->
    <q-dialog v-model="dialog.item">
      <q-card>
        <q-form ref="formItem" @submit="salvarItem(index)">
          <q-card-section class="items-center">
            <div class="row">
              <div class="col-4 q-pr-sm">
                <q-input
                  autofocus
                  type="number"
                  step="0.001"
                  min="0.001"
                  lazy-rules
                  outlined
                  v-model.number="edicao.quantidade"
                  label="Quantidade"
                  input-class="text-right"
                  :rules="[(val) => val > 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorProdutos()"
                />
              </div>
              <div class="col-4 q-pr-sm">
                <q-input
                  type="number"
                  step="0.01"
                  min="0.01"
                  outlined
                  v-model.number="edicao.preco"
                  prefix="R$"
                  label="Preço"
                  input-class="text-right"
                  :rules="[(val) => val > 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorProdutos()"
                />
              </div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  min="0.01"
                  outlined
                  disable
                  v-model.number="edicao.valorprodutos"
                  prefix="R$"
                  label="Total"
                  input-class="text-right"
                  :rules="[(val) => val > 0 || 'Deve ser maior que zero!']"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-4 q-pr-sm">
                <q-input
                  type="number"
                  step="0.01"
                  min="0"
                  max="99.99"
                  outlined
                  v-model.number="edicao.percentualdesconto"
                  label="% Desc"
                  input-class="text-right"
                  suffix="%"
                  :rules="[(val) => val >= 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorDesconto()"
                />
              </div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  :max="edicao.valorprodutos - 0.01"
                  outlined
                  v-model.number="edicao.valordesconto"
                  prefix="R$"
                  label="Desconto"
                  input-class="text-right"
                  :rules="[
                    (val) =>
                      val >= 0 || val == null || 'Deve ser maior que zero!',
                  ]"
                  @change="recalcularEdicaoPercentualDesconto()"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-4"></div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  outlined
                  v-model.number="edicao.valorfrete"
                  prefix="R$"
                  label="Frete"
                  input-class="text-right"
                  :rules="[(val) => val >= 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorTotal()"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-4"></div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  outlined
                  v-model.number="edicao.valorseguro"
                  prefix="R$"
                  label="Seguro"
                  input-class="text-right"
                  :rules="[(val) => val >= 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorTotal()"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-4"></div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  outlined
                  v-model.number="edicao.valoroutras"
                  prefix="R$"
                  label="Outras"
                  input-class="text-right"
                  :rules="[(val) => val >= 0 || 'Deve ser maior que zero!']"
                  @change="recalcularEdicaoValorTotal()"
                />
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-4"></div>
              <div class="col-4">
                <q-input
                  type="number"
                  step="0.01"
                  outlined
                  disable
                  v-model.number="edicao.valortotal"
                  prefix="R$"
                  label="Total"
                  input-class="text-right"
                  :rules="[(val) => val >= 0 || 'Deve ser maior que zero!']"
                />
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn
              flat
              label="Cancelar"
              color="primary"
              @click="dialog.item = false"
            />
            <q-btn type="submit" flat label="Salvar" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- listagem de produto -->
    <div class="row q-px-md" v-if="id">
      <q-pagination
        v-model="paginacao.pagina"
        :max="paginas"
        :max-pages="6"
        boundary-numbers
        gutter="md"
      />
    </div>
    <div class="row q-pa-sm" v-if="id">
      <div
        class="col-xs-6 col-sm-4 col-md-3 col-lg-2 col-xl-2 q-pa-sm"
        v-for="npb in produtosAtivosDaPagina"
        :key="npb.codprodutobarra"
      >
        <q-card>
          <q-img ratio="1" :src="urlImagem(npb.codimagem)" />
          <q-separator />

          <q-card-section>
            <div
              class="absolute"
              style="top: 0; right: 5px; transform: translateY(-42px)"
            >
              <q-btn
                color="primary"
                round
                icon="edit"
                @click="editarItem(npb)"
              />
              <q-btn
                round
                color="negative"
                icon="delete"
                class="q-ma-sm"
                @click="sNegocio.itemInativar(npb)"
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
                @click="sNegocio.itemAdicionarQuantidade(npb, -1)"
              />
              {{ new Intl.NumberFormat("pt-BR").format(npb.quantidade) }}
              <q-btn
                size="xs"
                label="+"
                round
                dense
                flat
                @click="sNegocio.itemAdicionarQuantidade(npb, 1)"
              />
              de
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "currency",
                  currency: "BRL",
                }).format(npb.preco)
              }}
              <template v-if="npb.valordesconto">
                <br />
                -
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(npb.valordesconto)
                }}
                (Desconto)
              </template>
              <template v-if="npb.valorfrete">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(npb.valorfrete)
                }}
                (Frete)
              </template>
              <template v-if="npb.valorseguro">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(npb.valorseguro)
                }}
                (Seguro)
              </template>
              <template v-if="npb.valoroutras">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(npb.valoroutras)
                }}
                (Outras)
              </template>
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
  // onUnmounted,
  watch,
  getCurrentInstance,
  computed,
} from "vue";
import { negocioStore } from "stores/negocio";
import { produtoStore } from "stores/produto";
// import { db } from "boot/db";
// import { api } from "boot/axios";
// import { format } from "quasar";
import { useQuasar } from "quasar";
import { useRouter, useRoute } from "vue-router";
// import { nextTick } from "process";

// export function useKeyDownEvent(handler) {
//   onMounted(() => document.addEventListener("keydown", handler));
//   onUnmounted(() => document.removeEventListener("keydown", handler));
// }

export default defineComponent({
  name: "IndexPage",
  setup() {
    const quantidade = ref("1");
    const barras = ref("");
    const dialog = ref({
      pesquisa: false,
      item: false,
    });
    const sNegocio = negocioStore();
    const sProduto = produtoStore();
    const $q = useQuasar();
    const edicao = ref({
      npb: null,
      quantidade: null,
      preco: null,
      valorprodutos: null,
      percentualdesconto: null,
      valordesconto: null,
      valorfrete: null,
      valorseguro: null,
      valoroutras: null,
      valortotal: null,
    });
    const paginacao = ref({
      porPagina: 12,
      pagina: 1,
    });
    const id = ref(null);
    const codnegocio = ref(null);
    const route = useRoute();
    const router = useRouter();

    onMounted(() => {
      id.value = route.params.id;
      if (id.value) {
        sNegocio.carregar(id.value);
      }
      console.log(id);
      return;
      // codnegocio.value = route.params.codnegocio;
      // if (id.value == null) {
      //   if (sNegocio.negocios.length > 0) {
      //     negocio.value = sNegocio.negocios[0];
      //   } else {
      //     negocio.value = sNegocio.criar();
      //   }
      //   id.value = negocio.value.id;
      //   router.replace("/id/" + id.value);
      // } else {
      //   negocio.value = sNegocio.negocios.find(function (n) {
      //     return n.id == id.value;
      //   });
      //   if (!negocio.value) {
      //     router.push("/ErrorNotFound");
      //   }
      // }

      inputBarras.focus();

      window.addEventListener("keydown", (event) => {
        switch (event.key) {
          case "F1":
            event.preventDefault();
            dialog.value.pesquisa = true;
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

    // const labelQuantidade = computed({
    //   /*
    //   // getter
    //   get() {
    //     // let lbl = new Intl.NumberFormat("pt-BR").format(quantidade);
    //     let lbl = "teste";
    //     return lbl;
    //   },
    //   // setter
    //   set(newValue) {},
    //   */
    //   // getter
    //   get() {
    //     return Math.ceil(
    //       quantidadeProdutosAtivos.value / paginacao.value.porPagina
    //     );
    //   },
    //   // setter
    //   set(newValue) {},
    // });

    const labelQuantidade = computed({
      // getter
      get() {
        let lbl = new Intl.NumberFormat("pt-BR").format(quantidade.value);
        lbl += " x";
        return lbl;
        return "este";
      },
      // setter
      set(newValue) {},
    });

    const paginas = computed({
      // getter
      get() {
        return Math.ceil(
          quantidadeProdutosAtivos.value / paginacao.value.porPagina
        );
      },
      // setter
      set(newValue) {},
    });

    const quantidadeProdutosAtivos = computed({
      get() {
        return produtosAtivos.value.length;
      },
      set(newValue) {},
    });

    const produtosAtivos = computed({
      get() {
        if (!sNegocio.negocio) {
          return [];
        }
        return sNegocio.negocio.itens.filter((item) => {
          return item.inativo == null;
        });
      },
      set(newValue) {},
    });

    const produtosAtivosDaPagina = computed({
      get() {
        const final = paginacao.value.pagina * paginacao.value.porPagina;
        const ret = produtosAtivos.value.slice(
          final - paginacao.value.porPagina,
          final
        );
        return ret;
      },
      set(newValue) {},
    });

    const urlImagem = (codimagem) => {
      // return "https://fakeimg.pl/300x300";
      if (codimagem == null) {
        return "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg";
      }
      return (
        "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/" +
        codimagem +
        ".jpg"
      );
    };

    // adiciona produto pelo codigo de barras ou codigo interno
    const buscarBarras = async () => {
      // $q.notify("Message");
      const txt = barras.value;
      if (txt.length == 0) {
        return;
      }
      barras.value = "";

      let ret = await sProduto.buscarBarras(txt);

      if (ret.length == 1) {
        const qtd = parseFloat(quantidade.value);
        quantidade.value = 1;
        sNegocio.itemAdicionar(
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
        paginacao.value.pagina = 1;
        var audio = new Audio("successo.mp3");
        audio.play();
      } else {
        $q.notify({
          type: "negative",
          message: "Falha ao buscar código " + txt + "!",
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
        var audio = new Audio("erro.mp3");
        audio.play();
      }
    };

    const editarItem = (npb) => {
      edicao.value.npb = npb;
      edicao.value.quantidade = npb.quantidade;
      edicao.value.preco = npb.preco;
      edicao.value.valorprodutos = npb.valorprodutos;
      edicao.value.percentualdesconto = npb.percentualdesconto;
      edicao.value.valordesconto = npb.valordesconto;
      edicao.value.valorfrete = npb.valorfrete;
      edicao.value.valorseguro = npb.valorseguro;
      edicao.value.valoroutras = npb.valoroutras;
      edicao.value.valortotal = npb.valortotal;
      dialog.value.item = true;
    };

    const salvarItem = async () => {
      const npb = edicao.value.npb;
      npb.quantidade = edicao.value.quantidade;
      npb.preco = edicao.value.preco;
      npb.valorprodutos = edicao.value.valorprodutos;
      npb.percentualdesconto = edicao.value.percentualdesconto;
      npb.valordesconto = edicao.value.valordesconto;
      npb.valorfrete = edicao.value.valorfrete;
      npb.valorseguro = edicao.value.valorseguro;
      npb.valoroutras = edicao.value.valoroutras;
      npb.valortotal = edicao.value.valortotal;
      sNegocio.itemRecalcularValorTotal(sNegocio.negocio, npb);
      dialog.value.item = false;
    };

    const adicionarPelaListagem = (
      codprodutobarra,
      barras,
      codproduto,
      produto,
      codimagem,
      quantidade,
      preco
    ) => {
      sNegocio.itemAdicionar(
        codprodutobarra,
        barras,
        codproduto,
        produto,
        codimagem,
        quantidade,
        preco
      );
      dialog.value.pesquisa = false;
      var audio = new Audio("successo.mp3");
      audio.play();
    };

    const recalcularEdicaoValorProdutos = () => {
      edicao.value.valorprodutos =
        Math.round(edicao.value.quantidade * edicao.value.preco * 100) / 100;
      recalcularEdicaoValorDesconto();
    };

    const recalcularEdicaoValorDesconto = () => {
      if (edicao.value.percentualdesconto <= 0) {
        edicao.value.valordesconto = null;
      } else {
        edicao.value.valordesconto =
          Math.round(
            edicao.value.valorprodutos * edicao.value.percentualdesconto
          ) / 100;
      }
      recalcularEdicaoValorTotal();
    };

    const recalcularEdicaoPercentualDesconto = () => {
      if (edicao.value.valordesconto <= 0) {
        edicao.value.percentualdesconto = null;
      } else {
        edicao.value.percentualdesconto =
          Math.round(
            (edicao.value.valordesconto * 10000) / edicao.value.valorprodutos
          ) / 100;
      }
      recalcularEdicaoValorTotal();
    };

    const recalcularEdicaoValorTotal = () => {
      let total = parseFloat(edicao.value.valorprodutos);
      if (edicao.value.valordesconto) {
        total -= parseFloat(edicao.value.valordesconto);
      }
      if (edicao.value.valorfrete) {
        total += parseFloat(edicao.value.valorfrete);
      }
      if (edicao.value.valorseguro) {
        total += parseFloat(edicao.value.valorseguro);
      }
      if (edicao.value.valoroutras) {
        total += parseFloat(edicao.value.valoroutras);
      }
      edicao.value.valortotal = Math.round(total * 100) / 100;
    };

    return {
      sNegocio,
      sProduto,
      barras,
      edicao,
      quantidade,
      urlImagem,
      buscarBarras,
      editarItem,
      salvarItem,
      adicionarPelaListagem,
      dialog,
      paginacao,
      paginas,
      produtosAtivos,
      produtosAtivosDaPagina,
      quantidadeProdutosAtivos,
      id,
      codnegocio,
      recalcularEdicaoValorProdutos,
      recalcularEdicaoValorDesconto,
      recalcularEdicaoPercentualDesconto,
      recalcularEdicaoValorTotal,
      labelQuantidade,
    };
  },
});
</script>
