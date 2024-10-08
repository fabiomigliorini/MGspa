<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { produtoStore } from "stores/produto";
import { negocioStore } from "stores/negocio";
import { listagemStore } from "stores/listagem";
import { Notify, Dialog, debounce } from "quasar";
import { falar } from "../../utils/falar.js";
import emitter from "../../utils/emitter.js";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const router = useRouter();
const sProduto = produtoStore();
const sNegocio = negocioStore();
const sListagem = listagemStore();
const quantidade = ref(1);
const barras = ref(null);
const barcodeVideo = ref(null);
const dialogOrcamento = ref(false);
const uuidOrcamento = ref(null);

var stream = null;
var leitorLigado = ref(false);

const labelQuantidade = computed({
  get() {
    let lbl = new Intl.NumberFormat("pt-BR").format(quantidade.value);
    lbl += " x";
    return lbl;
  },
});

const orcamento = (inicioUuid) => {
  uuidOrcamento.value = inicioUuid;
  dialogOrcamento.value = true;
};

watch(
  () => uuidOrcamento.value,
  () => {
    buscarOrcamento();
  }
);

const buscarOrcamento = debounce(() => {
  if (!uuidOrcamento.value) {
    sListagem.orcamentos = [];
    return;
  }
  sListagem.getOrcamentos(uuidOrcamento.value);
});

const unificarOrcamento = (orc) => {
  Dialog.create({
    title: "Abrir como Comanda",
    message:
      "Tem certeza que deseja abrir esse orçamento como uma comanda? Esta operação não poderá ser desfeita.",
    cancel: true,
  }).onOk(() => {
    unificarComanda(parseInt(orc.codnegocio));
    dialogOrcamento.value = false;
  });
};

const informarVendedor = async (codpessoavendedor) => {
  await sNegocio.informarVendedor(codpessoavendedor);
  Notify.create({
    type: "positive",
    message: "Vendedor '" + sNegocio.negocio.fantasiavendedor + "' informado.",
    timeout: 1000, // 1 segundo
    actions: [{ icon: "close", color: "white" }],
  });
  if (sNegocio.negocio.codpessoavendedor) {
    falar("Vendedor " + sNegocio.negocio.fantasiavendedor.split(" ")[0]);
  }
  var audio = new Audio("successo.mp3");
  audio.play();
};

const unificarComanda = async (codnegociocomanda) => {
  let sucesso = false;
  try {
    sucesso = await sNegocio.unificarComanda(codnegociocomanda);
  } catch (error) {}
  if (sucesso) {
    router.push("/offline/" + sNegocio.negocio.uuid);
    var audio = new Audio("sucesso.mp3");
    audio.play();
    falar("Comanda Lida!");
  } else {
    var audio = new Audio("erro.mp3");
    audio.play();
    falar("Falha ao buscar comanda!");
  }
};

const valeCompras = async (codigo) => {
  emitter.emit("valeComprasLido", codigo);
  falar("Vale Compras Lido!");
};

const comanda = async () => {
  Dialog.create({
    title: "Informe o número da Comanda",
    message: "Informe o número da comanda ou negócio que deseja unificar!",
    prompt: {
      model: "",
      isValid: (val) => val > 100,
      outlined: true,
      type: "Number", // optional
      min: 1, // optional
      max: 99999999, // optional
      step: 1, // optional
      placeholder: "Comanda...",
      // inputClass: "text-center",
    },
    cancel: true,
  }).onOk(async (codnegociocomanda) => {
    unificarComanda(parseInt(codnegociocomanda));
  });
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
  quantidade.value = Math.abs(quant);
  barras.value = "";
});

const buscarBarras = async () => {
  if (!barras.value) {
    return;
  }
  const txt = barras.value;
  barras.value = "";
  adicionarPeloCodigoBarras(txt);
};

