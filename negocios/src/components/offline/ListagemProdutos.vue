<script setup>
import { ref, computed } from "vue";
import { produtoStore } from "stores/produto";
import { negocioStore } from "stores/negocio";
import { Dialog } from "quasar";

const sProduto = produtoStore();
const sNegocio = negocioStore();
const dialogItem = ref(false);
const porPagina = ref(12);

const columns = [
  {
    name: "valortotal",
    required: true,
    label: "R$ Total",
    align: "right",
    field: (row) => row.valortotal,
    format: (val) =>
      new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(val),
    sortable: true,
  },
  {
    name: "quantidade",
    required: true,
    label: "Quant",
    align: "right",
    field: (row) => row.quantidade,
    format: (val) =>
      new Intl.NumberFormat("pt-BR", {
        style: "decimal",
        minimumFractionDigits: 3,
        maximumFractionDigits: 3,
      }).format(val),
    sortable: true,
  },
  {
    name: "produto",
    label: "Descrição",
    field: "produto",
    sortable: true,
    align: "left",
  },
  {
    name: "barras",
    label: "Barras",
    field: "barras",
    sortable: true,
  },
];

const maiorQueZeroRule = [
  (value) => {
    if (!value || parseFloat(value) >= 0) {
      return true;
    }
    return "Negativo!";
  },
];

const preenchimentoObrigatorioRule = [
  (val) => (val && parseFloat(val) >= 0.001) || "* Obrigatório!",
];

const paginas = computed(() => {
  return Math.ceil(sNegocio.quantidadeProdutosAtivos / porPagina.value);
});

const itens = computed(() => {
  const final = sNegocio.paginaAtual * porPagina.value;
  const ret = sNegocio.itensAtivos.slice(final - porPagina.value, final);
  return ret;
});

const inativos = computed(() => {
  const ret = sNegocio.itensInativos;
  return ret;
});

const edicao = ref({
  codprodutobarra: null,
  quantidade: null,
  valorunitario: null,
  valorprodutos: null,
  percentualdesconto: null,
  valordesconto: null,
  valorfrete: null,
  valorseguro: null,
  valoroutras: null,
  valortotal: null,
});

const editar = async (codprodutobarra) => {
  await sNegocio.recarregar();
  const item = sNegocio.negocio.itens.find(function (item) {
    return (
      item.inativo === null &&
      parseInt(item.codprodutobarra) === parseInt(codprodutobarra)
    );
  });
  if (!item) {
    return false;
  }
  edicao.value.codprodutobarra = codprodutobarra;
  edicao.value.quantidade = item.quantidade;
  edicao.value.valorunitario = item.valorunitario;
  edicao.value.valorprodutos = item.valorprodutos;
  edicao.value.percentualdesconto = item.percentualdesconto;
  edicao.value.valordesconto = item.valordesconto;
  edicao.value.valorfrete = item.valorfrete;
  edicao.value.valorseguro = item.valorseguro;
  edicao.value.valoroutras = item.valoroutras;
  edicao.value.valortotal = item.valortotal;
  dialogItem.value = true;
};

const inativar = async (codprodutobarra) => {
  Dialog.create({
    title: "Excluir",
    message: "Tem certeza que você deseja excluir esse item do negócio?",
    cancel: true,
  }).onOk(() => {
    sNegocio.itemInativar(codprodutobarra);
  });
};

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.itemSalvar(
      edicao.value.codprodutobarra,
      parseFloat(edicao.value.quantidade),
      parseFloat(edicao.value.valorunitario),
      parseFloat(edicao.value.valorprodutos),
      parseFloat(edicao.value.percentualdesconto),
      parseFloat(edicao.value.valordesconto),
      parseFloat(edicao.value.valorfrete),
      parseFloat(edicao.value.valorseguro),
      parseFloat(edicao.value.valoroutras),
      parseFloat(edicao.value.valortotal)
    );
    dialogItem.value = false;
  });
};

const recalcularValorProdutos = () => {
  edicao.value.valorprodutos =
    Math.round(edicao.value.quantidade * edicao.value.valorunitario * 100) /
    100;
  recalcularValorDesconto();
};

const recalcularValorDesconto = () => {
  if (edicao.value.percentualdesconto <= 0) {
    edicao.value.valordesconto = null;
  } else {
    edicao.value.valordesconto =
      Math.round(edicao.value.valorprodutos * edicao.value.percentualdesconto) /
      100;
  }
  recalcularValorTotal();
};

const recalcularPercentualDesconto = () => {
  if (edicao.value.valordesconto <= 0) {
    edicao.value.percentualdesconto = null;
  } else {
    edicao.value.percentualdesconto =
      Math.round(
        (edicao.value.valordesconto * 1000) / edicao.value.valorprodutos
      ) / 10;
  }
  recalcularValorTotal();
};

