<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { db } from "src/boot/db";
import { produtoStore } from "src/stores/produto";
import { uid, Dialog } from "quasar";

const sProduto = produtoStore();
const prancheta = ref([]);
const arvore = ref([]);
const splitterModel = ref(40);
const selecionado = ref(null);
const codprancheta = ref(null);
const codpranchetacategoria = ref(null);
const categoria = ref(null);
const produto = ref(null);
const aba = ref(null);

onMounted(() => {
  carregarPrancheta();
});

const carregarPrancheta = async () => {
  prancheta.value = await db.prancheta.orderBy("ordem").toArray();
};

const adicionarFilhosArvore = async (nodo, categoria) => {
  nodo.children = [];
  categoria.categorias
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((cat) => {
      let item = {
        key: "CAT" + cat.codpranchetacategoria,
        label: cat.categoria,
        img: cat.imagem,
      };
      adicionarFilhosArvore(item, cat);
      nodo.children.push(item);
    });
  categoria.produtos
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((prod) => {
      nodo.children.push({
        key: "PRD" + prod.codprancheta,
        label: prod.descricao,
        img: sProduto.urlImagem(prod.codimagem),
      });
    });
};

const montarArvore = async () => {
  let arvoreTemp = [];
  if (!Array.isArray(prancheta.value)) {
    return arvoreTemp;
  }
  prancheta.value
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((cat) => {
      let item = {
        key: "CAT" + cat.codpranchetacategoria,
        label: cat.categoria,
        img: cat.imagem,
      };
      adicionarFilhosArvore(item, cat);
      arvoreTemp.push(item);
    });
  arvore.value = arvoreTemp;
};

watch(
  () => prancheta.value,
  () => {
    montarArvore();
  },
  { deep: true }
);

const selecionarAba = () => {
  if (!selecionado.value) {
    aba.value = null;
    return;
  }
  switch (selecionado.value.substring(0, 3)) {
    case "PRD":
      aba.value = "produto";
      codprancheta.value = selecionado.value.replace("PRD", "");
      produto.value = localizaProduto(codprancheta.value, prancheta.value);
      break;
    case "CAT":
      aba.value = "categoria";
      codpranchetacategoria.value = selecionado.value.replace("CAT", "");
      categoria.value = localizaCategoria(
        codpranchetacategoria.value,
        prancheta.value
      );
      break;
    default:
      aba.value = null;
      break;
  }
};

const localizaCategoria = (codpranchetacategoria, categorias = []) => {
  let node;
  categorias.some(
    (object) =>
      (node =
        object.codpranchetacategoria == codpranchetacategoria
          ? object
          : localizaCategoria(codpranchetacategoria, object.categorias))
  );
  return node;
};

const localizaProduto = (codprancheta, categorias) => {
  let prod;
  categorias.some((cat) => {
    cat.produtos.some((p) => {
      if (p.codprancheta == codprancheta) {
        prod = p;
        return true;
      }
    });
    if (prod) {
      return true;
    }
  });
  if (prod) {
    return prod;
  }
  categorias.some((cat) => {
    prod = localizaProduto(codprancheta, cat.categorias);
    if (prod) {
      return true;
    }
  });
  return prod;
};

const adicionarCategoria = () => {
  let ordem = 1;
  if (categoria.value.categorias.length > 0) {
    ordem = Math.max(...categoria.value.categorias.map((cat) => cat.ordem)) + 1;
  }
  categoria.value.categorias.push({
    codpranchetacategoria: uid(),
    codpranchetacategoriapai: categoria.value.codpranchetacategoria,
    ordem: ordem,
    categoria: "Nova Categoria",
    observacoes: null,
    criacao: null,
    codusuariocriacao: null,
    alteracao: null,
    codusuarioalteracao: null,
    imagem: null,
    categorias: [],
    produtos: [],
  });
};

const adicionarCategoriaRaiz = () => {
  let ordem = 1;
  if (prancheta.value.length > 0) {
    ordem = Math.max(...prancheta.value.map((cat) => cat.ordem)) + 1;
  }
  prancheta.value.push({
    codpranchetacategoria: uid(),
    codpranchetacategoriapai: null,
    ordem: ordem,
    categoria: "Nova Categoria",
    observacoes: null,
    criacao: null,
    codusuariocriacao: null,
    alteracao: null,
    codusuarioalteracao: null,
    imagem: null,
    categorias: [],
    produtos: [],
  });
};

const adicionarProduto = () => {
  Dialog.create({
    title: "Adicionar Produto",
    message: "Informe o código de barras do produto!",
    prompt: {
      model: "",
      isValid: (val) => val > 100,
      outlined: true,
      placeholder: "Código de barras...",
      inputmode: "tel",
    },
    cancel: true,
  }).onOk(async (barras) => {
    let ret = await sProduto.buscarBarras(barras);
    if (ret.length <= 0) {
      console.log("naoi achou");
    }

    categoria.value.produtos.push({
      codprancheta: uid(),
      codprodutobarra: ret[0].codprodutobarra,
      codpranchetacategoria: categoria.value.codpranchetacategoria,
      ordem: null,
      descricao: ret[0].produto,
      observacoes: null,
      criacao: null,
      codusuariocriacao: null,
      alteracao: null,
      codusuarioalteracao: null,
      codproduto: ret[0].codproduto,
      barras: ret[0].barras,
      produto: ret[0].produto,
      variacao: ret[0].variacao,
      abc: ret[0].abc,
      sigla: ret[0].sigla,
      quantidade: ret[0].quantidade,
      codimagem: ret[0].codimagem,
      preco: ret[0].preco,
      inativo: ret[0].inativo,
    });

    console.log(ret);
    /*
     */
  });
};

