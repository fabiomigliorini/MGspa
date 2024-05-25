<script setup>
import { ref, onMounted, watch } from "vue";
import { debounce } from "quasar";
import { db } from "src/boot/db";
import { produtoStore } from "src/stores/produto";
import emitter from "src/utils/emitter";

const sProduto = produtoStore();
const prancheta = ref({});
const categorias = ref([]);
const produtos = ref([]);
const historico = ref([]);
const texto = ref("");

onMounted(() => {
  carregarPrancheta();
});

const carregarPrancheta = async () => {
  prancheta.value = await db.prancheta.orderBy("ordem").toArray();
  inicio();
};

const inicio = () => {
  produtos.value = [];
  historico.value = [];
  if (texto.value != "") {
    pesquisar();
  } else {
    categorias.value = prancheta.value;
  }
};

const selecionarCategoria = (cat) => {
  historico.value.push(cat);
  categorias.value = cat.categorias;
  produtos.value = cat.produtos;
};

const adicionarProduto = (prod) => {
  emitter.emit("adicionarProduto", prod);
};

const voltar = (i) => {
  categorias.value = historico.value[i].categorias;
  produtos.value = historico.value[i].produtos;
  historico.value = historico.value.slice(0, i + 1);
};

watch(
  () => texto.value,
  () => {
    pesquisar();
  }
);

const pesquisarCategoria = (cat, textoMinusculo) => {
  cat.categorias.forEach((subCat) => {
    if (
      subCat.categoria
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase("en-US")
        .includes(textoMinusculo)
    ) {
      categorias.value.push(subCat);
      pesquisarCategoria(subCat, textoMinusculo);
    }
  });
  cat.produtos.forEach((prd) => {
    if (
      prd.descricao
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase("en-US")
        .includes(textoMinusculo)
    ) {
      produtos.value.push(prd);
    } else if (
      prd.produto
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase("en-US")
        .includes(textoMinusculo)
    ) {
      produtos.value.push(prd);
    }
  });
};

const pesquisar = debounce(async () => {
  if (texto.value == "") {
    inicio();
    return;
  }
  const textoMinusculo = texto.value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLocaleLowerCase("en-US");
  categorias.value = [];
  produtos.value = [];
  prancheta.value.forEach((cat) => {
    if (
      cat.categoria
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase("en-US")
        .includes(textoMinusculo)
    ) {
      categorias.value.push(cat);
    }
    pesquisarCategoria(cat, textoMinusculo);
  });
}, 500);
</script>
<template>
  <div class="q-pa-md">
    <q-breadcrumbs v-if="historico.length > 0">
      <q-breadcrumbs-el icon="home" @click="inicio()" class="cursor-pointer" />
      <q-breadcrumbs-el
        v-for="(cat, i) in historico"
        :key="i"
        :label="cat.categoria"
        @click="voltar(i)"
        class="cursor-pointer"
      />
    </q-breadcrumbs>
    <q-input outlined v-model="texto" label="Pesquisa" autofocus v-else>
      <template v-slot:append>
        <q-icon
          v-if="texto !== ''"
          name="close"
          @click="texto = ''"
          class="cursor-pointer"
        />
        <q-icon name="search" />
      </template>
    </q-input>
  </div>
  <q-list>
    <template v-for="cat in categorias" :key="cat.codpranchetacategoria">
      <q-item clickable v-ripple @click="selecionarCategoria(cat)">
        <q-item-section avatar>
          <q-avatar rounded size="90px">
            <img :src="cat.imagem" v-if="cat.imagem" />
            <q-icon name="mdi-bookshelf" color="grey" />
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium text-grey-9">
            {{ cat.categoria }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
    <template v-for="prod in produtos" :key="prod.codprancheta">
      <q-item clickable v-ripple @click="adicionarProduto(prod)">
        <q-item-section avatar>
          <q-avatar rounded size="90px">
            <img :src="sProduto.urlImagem(prod.codimagem)" />
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium text-grey-9">
            {{ prod.descricao }}
          </q-item-label>
          <q-item-label caption class="ellipsis-3-lines">
            {{ prod.barras }}
            {{ prod.produto }}
          </q-item-label>
        </q-item-section>
        <q-item-section side class="text-bold">
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(prod.preco)
          }}
        </q-item-section>
      </q-item>
      <q-separator />
    </template>
  </q-list>
</template>
