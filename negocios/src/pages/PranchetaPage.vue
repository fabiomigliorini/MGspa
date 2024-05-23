<script setup>
import { ref, onMounted, watch } from "vue";
import { db } from "src/boot/db";
import { produtoStore } from "src/stores/produto";
import { uid, Dialog, Notify } from "quasar";
import { api } from "src/boot/axios";
import { sincronizacaoStore } from "src/stores/sincronizacao";

const sProduto = produtoStore();
const sSinc = sincronizacaoStore();
const prancheta = ref([]);
const arvore = ref([]);
const splitterModel = ref(30);
const selecionado = ref(null);
const codprancheta = ref(null);
const codpranchetacategoria = ref(null);
const codpranchetacategoriapai = ref(null);
const produto = ref(null);
const categoria = ref(null);
const aba = ref(null);
const rodando = ref(false);
const refTree = ref(null);
const listagemCategorias = ref([]);

onMounted(() => {
  carregarPrancheta();
});

const carregarPrancheta = async () => {
  await sSinc.sincronizarPrancheta(true);
  prancheta.value = await db.prancheta.orderBy("ordem").toArray();
  produto.value = null;
  categoria.value = null;
  codprancheta.value = null;
  codpranchetacategoria.value = null;
  aba.value = null;
  selecionado.value = null;
};

const cancelar = () => {
  Dialog.create({
    title: "Tem certeza?",
    message: "Você vai perder todas as alterações feitas na prancheta!",
    cancel: true,
  }).onOk(async () => {
    rodando.value = true;
    carregarPrancheta();
    rodando.value = false;
  });
};

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Salvar as modificações feitas na prancheta?",
    cancel: true,
  }).onOk(async () => {
    rodando.value = true;
    try {
      let data = {
        prancheta: JSON.parse(JSON.stringify(prancheta.value)),
        pdv: sSinc.pdv.uuid,
      };
      const ret = await api.put("/api/v1/pdv/prancheta/", data);
      await sSinc.sincronizarPrancheta(true);
      prancheta.value = await db.prancheta.orderBy("ordem").toArray();
      Notify.create({
        type: "positive",
        message: "Prancheta Atualizada!",
      });
    } catch (error) {
      console.log(error);
      console.log("Impossível sincronizar Prancheta");
      Notify.create({
        type: "negative",
        message: error.response.data.message,
        actions: [{ icon: "close", color: "white" }],
      });
    } finally {
      produto.value = null;
      categoria.value = null;
      codprancheta.value = null;
      codpranchetacategoria.value = null;
      aba.value = null;
      selecionado.value = null;
      rodando.value = false;
    }
  });
};

const adicionarFilhosArvore = async (nodo, categoria) => {
  nodo.children = [];
  categoria.categorias
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((cat) => {
      let item = {
        key: "CAT" + cat.codpranchetacategoria,
        label: cat.categoria,
      };
      if (cat.imagem) {
        item.img = cat.imagem;
      } else {
        item.icon = "mdi-bookshelf";
      }
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
      };
      if (cat.imagem) {
        item.img = cat.imagem;
      } else {
        item.icon = "mdi-bookshelf";
      }
      adicionarFilhosArvore(item, cat);
      arvoreTemp.push(item);
    });
  arvore.value = arvoreTemp;
};

watch(
  () => prancheta.value,
  () => {
    montarArvore();
    montarListagemCategorias();
  },
  { deep: true }
);

const montarListagemSubCategorias = (cats, labelpai) => {
  cats
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((cat) => {
      let label = labelpai + " > " + cat.categoria;
      listagemCategorias.value.push({
        label: label,
        value: cat.codpranchetacategoria,
      });
      montarListagemSubCategorias(cat.categorias, label);
    });
};

const montarListagemCategorias = () => {
  listagemCategorias.value = [{ label: "Raiz", value: null }];
  prancheta.value
    .sort((a, b) => a.ordem - b.ordem)
    .forEach((cat) => {
      listagemCategorias.value.push({
        label: cat.categoria,
        value: cat.codpranchetacategoria,
      });
      montarListagemSubCategorias(cat.categorias, cat.categoria);
    });
};

