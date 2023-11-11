<template>
  <q-page class="">
    <!-- Sincronizacao  -->

    <q-dialog v-model="dialog.sincronizacao" seamless position="bottom">
      <q-card style="width: 350px">
        <q-linear-progress :value="importacao.progresso" color="pink" />

        <q-card-section class="row items-center no-wrap">
          <div>
            <div class="text-weight-bold">
              {{
                new Intl.NumberFormat("pt-BR").format(
                  importacao.totalSincronizados
                )
              }}
              /
              {{
                new Intl.NumberFormat("pt-BR").format(importacao.totalRegistros)
              }}
              Produtos
            </div>
            <div class="text-grey">{{ importacao.tempoTotal }} Segundos</div>
          </div>

          <q-space />

          <!-- <q-btn flat round icon="play_arrow" /> -->
          <!-- <q-btn flat round icon="pause" /> -->
          <q-btn
            flat
            round
            icon="close"
            v-close-popup
            @click="dialog.sincronizacao = false"
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
            v-model="texto"
            label="Pesquisa"
            bg-color="white"
            @change="buscarTexto()"
          >
            <template v-slot:append>
              <q-btn round dense flat icon="search" @click="buscarTexto()" />
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
              v-for="produto in store.pesquisa"
              v-bind:key="produto.codprodutobarra"
            >
              <div class="q-pa-sm col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6">
                <q-card
                  flat
                  bordered
                  v-ripple
                  class="cursor-pointer q-hoverable"
                  @click="
                    store.adicionarItem(
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
                      <q-icon name="bar_chart" />
                      {{ produto.barras }}
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
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <div class="row">
      <div class="col-md-3 col-sm-12 q-pa-md">
        <q-btn
          color="primary"
          icon="check"
          label="Sincronizar Produtos"
          @click="sincronizarProdutos()"
        />
        <q-btn
          label="Pesquisar Produtos"
          icon="search"
          color="primary"
          class="q-ml-sm"
          @click="dialog.pesquisa = true"
        />
      </div>
      <div class="col-md-3 col-sm-12 q-pa-md">
        <div class="row">
          <div class="col-sm-2 q-pr-md">
            <q-input
              type="number"
              outlined
              step="0.001"
              label="Quantidade"
              class="text-right"
              :model-value="quantidade"
            >
            </q-input>
          </div>
          <div class="col">
            <q-input
              type="text"
              outlined
              v-model="barras"
              label="Barras"
              @change="buscarBarras()"
            >
              <template v-slot:append>
                <q-btn round dense flat icon="search" @click="buscarBarras()" />
              </template>
            </q-input>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-12 q-pa-md"></div>
    </div>

    <div class="row" v-if="store.negocio">
      <div class="col">
        <div class="q-pa-md">
          <q-btn color="primary" icon="delete" label="Limpar" />
          <q-table
            title="Adicionados"
            :rows="store.negocio.NegocioProdutoBarraS"
            row-key="codprodutobarra"
          />
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from "vue";
import { api } from "boot/axios";
import { mainStore } from "stores/main";
import { db } from "boot/db";
import { format } from "quasar";

export default defineComponent({
  name: "IndexPage",
  setup() {
    onMounted(() => {
      // store.pesquisa=[];
    });
    const quantidade = ref("1");
    const barras = ref("7891360503521");
    const texto = ref("caneta braswu metal");
    const indeterminado = ref(false);
    const dialog = ref({
      pesquisa: false,
      sincronizacao: false,
    });
    const importacao = ref({
      totalRegistros: null,
      totalSincronizados: null,
      progresso: 0,
      requisicoes: null,
      maxRequisicoes: 1000,
      // maxRequisicoes: 2,
      limiteRequisicao: 3000,
      tempoTotal: null,
    });
    const progressoApi = ref(0);
    const store = mainStore();

    //verifica se tem uma quantidade digitada nas barras
    watch(barras, (newValue, oldValue) => {
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
      let ret = await db.produtos.where({ barras: barras.value }).toArray();
      if (ret.length == 0) {
        if (barras.value.length == 6) {
          const codproduto = parseInt(barras.value);
          if (!isNaN(codproduto)) {
            ret = await db.produtos
              .where({ codproduto: codproduto })
              .filter((produto) => produto.quantidade == null)
              .toArray();
          }
        }
      }
      if (ret.length == 0) {
        console.log("nada");
      }
      if (ret.length == 1) {
        store.adicionarItem(
          ret[0].codprodutobarra,
          ret[0].barras,
          ret[0].codproduto,
          ret[0].produto,
          ret[0].codimagem,
          parseFloat(quantidade.value),
          ret[0].preco
        );
        barras.value = "";
      }
    };

    const buscarTexto = async () => {
      // verifica se tem texto de busca
      const palavras = texto.value.trim().split(" ");
      if (palavras.length == 0) {
        return;
      }
      if (palavras[0].length == 0) {
        return;
      }

      // mostra barra de pesquisa
      indeterminado.value = true;

      // Busca produtos baseados na primeira palavra de pesquisa
      var colProdutos = await db.produtos
        .where("palavras")
        .startsWithIgnoreCase(palavras[0]);

      // se estiver buscando por mais de uma palavra
      if (palavras.length > 1) {
        // monta expressoes regulares
        var regexes = [];
        for (let i = 1; i < palavras.length; i++) {
          regexes.push(new RegExp(".*" + palavras[i] + ".*", "i"));
        }

        // percorre todos registros filtrando pelas expressoes regulares
        const iMax = regexes.length;
        colProdutos = await colProdutos.and(function (produto) {
          for (let i = 0; i < iMax; i++) {
            if (!regexes[i].test(produto.produto)) {
              return false;
            }
          }
          return true;
        });
      }

      // transforma colecao de produtos em array
      var arrProdutos = await colProdutos.sortBy("preco");
      // console.log(arrProdutos);
      // var arrProdutos = await colProdutos;
      indeterminado.value = false;
      store.pesquisa = arrProdutos.slice(0, 50);
    };

    const sincronizarProdutos = async () => {
      // inicializa progresso
      importacao.value.progresso = 0;
      importacao.value.totalRegistros = 0;
      importacao.value.totalSincronizados = 0;
      importacao.value.requisicoes = 0;
      importacao.value.tempoTotal = 0;

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/produto/listagem-pdv-count");
        importacao.value.totalRegistros = data.count;
        importacao.value.limiteRequisicao = Math.round(
          importacao.value.totalRegistros / 100
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
      }

      // mostra janela de progresso
      dialog.value.sincronizacao = true;

      let sincronizado = null;
      let inicio = performance.now();
      let codprodutobarra = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/produto/listagem-pdv", {
          params: {
            codprodutobarra: codprodutobarra,
            limite: importacao.value.limiteRequisicao,
          },
        });
        // incrementa numero de requisicoes
        importacao.value.requisicoes++;

        // incrementa progresso
        // progressoApi.value += 0.05;

        // insere dados no banco local indexeddb
        try {
          await db.produtos.bulkPut(data);
        } catch (error) {
          console.log(error.stack || error);
        }

        if (sincronizado == null) {
          sincronizado = data[0].sincronizado;
        }

        // busca codigo do ultimo registro
        codprodutobarra = data.slice(-1)[0].codprodutobarra;

        //monta status de progresso
        importacao.value.totalSincronizados += data.length;
        importacao.value.progresso =
          importacao.value.totalSincronizados / importacao.value.totalRegistros;
        importacao.value.tempoTotal = Math.round(
          (performance.now() - inicio) / 1000
        );

        // loop enquanto nao tiver buscado menos registros que o limite
      } while (
        data.length >= importacao.value.limiteRequisicao &&
        importacao.value.requisicoes <= importacao.value.maxRequisicoes &&
        dialog.value.sincronizacao
      );

      // exclui registros que nao vieram na importacao
      db.produtos.where("sincronizado").below(sincronizado).delete();

      // mostra progresso como completo
      progressoApi.value = 1;
    };
    return {
      store,
      barras,
      quantidade,
      texto,
      progressoApi,
      indeterminado,
      sincronizarProdutos,
      buscarBarras,
      buscarTexto,
      dialog,
      importacao,
      // verificarQuantidade,
    };
  },
});
</script>
