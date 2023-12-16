<script setup>
import { ref } from "vue";
import { negocioStore } from "stores/negocio";
import { Dialog } from "quasar";
import ComputerSettings from "components/ComputerSettings.vue";

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

const dialogValores = ref(false);

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
</script>
<template>
  <template v-if="sNegocio.negocio">
    <!-- Editar Item -->
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
    <q-list dense class="q-mt-xs">
      <q-item-label header>
        Valores
        <q-btn
          flat
          label="F3"
          color="primary"
          @click="editarValores()"
          icon="add"
          size="md"
          dense
        />
      </q-item-label>
      <q-item
        v-if="sNegocio.negocio.valorprodutos != sNegocio.negocio.valortotal"
      >
        <q-item-section avatar>
          <q-item-label caption>Produtos</q-item-label>
        </q-item-section>
        <q-item-section class="text-right text-grey-7">
          <q-item-label class="text-h6">
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
        <q-item-section class="text-right text-green">
          <q-item-label class="text-h6">
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
        <q-item-section class="text-right text-grey-7">
          <q-item-label class="text-h6">
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
        <q-item-section class="text-right text-grey-7">
          <q-item-label class="text-h6">
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
        <q-item-section class="text-right text-grey-7">
          <q-item-label class="text-h6">
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
        <q-item-section class="text-right text-grey-7">
          <q-item-label class="text-h6">
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

      <q-item>
        <q-item-section avatar>
          <q-item-label caption>Total </q-item-label>
        </q-item-section>
        <q-item-section class="text-right text-primary">
          <Transition
            mode="out-in"
            :duration="{ enter: 300, leave: 300 }"
            leave-active-class="animated bounceOut"
            enter-active-class="animated bounceIn"
          >
            <q-item-label class="text-h3" :key="sNegocio.negocio.valortotal">
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
    <q-separator spaced />
    <q-list>
      <q-item-label header>Detalhes</q-item-label>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="fingerprint" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">#03386672</q-item-label>
          <q-item-label caption>{{ sNegocio.negocio.id }}</q-item-label>
        </q-item-section>
      </q-item>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="work" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">Saída - Venda</q-item-label>
          <q-item-label caption>Aberto</q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-icon name="error" color="warning" />
        </q-item-section>
      </q-item>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="store" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">Botanico</q-item-label>
          <q-item-label caption>February 22, 2019 18:15:33</q-item-label>
          <q-item-label caption>fabio</q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-icon name="error" color="warning" />
        </q-item-section>
      </q-item>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="escalator_warning" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">Não Informado</q-item-label>
          <q-item-label caption>Vendedor</q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-icon name="error" color="warning" />
        </q-item-section>
      </q-item>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="person" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">Consumidor</q-item-label>
          <q-item-label caption>04.576.775/0001-60</q-item-label>
          <q-item-label caption
            >Rua das Paineiras, 995, Jardim Imperial, Sinop/MT</q-item-label
          >
        </q-item-section>

        <q-item-section side>
          <q-icon name="info" color="warning" />
        </q-item-section>
      </q-item>

      <q-item clickable v-ripple>
        <q-item-section avatar top>
          <q-avatar icon="notes" color="grey" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label lines="1">Singing it all day</q-item-label>
          <q-item-label caption>Observações</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </template>
</template>

<style lang="sass">
.my-content
  padding: 10px 15px
  background: rgba(#999,.15)
  border: 1px solid rgba(#999,.2)
</style>
