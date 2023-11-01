<template>
  <q-page class="flex flex-center">
    <!-- <img
      alt="Quasar logo"
      src="~assets/quasar-logo-vertical.svg"
      style="width: 200px; height: 200px"
    > -->
    <!-- {{store.listagemPdv.length}} -->
    <q-btn
      color="primary"
      icon="check"
      label="Buscar Produtos"
      @click="getProdutos()"
    />
    <div class="q-pa-md">
      <q-table
        title="Produtos"
        :rows="store.listagemPdv"
        row-key="codprodutobarra"
      />
    </div>
  </q-page>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { api } from "boot/axios";
import { produtosStore } from "stores/produtos";
import { db } from "boot/db";

export default defineComponent({
  name: "IndexPage",
  setup() {
    onMounted(() => {
      // getProdutos();
      getProduto();
    });
    const store = produtosStore();
    const getProduto = async () => {
      var prod = await db.produtos.where({ barras: "7891027941321" }).first();
      console.log(prod);
      var prod = await db.produtos
        .where("produto")
        .startsWithIgnoreCase("caderno")
        .first();
      console.log(prod);
      var prod = await db.produtos
        .filter((p) => /Foroni/.test(p.produto))
        .toArray();
      console.log(prod);
      const regex = new RegExp("cad.*48.*basico", "i");
      var prod = await db.produtos
        .toArray()
        .then((result) => result.filter((p) => regex.test(p.produto)));
      store.listagemPdv = prod;
      console.log(prod);
    };
    const getProdutos = async () => {
      // controle das iteracoes
      const limite = 10000;
      // const limite = 100;
      var iteracoes = 0;
      var codprodutobarra = 0;

      // inicializa store
      store.listagemPdv = [];

      //teste adicionar
      db.produtos.add({
        codprodutobarra: 1,
        codproduto: 1,
        barras: 789,
        produto: "produto",
        variacao: "variacao",
        sigla: "UN",
        quantidade: 1,
        codimagem: null,
        preco: 1.99,
        inativo: null,
        sincronizado: null,
      });

      // busca dados na api
      do {
        var { data } = await api.get("/api/v1/produto/listagem-pdv", {
          params: {
            codprodutobarra,
            limite,
          },
        });

        // insere dados no banco local
        await data.forEach(async function (produto) {
          // console.log(produto);
          db.produtos.add(produto).catch(function (err) {
            console.log("error dexie");
            console.error(err.stack || err);
          });
        });
        // store.listagemPdv = [].concat(store.listagemPdv, data);
        var codprodutobarra = data.slice(-1)[0].codprodutobarra;
        // console.log(codprodutobarra);
        // console.log(store.listagemPdv.length);
        iteracoes++;
        console.log("iteracoes:" + iteracoes * limite);
      } while (data.length >= limite && iteracoes <= 1000);
      // } while (data.length >= limite && iteracoes <= 5);
    };
    return {
      store,
      getProdutos,
    };
  },
});
</script>