const adicionarPeloCodigoBarras = async (txt) => {
  if (txt.length == 11) {
    const prefixo = txt.substring(0, 3);
    const codigo = txt.substring(3, 11);
    switch (prefixo.toUpperCase()) {
      // Comanda Vendedor (Ex VDD00010022)
      case "VDD":
        informarVendedor(parseInt(codigo));
        return;

      // Comanda Negocio (Ex NEG03386672)
      case "NEG":
        unificarComanda(parseInt(codigo));
        return;

      // Orçamento (Ex ORC7d5864f5)
      case "ORC":
        orcamento(codigo);
        return;

      // Vale compras (Ex VAL00532214)
      case "VAL":
        valeCompras(parseInt(codigo));
        return;
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
    Notify.create({
      type: "positive",
      message: "Código " + txt + " adicionado.",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
    sNegocio.paginaAtual = 1;
    var audio = new Audio("successo.mp3");
    audio.play();
  } else {
    Notify.create({
      type: "negative",
      message: "Falha ao buscar código " + txt + "!",
      timeout: 0, // 20 minutos
      actions: [{ icon: "close", color: "white" }],
    });
    var audio = new Audio("erro.mp3");
    audio.play();
    falar("Não encontrei!");
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

const leitor = async () => {
  if (leitorLigado.value) {
    desligarLeitor();
  } else {
    ligarLeitor();
  }
};

const ligarLeitor = async () => {
  leitorLigado.value = true;
  setTimeout(async () => {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        height: { exact: barcodeVideo.value.offsetWidth * 2 },
        width: { exact: barcodeVideo.value.offsetHeight * 2 },
        focusMode: "continuous",
        focusDistance: 0.03,
        colorTemperature: 5000,
        zoom: 1.2,
        facingMode: {
          ideal: "environment",
        },
      },
      audio: false,
    });
    barcodeVideo.value.srcObject = stream;
    await barcodeVideo.value.play();
  }, 200);
};

const desligarLeitor = async () => {
  if (stream) {
    stream.getTracks().forEach((track) => {
      track.stop();
    });
  }
  barcodeVideo.value.srcObject = null;
  leitorLigado.value = false;
};

const lerCodigoBarras = async () => {
  const barcodeDetector = new BarcodeDetector({
    formats: [
      // "aztec",
      "code_128",
      "code_39",
      "code_93",
      "codabar",
      // "data_matrix",
      "ean_13",
      "ean_8",
      "itf",
      // "pdf417",
      // "qr_code",
      "upc_a",
      "upc_e",
    ],
  });
  var barras = null;
  var barcodes = [];
  var tentativas = 0;
  do {
    tentativas++;
    try {
      barcodes = await barcodeDetector.detect(barcodeVideo.value);
      if (barcodes.length <= 0) {
        continue;
      }
      barcodes.forEach((barcode) => {
        barras = barcode.rawValue;
      });
    } catch (error) {}
  } while (!barras && tentativas < 20);
  if (barras) {
    adicionarPeloCodigoBarras(barras);
  } else {
    falar("Não consigo ler!");
  }
};

onMounted(() => {
  emitter.on("adicionarProduto", (prod) => {
    adicionarPelaListagem(
      prod.codprodutobarra,
      prod.barras,
      prod.codproduto,
      prod.produto,
      prod.codimagem,
      prod.preco
    );
  });
});

onUnmounted(() => {
  emitter.off("adicionarProduto");
});
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
    inputmode="tel"
    autocomplete="off"
    autofocus
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
      <q-btn round dense flat icon="mdi-barcode-scan" @click="leitor()">
        <q-tooltip class="bg-accent">Leitor de Codigo de Barras</q-tooltip>
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
      <q-fab icon="receipt" flat padding="5px" direction="down">
        <!-- COMANDA -->
        <q-fab-action
          external-label
          label-class="bg-primary"
          label="Importar Comanda"
          icon="receipt"
          color="primary"
          @click="comanda()"
          label-position="left"
        />
        <!-- COMANDA -->
        <q-fab-action
          external-label
          label-class="bg-primary"
          label="Importar Orçamento"
          icon="mdi-clipboard-edit-outline"
          color="primary"
          @click="orcamento()"
          label-position="left"
        />
      </q-fab>
    </template>
  </q-input>

  <!-- Leitor de Codigo de Barras -->
  <div v-if="leitorLigado">
    <div
      style="
        border-top: 2px solid red;
        position: relative;
        top: 0;
        right: 0;
        transform: translateY(50px);
        width: 100%;
      "
    ></div>
    <video ref="barcodeVideo" style="width: 100%; height: 100px"></video>
    <q-btn
      color="primary"
      icon="mdi-barcode"
      @click="lerCodigoBarras()"
      style="width: 100%"
    >
      Ler código de Barras!
    </q-btn>
  </div>

  <!-- Pesquisa Orçamento -->
  <q-dialog v-model="dialogOrcamento">
    <q-card style="width: 360px">
      <q-card-section>
        <q-input
          type="text"
          mask="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
          outlined
          v-model="uuidOrcamento"
          autofocus
          label="UUID"
          class="q-mb-md"
        />
        <q-scroll-area style="height: 400px">
          <q-list v-if="sListagem.orcamentos.length > 0">
            <template v-for="orc in sListagem.orcamentos" :key="orc.codnegocio">
              <q-item clickable v-ripple @click="unificarOrcamento(orc)">
                <q-item-section>
                  <q-item-label>
                    {{ orc.estoquelocal }}
                  </q-item-label>
                  <q-item-label caption v-if="orc.codpessoa != 1">
                    {{ orc.fantasia }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ orc.naturezaoperacao }}
                  </q-item-label>
                  <q-item-label caption v-if="orc.codpessoavendedor">
                    {{ orc.fantasiavendedor }}
                  </q-item-label>
                  <q-item-label caption>
                    #{{ String(orc.codnegocio).padStart(8, "0") }}
                  </q-item-label>
                  <q-item-label caption class="ellipsis">
                    {{ orc.uuid }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <q-item-label>
                    R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(orc.valortotal)
                    }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ moment(orc.lancamento).fromNow() }}
                  </q-item-label>
                  <q-item-label caption> </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
            </template>
          </q-list>
          <template v-else-if="uuidOrcamento">
            <h4>Nenhum Orçamento Localizado!</h4>
            Confirme se você digitou o código UUID correto ou ainda se quem
            emitiu o orçamento fez o processo de sincronização!
          </template>
          <template v-else>
            <h4>Digite o UUID!</h4>
            Digite o código UUID para pesquisar os orçamentos em aberto!
          </template>
        </q-scroll-area>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          flat
          label="Cancelar"
          color="primary"
          @click="dialogOrcamento = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- Pesquisa de Produto -->
  <q-dialog v-model="sProduto.dialogPesquisa" maximized>
    <q-card>
      <q-card-section class="bg-primary text-white">
        <div class="row q-col-gutter-sm">
          <q-input
            outlined
            autofocus
            v-model="sProduto.textoPesquisa"
            label="Pesquisa"
            ref="refPesquisa"
            bg-color="white"
            class="col"
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
              <q-btn
                round
                dense
                flat
                icon="search"
                @click="sProduto.pesquisar()"
              >
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
          <q-select
            outlined
            borderless
            v-model="sProduto.sortPesquisa"
            :options="['Alfabética', 'Preço', 'Código', 'Barras']"
            label="Ordem"
            bg-color="white"
            style="width: 130px"
            @update:model-value="sProduto.pesquisar()"
          />
        </div>
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