const selecionarAba = (sel) => {
  if (!selecionado.value) {
    aba.value = null;
    return;
  }
  switch (selecionado.value.substring(0, 3)) {
    case "PRD":
      aba.value = "produto";
      codprancheta.value = selecionado.value.replace("PRD", "");
      produto.value = localizaProduto(codprancheta.value, prancheta.value);
      codpranchetacategoriapai.value = produto.value.codpranchetacategoria;
      break;
    case "CAT":
      refTree.value.setExpanded(sel, true);
      aba.value = "categoria";
      codpranchetacategoria.value = selecionado.value.replace("CAT", "");
      categoria.value = localizaCategoria(
        codpranchetacategoria.value,
        prancheta.value
      );
      codpranchetacategoriapai.value = categoria.value.codpranchetacategoriapai;
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
    // procura produto
    let ret = await sProduto.buscarBarras(barras);
    if (ret.length <= 0) {
      Notify.create({
        type: "negative",
        message: `Nenhum produto localizado com o código de barras '${barras}'`,
      });
    }

    // verifica ordem
    let ordem = 1;
    if (categoria.value.produtos.length > 0) {
      ordem = Math.max(...categoria.value.produtos.map((prd) => prd.ordem)) + 1;
    }

    // monta objeto novo produto
    let novo = {
      codprancheta: uid(),
      codprodutobarra: ret[0].codprodutobarra,
      codpranchetacategoria: categoria.value.codpranchetacategoria,
      ordem: ordem,
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
    };

    //adiciona
    categoria.value.produtos.push(novo);
    Notify.create({
      type: "positive",
      message: `Produto adicionado !`,
    });
    console.log("CAT" + categoria.value.codpranchetacategoria);
    setTimeout(() => {
      refTree.value.setExpanded(
        "CAT" + categoria.value.codpranchetacategoria,
        true
      );
    }, 750);
  });
};

