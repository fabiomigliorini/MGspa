<script setup>
import { ref, computed, watch } from "vue";
import { produtoStore } from "stores/produto";
import { negocioStore } from "stores/negocio";
import { sincronizacaoStore } from "stores/sincronizacao";
import { useQuasar } from "quasar";

const $q = useQuasar();
const sProduto = produtoStore();
const sNegocio = negocioStore();
const sSinc = sincronizacaoStore();
const quantidade = ref(1);
const barras = ref(null);

const labelQuantidade = computed({
  get() {
    let lbl = new Intl.NumberFormat("pt-BR").format(quantidade.value);
    lbl += " x";
    return lbl;
  },
});

const informarVendedor = async (codpessoavendedor) => {
  await sNegocio.informarVendedor(codpessoavendedor);
  $q.notify({
    type: "positive",
    message: "Vendedor '" + sNegocio.negocio.fantasiavendedor + "' informado.",
    timeout: 1500, // 1,5 segundos
  });
  var audio = new Audio("successo.mp3");
  audio.play();
};

const abrirComanda = (codnegocio) => {
  $q.notify({
    type: "negative",
    message: "Precisa implementar busca da comanda " + codnegocio + "!",
    timeout: 0, // 20 minutos
    actions: [{ icon: "close", color: "white" }],
  });
  var audio = new Audio("erro.mp3");
  audio.play();
};

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

const buscarBarras = async () => {
  if (!barras.value) {
    return;
  }
  const txt = barras.value;
  barras.value = "";

  if (txt.length == 11) {
    const prefixo = txt.substring(0, 3);
    const codigo = txt.substring(3, 11);
    if (!isNaN(codigo)) {
      switch (prefixo) {
        // Comanda Vendedor (Ex  VDD00010022)
        case "VDD":
          informarVendedor(parseInt(codigo));
          return;

        // Comanda Negocio (Ex NEG03386672)
        case "NEG":
          abrirComanda(parseInt(codigo));
          return;
      }
    }
  }

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
    sNegocio.paginaAtual = 1;
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

const adicionarPelaListagem = (
  codprodutobarra,
  barras,
  codproduto,
  produto,
  codimagem,
  preco
) => {
  sNegocio.itemAdicionar(
    codprodutobarra,
    barras,
    codproduto,
    produto,
    codimagem,
    quantidade.value,
    preco
  );
  sProduto.dialogPesquisa = false;
  sNegocio.paginaAtual = 1;
  quantidade.value = 1;
  var audio = new Audio("successo.mp3");
  audio.play();
};
</script>

<template>
  <q-input
    type="text"
    outlined
    ref="refBarras"
    v-model="barras"
    label="Barras"
    for="inputBarras"
    input-class="text-right"
    @change="buscarBarras()"
    :prefix="labelQuantidade"
  >
    <template v-slot:append>
      <q-btn
        round
        dense
        flat
        icon="add"
        @click="buscarBarras()"
        :disable="barras == null || barras == ''"
      >
        <q-tooltip class="bg-accent">Adicionar</q-tooltip>
      </q-btn>
      <q-btn
        round
        dense
        flat
        icon="search"
        @click="sProduto.dialogPesquisa = true"
      >
        <q-tooltip class="bg-accent">Pesquisar</q-tooltip>
      </q-btn>
      <q-btn
        round
        dense
        flat
        icon="refresh"
        :loading="sSinc.importacao.rodando"
        :percentage="sSinc.importacao.progresso * 100"
        @click="sSinc.sincronizar()"
      >
        <template v-slot:loading>
          <q-spinner-dots />
        </template>
        <q-tooltip class="bg-accent"
          >Sincronizar Cadastro de Produtos</q-tooltip
        >
      </q-btn>
    </template>
  </q-input>

  <!-- Pesquisa de Produto -->
  <q-dialog v-model="sProduto.dialogPesquisa" maximized>
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
              icon="close"
              @click="sProduto.textoPesquisa = ''"
            >
              <q-tooltip class="bg-accent">Limpar</q-tooltip>
            </q-btn>
            <q-btn round dense flat icon="search" @click="sProduto.pesquisar()">
              <q-tooltip class="bg-accent">Pesquisar</q-tooltip>
            </q-btn>
            <q-btn
              round
              dense
              flat
              icon="logout"
              @click="sProduto.dialogPesquisa = false"
            >
              <q-tooltip class="bg-accent">Fechar</q-tooltip>
            </q-btn>
          </template>
        </q-input>
      </q-card-section>

      <q-card-section class="q-pa-none q-ma-none">
        <div class="row q-pa-md q-col-gutter-md">
          <template
            v-for="produto in sProduto.resultadoPesquisa"
            v-bind:key="produto.codprodutobarra"
          >
            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-3 col-xs-6">
              <q-card
                v-ripple
                class="cursor-pointer q-hoverable"
                @click="
                  adicionarPelaListagem(
                    produto.codprodutobarra,
                    produto.barras,
                    produto.codproduto,
                    produto.produto,
                    produto.codimagem,
                    produto.preco
                  )
                "
              >
                <span class="q-focus-helper"></span>
                <q-img ratio="1" :src="sProduto.urlImagem(produto.codimagem)" />

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
</template>