const recalcularValorTotal = () => {
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
</script>

<template>
  <template v-if="sNegocio.negocio">
    <!-- Editar Item -->
    <q-dialog v-model="dialogItem">
      <div class="q-pa-md">
        <q-card>
          <q-form ref="formItem" @submit="salvar(index)">
            <q-card-section>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
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
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorProdutos()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    min="0.01"
                    outlined
                    v-model.number="edicao.valorunitario"
                    prefix="R$"
                    label="Preço"
                    input-class="text-right"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorProdutos()"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    min="0.01"
                    outlined
                    v-model.number="edicao.valorprodutos"
                    prefix="R$"
                    label="Total Produto"
                    input-class="text-right"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorProdutos()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.1"
                    min="0"
                    max="99.9"
                    outlined
                    v-model.number="edicao.percentualdesconto"
                    label="% Desc"
                    input-class="text-right"
                    suffix="%"
                    :rules="maiorQueZeroRule"
                    @change="recalcularValorDesconto()"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    :max="edicao.valorprodutos - 0.01"
                    outlined
                    v-model.number="edicao.valordesconto"
                    prefix="R$"
                    label="Desconto"
                    input-class="text-right"
                    :rules="maiorQueZeroRule"
                    @change="recalcularPercentualDesconto()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    outlined
                    v-model.number="edicao.valorfrete"
                    prefix="R$"
                    label="Frete"
                    input-class="text-right"
                    :rules="maiorQueZeroRule"
                    @change="recalcularValorTotal()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    outlined
                    v-model.number="edicao.valorseguro"
                    prefix="R$"
                    label="Seguro"
                    input-class="text-right"
                    :rules="maiorQueZeroRule"
                    @change="recalcularValorTotal()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    outlined
                    v-model.number="edicao.valoroutras"
                    prefix="R$"
                    label="Outras"
                    input-class="text-right"
                    :rules="maiorQueZeroRule"
                    @change="recalcularValorTotal()"
                  />
                </div>
              </div>
              <div class="row justify-end q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    type="number"
                    step="0.01"
                    outlined
                    v-model.number="edicao.valortotal"
                    prefix="R$"
                    label="Total"
                    input-class="text-right"
                    :rules="preenchimentoObrigatorioRule"
                    @change="recalcularValorTotal()"
                  />
                </div>
              </div>
            </q-card-section>

            <q-card-actions align="right">
              <q-btn
                flat
                label="Cancelar"
                color="primary"
                @click="dialogItem = false"
                tabindex="-1"
              />
              <q-btn type="submit" flat label="Salvar" color="primary" />
            </q-card-actions>
          </q-form>
        </q-card>
      </div>
    </q-dialog>

    <!-- Paginacao -->
    <div class="row q-px-md">
      <q-pagination
        v-model="sNegocio.paginaAtual"
        :max="paginas"
        :max-pages="6"
        boundary-numbers
        gutter="md"
      />
    </div>

    <!-- listagem de produto -->
    <div class="row q-pa-md q-col-gutter-md">
      <div
        class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2"
        v-for="item in itens"
        :key="item.codprodutobarra"
      >
        <q-card>
          <q-img ratio="1" :src="sProduto.urlImagem(item.codimagem)" />
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
                @click="editar(item.codprodutobarra)"
              />
              <q-btn
                round
                color="negative"
                icon="delete"
                class="q-ma-sm"
                @click="inativar(item.codprodutobarra)"
              />
            </div>

            <Transition
              mode="out-in"
              :duration="{ enter: 300, leave: 300 }"
              leave-active-class="animated bounceOut"
              enter-active-class="animated bounceIn"
            >
              <div class="text-h5" :key="item.valortotal">
                <small class="text-grey-7">R$</small>
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(item.valortotal)
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
                @click="
                  sNegocio.itemAdicionarQuantidade(item.codprodutobarra, -1)
                "
              />
              {{ new Intl.NumberFormat("pt-BR").format(item.quantidade) }}
              <q-btn
                size="xs"
                label="+"
                round
                dense
                flat
                @click="
                  sNegocio.itemAdicionarQuantidade(item.codprodutobarra, 1)
                "
              />
              de
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "currency",
                  currency: "BRL",
                }).format(item.valorunitario)
              }}
              <template v-if="item.valordesconto">
                <br />
                -
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(item.valordesconto)
                }}
                (Desconto)
              </template>
              <template v-if="item.valorfrete">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(item.valorfrete)
                }}
                (Frete)
              </template>
              <template v-if="item.valorseguro">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(item.valorseguro)
                }}
                (Seguro)
              </template>
              <template v-if="item.valoroutras">
                <br />+
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "currency",
                    currency: "BRL",
                  }).format(item.valoroutras)
                }}
                (Outras)
              </template>
            </div>
            <div class="text-caption text-grey-7">
              {{ item.barras }} |
              {{ item.produto }}
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div></div>
    </div>

    <!-- Paginacao -->
    <div class="row q-px-md q-mb-lg">
      <q-pagination
        v-model="sNegocio.paginaAtual"
        :max="paginas"
        :max-pages="6"
        boundary-numbers
        gutter="md"
      />
    </div>

    <div class="q-pa-md q-mb-xl" v-if="inativos.length > 0">
      <q-table
        :rows="inativos"
        virtual-scroll
        title="Itens Excluídos"
        :rows-per-page-options="[0]"
        :columns="columns"
        selection="multiple"
      >
        <template v-slot:header-selection> </template>
        <template v-slot:body-selection="scope">
          <q-avatar>
            <img :src="sProduto.urlImagem(scope.row.codimagem)" />
          </q-avatar>
        </template>
      </q-table>
    </div>
  </template>

  <!-- <q-btn fab icon="plus" color="primary" /> -->
</template>
