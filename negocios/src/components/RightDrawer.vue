<script setup>
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import { Dialog } from "quasar";
import { formataCpf } from "../utils/formatador.js";
import { formataCnpjCpf } from "../utils/formatador.js";
import moment from "moment/min/moment-with-locales";
import SelectPessoa from "components/selects/SelectPessoa.vue";
import SelectNaturezaOperacao from "components/selects/SelectNaturezaOperacao.vue";
import SelectEstoqueLocal from "components/selects/SelectEstoqueLocal.vue";
import { db } from "boot/db";
import { LoadingBar } from "quasar";
moment.locale("pt-br");

const sNegocio = negocioStore();

const edicao = ref({
  valorprodutos: null,
  percentualdesconto: null,
  valordesconto: null,
  valorfrete: null,
  valorseguro: null,
  valoroutras: null,
  valortotal: null,
});

const edicaoPessoa = ref({
  codestoquelocal: null,
  codnaturezaoperacao: null,
  codpessoa: null,
  cpf: null,
  observacoes: null,
});

const vendedores = ref([]);

const dialogValores = ref(false);
const dialogPessoa = ref(false);
const dialogVendedor = ref(false);

const editarValores = () => {
  edicao.value.valorprodutos = sNegocio.negocio.valorprodutos;
  if (sNegocio.negocio.valordesconto > 0 && sNegocio.negocio.valorprodutos) {
    edicao.value.percentualdesconto =
      Math.round(
        (sNegocio.negocio.valordesconto / sNegocio.negocio.valorprodutos) * 1000
      ) / 10;
  } else {
    edicao.value.percentualdesconto = null;
  }
  edicao.value.valordesconto = sNegocio.negocio.valordesconto;
  edicao.value.valorfrete = sNegocio.negocio.valorfrete;
  edicao.value.valorseguro = sNegocio.negocio.valorseguro;
  edicao.value.valoroutras = sNegocio.negocio.valoroutras;
  edicao.value.valortotal = sNegocio.negocio.valortotal;
  dialogValores.value = true;
};

function validarCPF(cpf) {
  if (!cpf) {
    return true;
  }
  cpf = cpf.replace(/[^\d]+/g, "");
  if (cpf == "") {
    return true;
  }
  // Elimina CPFs invalidos conhecidos
  if (
    cpf.length != 11 ||
    cpf == "00000000000" ||
    cpf == "11111111111" ||
    cpf == "22222222222" ||
    cpf == "33333333333" ||
    cpf == "44444444444" ||
    cpf == "55555555555" ||
    cpf == "66666666666" ||
    cpf == "77777777777" ||
    cpf == "88888888888" ||
    cpf == "99999999999"
  ) {
    return "CPF Inválido";
  }
  // Valida 1o digito
  var add = 0;
  for (var i = 0; i < 9; i++) {
    add += parseInt(cpf.charAt(i)) * (10 - i);
  }
  var rev = 11 - (add % 11);
  if (rev == 10 || rev == 11) {
    rev = 0;
  }
  if (rev != parseInt(cpf.charAt(9))) {
    return "CPF Inválido";
  }
  // Valida 2o digito
  add = 0;
  for (i = 0; i < 10; i++) {
    add += parseInt(cpf.charAt(i)) * (11 - i);
  }
  rev = 11 - (add % 11);
  if (rev == 10 || rev == 11) {
    rev = 0;
  }
  if (rev != parseInt(cpf.charAt(10))) {
    return "CPF Inválido";
  }
  return true;
}

const editarPessoa = () => {
  edicaoPessoa.value.codestoquelocal = sNegocio.negocio.codestoquelocal;
  edicaoPessoa.value.codnaturezaoperacao = sNegocio.negocio.codnaturezaoperacao;
  edicaoPessoa.value.codpessoa = sNegocio.negocio.codpessoa;
  edicaoPessoa.value.cpf = sNegocio.negocio.cpf;
  edicaoPessoa.value.observacoes = sNegocio.negocio.observacoes;
  dialogPessoa.value = true;
};

