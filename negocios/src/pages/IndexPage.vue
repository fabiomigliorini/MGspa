<template>
  <q-page class="">

    <q-dialog
      v-model="dialogPesquisa"
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
              <q-btn round dense flat icon="search" @click="buscarTexto()"/>
              <q-btn round dense flat icon="close" @click="dialogPesquisa = false"/>
            </template>
          </q-input>

        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row">
            <template v-for="produto in store.pesquisa" v-bind:key="produto.codprodutobarra">
              <div class="q-pa-sm col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6 ">
                <q-card
                  flat
                  bordered
                  v-ripple
                  class="cursor-pointer q-hoverable"
                  @click="store.adicionarItem(
                    produto.codprodutobarra,
                    produto.barras,
                    produto.codproduto,
                    produto.produto,
                    produto.codimagem,
                    1,
                    produto.preco);
                    dialogPesquisa = false;
                ">
                  <span class="q-focus-helper"></span>
                  <q-card-section class="q-pa-xs text-center bg-grey-2 text-primary ">
                    <!-- <div class="text-h6">Our Changing Planet</div> -->
                    <div class="text-subtitle1">
                      <q-icon name="bar_chart" />
                      {{produto.barras}}
                    </div>
                  </q-card-section>

                  <img :src="'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' + produto.codimagem + '.jpg'" v-if="produto.codimagem">
                  <img src="https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg" v-else>

                  <q-card-section class="q-pa-xs text-center bg-grey-2 text-primary ">
                    <q-chip
                      size="md"
                      class="absolute text-bold"
                      square
                      color="primary"
                      text-color="white"
                      style="top: 0; right: 5px; transform: translateY(-42px);"
                      >
                      {{produto.sigla}}
                      <template v-if="produto.quantidade > 1">
                        C/{{ new Intl.NumberFormat('pt-BR').format(produto.quantidade) }}
                      </template>
                      {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(produto.preco) }}
                    </q-chip>
                    <div class="text-caption">
                      #{{String(produto.codproduto).padStart(6, '0')}} -
                      {{produto.produto}}
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
          @click="getProdutos()"
        />
        <q-linear-progress
          size="15px"
          :value="progressoApi"
          :indeterminate="indeterminado"
          class="q-mt-md"
        />
      </div>
      <div class="col-md-3 col-sm-12 q-pa-md">
        <q-input
          type="number"
          step="1"
          outlined
          v-model="barras"
          label="Barras"
          @change="buscarBarras()"
          >
          <template v-slot:after>
            <q-btn round dense flat icon="search" @click="buscarBarras()"/>
          </template>
        </q-input>
      </div>
      <div class="col-md-3 col-sm-12 q-pa-md">
        <q-btn label="Pesquisar Produtos" icon="search" color="primary" @click="dialogPesquisa = true" />
      </div>
    </div>




    <div class="row" >
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
import { defineComponent, ref, onMounted } from "vue";
import { api } from "boot/axios";
import { mainStore } from "stores/main";
import { db } from "boot/db";
import { format } from 'quasar'

export default defineComponent({
  name: "IndexPage",
  setup() {
    onMounted(() => {
      // store.pesquisa=[];
      // getProdutos();
    });
    const barras = ref("7891360503521");
    const texto = ref("caneta braswu metal");
    const indeterminado = ref(false);
    const dialogPesquisa = ref(true);
    // const progressoDb = ref(0);
    const progressoApi = ref(0.5);
    const store = mainStore();
    const buscarBarras = async () => {
      console.log(await db.produtos.count());
      console.log(barras.value);
      var ret = await db.produtos.where({ barras: barras.value }).toArray();

      ret.forEach(object => {
        delete object['palavras'];
      });
      console.log(ret);
      store.pesquisa = ret;
      if (ret.length > 0) {
        store.adicionarItem(
          ret[0].codprodutobarra,
          ret[0].barras,
          ret[0].codproduto,
          ret[0].produto,
          ret[0].codimagem,
          1,
          ret[0].preco
        )
      }
    };

    const buscarTexto = async () => {

      // verifica se tem texto de busca
      const palavras = texto.value.trim().split(' ');
      if (palavras.length == 0) {
        return;
      }
      if (palavras[0].length == 0) {
        return;
      }

      // mostra barra de pesquisa
      indeterminado.value = true;

      // Busca produtos baseados na primeira palavra de pesquisa
      var colProdutos = await db.produtos.where('palavras').startsWithIgnoreCase(palavras[0]);

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
      var arrProdutos = await colProdutos.sortBy('preco');
      // console.log(arrProdutos);
      // var arrProdutos = await colProdutos;
      indeterminado.value = false;
      store.pesquisa = arrProdutos.slice(0, 50);

    };
    const getProdutos = async () => {

      var startTime = performance.now();

      // controle das iteracoes
      const limite = 10000;
      var maxIteracoes = 1000;
      var iteracoes = 0;
      var codprodutobarra = 0;

      // zera barra de progresso
      progressoApi.value = 0;

      do {

        // busca dados na api
        var { data } = await api.get("/api/v1/produto/listagem-pdv", {
          params: {
            codprodutobarra,
            limite,
          },
        });

        // incrementa progresso
        progressoApi.value += 0.05;

        // insere dados no banco local indexeddb
        try {
          await db.produtos.bulkPut(data);
        } catch (error) {
          console.log(error.stack || error);
        }

        // busca codigo do ultimo registro
        var codprodutobarra = data.slice(-1)[0].codprodutobarra;

        // incrementa numero de iteracoes
        iteracoes++;

        //loga statis
        console.log(iteracoes * limite);
        var endTime = performance.now();
        console.log(`Call to doSomething took ${endTime - startTime} milliseconds`);

      // loop enquanto nao tiver buscado menos registros que o limite
      } while (data.length >= limite && iteracoes <= maxIteracoes);

      // mostra progresso como completo
      progressoApi.value = 1;

    };
    return {
      store,
      barras,
      texto,
      progressoApi,
      indeterminado,
      getProdutos,
      buscarBarras,
      buscarTexto,
      dialogPesquisa
    };
  },
});
</script>