const removerCategoria = () => {
  Dialog.create({
    title: "Excluir",
    message: "Deseja mesmo excluir a categoria?",
    cancel: true,
  }).onOk(async () => {
    if (categoria.value.codpranchetacategoriapai == null) {
      prancheta.value = prancheta.value.filter((cat) => {
        return (
          cat.codpranchetacategoria != categoria.value.codpranchetacategoria
        );
      });
    } else {
      let pai = localizaCategoria(
        categoria.value.codpranchetacategoriapai,
        prancheta.value
      );
      pai.categorias = pai.categorias.filter((cat) => {
        return (
          cat.codpranchetacategoria != categoria.value.codpranchetacategoria
        );
      });
    }
    codpranchetacategoria.value = null;
    categoria.value = null;
    aba.value = null;
  });
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

const removerProduto = () => {
  Dialog.create({
    title: "Excluir",
    message: "Deseja mesmo excluir o produto da prancheta?",
    cancel: true,
  }).onOk(async () => {
    let pai = localizaCategoria(
      produto.value.codpranchetacategoria,
      prancheta.value
    );
    pai.produtos = pai.produtos.filter((prd) => {
      return prd.codprancheta != produto.value.codprancheta;
    });
    codprancheta.value = null;
    produto.value = null;
    aba.value = null;
  });
};

const produtoParaCima = () => {
  let pai = localizaCategoria(
    produto.value.codpranchetacategoria,
    prancheta.value
  );
  const i = pai.produtos.findIndex((prd) => {
    return prd.codprancheta == produto.value.codprancheta;
  });
  if (i <= 0) {
    return;
  }
  const ordemAntiga = produto.value.ordem;
  produto.value.ordem = pai.produtos[i - 1].ordem;
  pai.produtos[i - 1].ordem = ordemAntiga;
};

const produtoParaBaixo = () => {
  let pai = localizaCategoria(
    produto.value.codpranchetacategoria,
    prancheta.value
  );
  const i = pai.produtos.findIndex((prd) => {
    return prd.codprancheta == produto.value.codprancheta;
  });
  if (i < 0 || i >= pai.produtos.length - 1) {
    return;
  }
  const ordemAntiga = produto.value.ordem;
  produto.value.ordem = pai.produtos[i + 1].ordem;
  pai.produtos[i + 1].ordem = ordemAntiga;
};

const linkProduto = (codproduto) => {
  return process.env.MGLARA_URL + "produto/" + codproduto;
};

const alterarCategoriaProduto = (codpranchetacategorianova) => {
  if (codpranchetacategorianova == null) {
    Notify.create({
      type: "negative",
      message: "Impossível mover um produto para a Raiz!",
      actions: [{ icon: "close", color: "white" }],
    });
    produto.value.codpranchetacategoria = codpranchetacategoriapai.value;
    return false;
  }

  // localiza a nova categoria
  let novaCategoria = localizaCategoria(
    codpranchetacategorianova,
    prancheta.value
  );

  // coloca por ultimo na ordem
  produto.value.ordem = 1;
  if (novaCategoria.produtos.length > 0) {
    produto.value.ordem =
      Math.max(...novaCategoria.produtos.map((prd) => prd.ordem)) + 1;
  }

  // adiciona produto na nova categoria
  novaCategoria.produtos.push(produto.value);

  // exclui produto da antiga categoria
  let antigaCategoria = localizaCategoria(
    codpranchetacategoriapai.value,
    prancheta.value
  );
  antigaCategoria.produtos = antigaCategoria.produtos.filter((prd) => {
    return prd.codprancheta != produto.value.codprancheta;
  });

  // sinaliza nova categoria pai
  codpranchetacategoriapai.value = codpranchetacategorianova;
};

const alterarCategoriaPai = (codpranchetacategorianova) => {
  if (codpranchetacategorianova == categoria.value.codpranchetacategoria) {
    Notify.create({
      type: "negative",
      message: "Impossível mover uma categoria para dentro dela mesma!",
      actions: [{ icon: "close", color: "white" }],
    });
    categoria.value.codpranchetacategoriapai = codpranchetacategoriapai.value;
    return false;
  }

  // se categoria nova é a raiz
  if (codpranchetacategorianova == null) {
    // coloca em ultimo na ordem
    categoria.value.ordem = 1;
    if (prancheta.value.length > 0) {
      categoria.value.ordem =
        Math.max(...prancheta.value.map((cat) => cat.ordem)) + 1;
    }

    // adiciona na raiz
    prancheta.value.push(categoria.value);
  } else {
    // procura nova categoria
    let novaCategoria = localizaCategoria(
      codpranchetacategorianova,
      prancheta.value
    );

    // coloca em ultimo na ordem
    categoria.value.ordem = 1;
    if (novaCategoria.categorias.length > 0) {
      categoria.value.ordem =
        Math.max(...novaCategoria.categorias.map((cat) => cat.ordem)) + 1;
    }

    // adiciona na categoria
    novaCategoria.categorias.push(categoria.value);
  }

  // se categoria antiga era raiz
  if (codpranchetacategoriapai.value == null) {
    // remove categoria da prancheta
    prancheta.value = prancheta.value.filter((cat) => {
      return cat.codpranchetacategoria != categoria.value.codpranchetacategoria;
    });
  } else {
    // localiza antiga categoria
    let antigaCategoria = localizaCategoria(
      codpranchetacategoriapai.value,
      prancheta.value
    );

    // remove subcategoria da antiga categoria
    antigaCategoria.categorias = antigaCategoria.categorias.filter((cat) => {
      return cat.codpranchetacategoria != categoria.value.codpranchetacategoria;
    });
  }

  codpranchetacategoriapai.value = codpranchetacategorianova;
};
</script>
<template>
  <q-page>
    <div>
      <q-card class="q-ma-md col-xs-11 col-sm-5 col-md-4 col-lg-3 col-xl-2">
        <q-card-section>
          <q-splitter v-model="splitterModel" style="height: 79vh">
            <template v-slot:before>
              <q-tree
                :nodes="arvore"
                no-selection-unset
                accordion
                node-key="key"
                selected-color="primary"
                v-model:selected="selecionado"
                @update:selected="selecionarAba"
                ref="refTree"
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

                  <div class="row">
                    <div class="col" style="max-width: 350px">
                      <q-form class="q-gutter-md" v-if="categoria">
                        <q-select
                          outlined
                          v-model="categoria.codpranchetacategoriapai"
                          :options="listagemCategorias"
                          label="Categoria Pai"
                          emit-value
                          map-options
                          @update:model-value="alterarCategoriaPai"
                        />
                        <q-input
                          v-model="categoria.categoria"
                          label="Categoria"
                          autofocus
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
                        <q-btn-group flat>
                          <q-btn
                            flat
                            round
                            color="secondary"
                            icon="arrow_upward"
                            @click="categoriaParaCima()"
                          >
                            <q-tooltip color="secondary"> Para Cima </q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            round
                            color="secondary"
                            icon="arrow_downward"
                            @click="categoriaParaBaixo()"
                          >
                            <q-tooltip color="secondary">
                              Para Baixo
                            </q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            color="negative"
                            icon="delete"
                            @click="removerCategoria()"
                          >
                            <q-tooltip color="secondary">
                              Excluir Categoria
                            </q-tooltip>
                          </q-btn>
                          <q-btn-dropdown
                            flat
                            auto-close
                            color="primary"
                            icon="add"
                          >
                            <q-item clickable @click="adicionarCategoria()">
                              <q-item-section>
                                <q-item-label class="text-primary">
                                  Nova Sub-Categoria
                                </q-item-label>
                              </q-item-section>
                            </q-item>
                            <q-separator />
                            <q-item clickable @click="adicionarProduto()">
                              <q-item-section>
                                <q-item-label class="text-primary">
                                  Novo Produto
                                </q-item-label>
                              </q-item-section>
                            </q-item>
                          </q-btn-dropdown>
                        </q-btn-group>
                      </q-form>
                    </div>
                    <div class="col q-px-md">
                      <q-img
                        v-if="categoria.imagem"
                        :src="categoria.imagem"
                        spinner-color="white"
                        style=""
                      />
                    </div>
                  </div>
                </q-tab-panel>
                <q-tab-panel name="produto">
                  <div class="text-h4 q-mb-xl">Produto</div>
                  <div class="row">
                    <div class="col" style="max-width: 350px">
                      <q-form v-if="produto" class="q-gutter-md">
                        <q-select
                          outlined
                          v-model="produto.codpranchetacategoria"
                          :options="listagemCategorias"
                          label="Categoria"
                          emit-value
                          map-options
                          @update:model-value="alterarCategoriaProduto"
                        />
                        <q-input
                          v-model="produto.barras"
                          label="Barras"
                          disable
                          outlined
                        />
                        <q-input
                          v-model="produto.produto"
                          label="Produto"
                          disable
                          outlined
                        />
                        <q-input
                          v-model="produto.preco"
                          label="Preço"
                          type="number"
                          disable
                          outlined
                        />
                        <q-input
                          v-model="produto.descricao"
                          autofocus
                          label="Descrição"
                          outlined
                        />
                        <q-input
                          v-model="produto.observacoes"
                          label="Observações"
                          outlined
                          autogrow
                        />
                        <q-btn-group flat>
                          <q-btn
                            flat
                            round
                            color="secondary"
                            icon="arrow_upward"
                            @click="produtoParaCima()"
                          >
                            <q-tooltip> Para Cima </q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            round
                            color="secondary"
                            icon="arrow_downward"
                            @click="produtoParaBaixo()"
                          >
                            <q-tooltip> Para Baixo </q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            color="negative"
                            icon="delete"
                            @click="removerProduto()"
                          >
                            <q-tooltip> Excluir Produto </q-tooltip>
                          </q-btn>
                          <q-btn
                            icon="launch"
                            round
                            flat
                            :href="linkProduto(produto.codproduto)"
                            target="_blank"
                          >
                            <q-tooltip>
                              Ir para o cadastro do Produto
                            </q-tooltip>
                          </q-btn>
                        </q-btn-group>
                      </q-form>
                    </div>
                    <div class="col q-px-md">
                      <q-img
                        v-if="produto.codimagem"
                        :src="sProduto.urlImagem(produto.codimagem)"
                        spinner-color="white"
                        style=""
                      />
                    </div>
                  </div>
                </q-tab-panel>
              </q-tab-panels>
            </template>
          </q-splitter>
        </q-card-section>
        <q-card-actions>
          <q-btn
            flat
            label="Cancelar"
            color="negative"
            tabindex="-1"
            @click="cancelar()"
            :disable="rodando"
          />
          <q-btn
            flat
            label="Salvar"
            color="primary"
            @click="salvar()"
            :disable="rodando"
          />
        </q-card-actions>
      </q-card>
    </div>
  </q-page>
</template>