const buscarListagemVendedores = async () => {
  LoadingBar.start();
  vendedores.value = await db.pessoa
    .filter((pessoa) => {
      if (!pessoa.vendedor) {
        return false;
      }
      if (pessoa.inativo) {
        return false;
      }
      return true;
    })
    .sortBy("fantasia");
  LoadingBar.stop();
};

const editarVendedor = async () => {
  if (vendedores.value.length == 0) {
    await buscarListagemVendedores();
  }
  dialogVendedor.value = true;
};

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

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.aplicarValores(
      parseFloat(edicao.value.valordesconto),
      parseFloat(edicao.value.valorfrete),
      parseFloat(edicao.value.valorseguro),
      parseFloat(edicao.value.valoroutras)
    );
    dialogValores.value = false;
  });
};

const salvarPessoa = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.informarPessoa(
      edicaoPessoa.value.codestoquelocal,
      edicaoPessoa.value.codnaturezaoperacao,
      edicaoPessoa.value.codpessoa,
      edicaoPessoa.value.cpf,
      edicaoPessoa.value.observacoes
    );
    dialogPessoa.value = false;
  });
};

const informarVendedor = async (codpessoavendedor) => {
  if (!sNegocio.negocio.codpessoavendedor) {
    sNegocio.informarVendedor(codpessoavendedor);
    dialogVendedor.value = false;
    return;
  }
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja alterar o vendedor?",
    cancel: true,
  }).onOk(() => {
    sNegocio.informarVendedor(codpessoavendedor);
    dialogVendedor.value = false;
  });
};