const removerCategoria = () => {
  if (categoria.value.codpranchetacategoriapai == null) {
    prancheta.value = prancheta.value.filter((cat) => {
      return cat.codpranchetacategoria != categoria.value.codpranchetacategoria;
    });
  } else {
    let pai = localizaCategoria(
      categoria.value.codpranchetacategoriapai,
      prancheta.value
    );
    pai.categorias = pai.categorias.filter((cat) => {
      return cat.codpranchetacategoria != categoria.value.codpranchetacategoria;
    });
  }
  codpranchetacategoria.value = null;
  categoria.value = null;
  aba.value = null;
};

const categoriaParaCima = () => {
  if (categoria.value.codpranchetacategoriapai == null) {
    const i = prancheta.value.findIndex((cat) => {
      return cat.codpranchetacategoria == categoria.value.codpranchetacategoria;
    });
    if (i <= 0) {
      return;
    }
    const ordemAntiga = categoria.value.ordem;
    categoria.value.ordem = prancheta.value[i - 1].ordem;
    prancheta.value[i - 1].ordem = ordemAntiga;
  } else {
    let pai = localizaCategoria(
      categoria.value.codpranchetacategoriapai,
      prancheta.value
    );
    const i = pai.categorias.findIndex((cat) => {
      return cat.codpranchetacategoria == categoria.value.codpranchetacategoria;
    });
    if (i <= 0) {
      return;
    }
    const ordemAntiga = categoria.value.ordem;
    categoria.value.ordem = pai.categorias[i - 1].ordem;
    pai.categorias[i - 1].ordem = ordemAntiga;
  }
};

const categoriaParaBaixo = () => {
  if (categoria.value.codpranchetacategoriapai == null) {
    const i = prancheta.value.findIndex((cat) => {
      return cat.codpranchetacategoria == categoria.value.codpranchetacategoria;
    });
    if (i < 0 || i >= prancheta.value.length - 1) {
      return;
    }
    const ordemAntiga = categoria.value.ordem;
    categoria.value.ordem = prancheta.value[i + 1].ordem;
    prancheta.value[i + 1].ordem = ordemAntiga;
  } else {
    let pai = localizaCategoria(
      categoria.value.codpranchetacategoriapai,
      prancheta.value
    );
    const i = pai.categorias.findIndex((cat) => {
      return cat.codpranchetacategoria == categoria.value.codpranchetacategoria;
    });
    if (i < 0 || i >= pai.categorias.length - 1) {
      return;
    }
    const ordemAntiga = categoria.value.ordem;
    categoria.value.ordem = pai.categorias[i + 1].ordem;
    pai.categorias[i + 1].ordem = ordemAntiga;
  }
};
</script>
<template>
  <q-page>
    <div>
      <q-card class="q-ma-md col-xs-11 col-sm-5 col-md-4 col-lg-3 col-xl-2">
        <q-card-section>
          <q-splitter v-model="splitterModel" style="height: 80vh">
            <template v-slot:before>
              <q-tree
                :nodes="arvore"
                node-key="key"
                selected-color="primary"
                v-model:selected="selecionado"
                @update:selected="selecionarAba()"
              />
              <q-separator inset spaced />
              <q-btn
                flat
                color="primary"
                icon="add"
                label="Categoria"
                @click="adicionarCategoriaRaiz()"
              />
            </template>

            <template v-slot:after>
              <q-tab-panels
                v-model="aba"
                animated
                transition-prev="jump-up"
                transition-next="jump-up"
              >
                <q-tab-panel name="categoria">
                  <div class="text-h4 q-mb-xl">Categoria</div>
                  <q-form class="q-gutter-md" v-if="categoria">
                    <q-input
                      v-model="categoria.categoria"
                      label="Categoria"
                      outlined
                    />
                    <q-input
                      v-model="categoria.imagem"
                      label="Imagem"
                      outlined
                    />
                    <q-input
                      v-model="categoria.observacoes"
                      label="Observações"
                      outlined
                      autogrow
                    />
                    <q-btn
                      flat
                      dense
                      round
                      color="secondary"
                      icon="arrow_upward"
                      @click="categoriaParaCima()"
                    />
                    <q-btn
                      flat
                      dense
                      round
                      color="secondary"
                      icon="arrow_downward"
                      @click="categoriaParaBaixo()"
                    />
                    <q-btn
                      flat
                      color="negative"
                      icon="delete"
                      label="Remover"
                      @click="removerCategoria()"
                    />
                    <q-btn
                      flat
                      color="primary"
                      icon="add"
                      label="Sub-Categoria"
                      @click="adicionarCategoria()"
                    />
                    <q-btn
                      flat
                      color="primary"
                      icon="add"
                      label="Produto"
                      @click="adicionarProduto()"
                    />
                  </q-form>
                </q-tab-panel>
                <q-tab-panel name="produto">
                  <div class="text-h4 q-mb-md">Produto</div>
                  {{ codprancheta }}
                  <pre>{{ produto }}</pre>
                </q-tab-panel>
              </q-tab-panels>
            </template>
          </q-splitter>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            tabindex="-1"
            @click="inicializaModel()"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-card>
    </div>
  </q-page>
</template>