const recalcularValorProdutos = () => {
  edicao.value.valorprodutos =
    Math.round(edicao.value.quantidade * edicao.value.preco * 100) / 100;
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

const formaPagamentoPadrao = () => {
  Dialog.create({
    title: "Pagamento",
    message: "Adicionar a forma de pagamento padrão do Cliente?",
    cancel: true,
  }).onOk(() => {
    // dialogVendedor.value = false;
  });
};
</script>
<template>
  <template v-if="sNegocio.negocio">
    <!-- Editar Valores Desconto / Frete / etc -->
    <q-dialog v-model="dialogValores">
      <q-card style="width: 350px; max-width: 80vw">
        <q-form ref="formItem" @submit="salvar()">
          <q-card-section>
            <div class="row justify-end q-col-gutter-md">
              <div class="col-6"></div>
              <div class="col-6">
                <q-input
                  disable
                  type="number"
                  step="0.01"
                  min="0.01"
                  outlined
                  v-model.number="edicao.valorprodutos"
                  prefix="R$"
                  label="Total Produtos"
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
                  autofocus
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
              @click="dialogValores = false"
              tabindex="-1"
            />
            <q-btn type="submit" flat label="Salvar" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Editar Pessoa -->
    <q-dialog v-model="dialogPessoa">
      <q-card style="width: 500px; max-width: 80vw">
        <q-form ref="formItem" @submit="salvarPessoa()">
          <q-card-section>
            <div class="row q-gutter-md q-pr-md">
              <div class="col-12">
                <select-estoque-local
                  outlined
                  v-model="edicaoPessoa.codestoquelocal"
                  label="Local de Estoque"
                />
              </div>
              <div class="col-12">
                <select-natureza-operacao
                  outlined
                  v-model="edicaoPessoa.codnaturezaoperacao"
                  label="Natureza de Operacao"
                />
              </div>
              <div class="col-12">
                <select-pessoa
                  ref="selectPessoa"
                  outlined
                  autofocus
                  v-model="edicaoPessoa.codpessoa"
                  label="Pessoa"
                  clearable
                  @clear="edicaoPessoa.codpessoa = 1"
                >
                  <template v-slot:after>
                    X
                    <q-icon
                      name="delete"
                      @click.stop.prevent="model = null"
                      class="cursor-pointer"
                    />
                  </template>
                </select-pessoa>
              </div>
              <div class="col-12">
                <q-input
                  ref="codpessoa"
                  v-if="edicaoPessoa.codpessoa == 1"
                  :rules="[validarCPF]"
                  outlined
                  v-model="edicaoPessoa.cpf"
                  label="CPF"
                  mask="###.###.###-##"
                />
              </div>
              <div class="col-12">
                <q-input
                  outlined
                  autogrow
                  v-model.number="edicaoPessoa.observacoes"
                  label="Observações"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn
              flat
              label="Cancelar"
              color="primary"
              @click="dialogPessoa = false"
              tabindex="-1"
            />
            <q-btn type="submit" flat label="Salvar" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Editar Vendedor -->
    <q-dialog v-model="dialogVendedor">
      <q-card style="width: 500px; max-width: 80vw">
        <q-form ref="formItem" @submit="salvarPessoa()">
          <q-card-section>
            <q-list>
              <q-item clickable v-ripple @click="informarVendedor(null)">
                <q-item-section avatar>
                  <q-avatar
                    color="negative"
                    icon="close"
                    text-color="white"
                  ></q-avatar>
                </q-item-section>
                <q-item-section> Sem Vendedor </q-item-section>
              </q-item>
              <q-item
                v-for="vendedor in vendedores"
                :key="vendedor.codpessoa"
                clickable
                v-ripple
                @click="informarVendedor(vendedor.codpessoa)"
              >
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white">
                    {{ vendedor.fantasia.charAt(0) }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  {{ vendedor.fantasia }}
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- TOTAIS -->
    <q-list dense class="q-mt-md">
      <q-item
        v-if="
          parseFloat(sNegocio.negocio.valorprodutos) -
          parseFloat(sNegocio.negocio.valortotal)
        "
      >
        <q-item-section avatar>
          <q-item-label caption>Produtos</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorprodutos)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valordesconto">
        <q-item-section avatar>
          <q-item-label caption>Desconto</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-weight-bolder text-green-8">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valordesconto)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorfrete">
        <q-item-section avatar>
          <q-item-label caption>Frete</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorfrete)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorseguro">
        <q-item-section avatar>
          <q-item-label caption>Seguro</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorseguro)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valoroutras">
        <q-item-section avatar>
          <q-item-label caption>Outras</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valoroutras)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorjuros">
        <q-item-section avatar>
          <q-item-label caption>Juros</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorjuros)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item @click="editarValores()" v-ripple clickable>
        <q-item-section class="text-right">
          <Transition
            mode="out-in"
            :duration="{ enter: 300, leave: 300 }"
            leave-active-class="animated bounceOut"
            enter-active-class="animated bounceIn"
          >
            <q-item-label
              class="text-h2 text-primary text-weight-bolder"
              :key="sNegocio.negocio.valortotal"
            >
              <small class="text-h5 text-grey">R$ </small>
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(sNegocio.negocio.valortotal)
              }}
            </q-item-label>
          </Transition>
        </q-item-section>
      </q-item>
    </q-list>
    <q-separator spaced inset />

    <q-list>
      <!-- <q-item-label header>Pessoa</q-item-label> -->

      <!-- Filial -->
      <q-item clickable v-ripple @click="editarPessoa()">
        <q-item-section avatar top>
          <q-avatar icon="store" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">
            {{ sNegocio.negocio.estoquelocal }}
          </q-item-label>
          <q-item-label caption>
            {{ moment(sNegocio.negocio.lancamento).format("llll") }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- Natureza -->
      <q-item clickable v-ripple @click="editarPessoa()">
        <q-item-section avatar top>
          <q-avatar icon="work" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">
            {{ sNegocio.negocio.operacao }} -
            {{ sNegocio.negocio.naturezaoperacao }}
          </q-item-label>
          <q-item-label caption>
            {{ sNegocio.negocio.negociostatus }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- PESSOA -->
      <q-item clickable v-ripple @click="editarPessoa()">
        <q-item-section avatar top>
          <q-avatar icon="person" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">
            {{ sNegocio.negocio.fantasia }}
          </q-item-label>
          <q-item-label caption v-if="sNegocio.negocio.cpf">
            {{ formataCpf(sNegocio.negocio.cpf) }}
          </q-item-label>
          <template v-if="sNegocio.negocio.Pessoa">
            <q-item-label caption v-if="sNegocio.negocio.Pessoa.cnpj">
              {{
                formataCnpjCpf(
                  sNegocio.negocio.Pessoa.cnpj,
                  sNegocio.negocio.Pessoa.fisica
                )
              }}
            </q-item-label>
            <q-item-label caption v-if="sNegocio.negocio.Pessoa.endereco">
              {{ sNegocio.negocio.Pessoa.endereco }},
              {{ sNegocio.negocio.Pessoa.numero }} -
              <template v-if="sNegocio.negocio.Pessoa.complemento">
                {{ sNegocio.negocio.Pessoa.complemento }} -
              </template>
              <template v-if="sNegocio.negocio.Pessoa.bairro">
                {{ sNegocio.negocio.Pessoa.bairro }} -
              </template>
              {{ sNegocio.negocio.Pessoa.cidade }} /
              {{ sNegocio.negocio.Pessoa.uf }}
            </q-item-label>
          </template>
        </q-item-section>

        <q-item-section
          side
          v-if="sNegocio.negocio.codpessoa == 1 && !sNegocio.negocio.cpf"
        >
          <q-icon name="info" color="warning" />
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.Pessoa.mensagemvenda">
        <q-item-section>
          <q-banner
            inline-actions
            rounded
            class="bg-orange-8 text-white"
            style="white-space: pre-line"
          >
            {{ sNegocio.negocio.Pessoa.mensagemvenda }}
          </q-banner>
        </q-item-section>
      </q-item>

      <!-- OBSERVACOES -->
      <q-item
        clickable
        v-ripple
        v-if="sNegocio.negocio.observacoes"
        @click="editarPessoa()"
      >
        <q-item-section avatar top>
          <q-avatar icon="notes" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1" style="white-space: pre-line">
            {{ sNegocio.negocio.observacoes }}
          </q-item-label>
          <q-item-label caption>Observações</q-item-label>
        </q-item-section>
      </q-item>

      <q-item
        clickable
        v-ripple
        v-if="sNegocio.negocio.Pessoa.codformapagamento"
        @click="formaPagamentoPadrao()"
      >
        <q-item-section avatar top>
          <q-avatar icon="attach_money" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">
            {{ sNegocio.negocio.Pessoa.formapagamento }}
          </q-item-label>
          <q-item-label caption>Forma de Pagamento Padrão</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator spaced inset />

      <!-- VENDEDOR -->
      <q-item clickable v-ripple @click="editarVendedor()">
        <q-item-section avatar top>
          <q-avatar icon="escalator_warning" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1" v-if="sNegocio.negocio.codpessoavendedor">
            {{ sNegocio.negocio.fantasiavendedor }}
          </q-item-label>
          <q-item-label lines="1" v-else> Não Informado </q-item-label>
          <q-item-label caption>Vendedor</q-item-label>
        </q-item-section>

        <q-item-section side v-if="!sNegocio.negocio.codpessoavendedor">
          <q-icon name="error" color="warning" />
        </q-item-section>
      </q-item>

      <q-separator spaced inset />

      <!-- CODIGOS -->
      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="fingerprint" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1" v-if="sNegocio.negocio.codnegocio">
            {{ sNegocio.negocio.codnegocio.padStart(8, "0") }}
          </q-item-label>
          <q-item-label lines="1" v-else> Não Integrado </q-item-label>
          <q-item-label caption>{{ sNegocio.negocio.id }}</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </template>
</template>
