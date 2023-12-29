<script setup>
import { ref, computed } from "vue";
import { Dialog } from "quasar";
import { negocioStore } from "stores/negocio";
import PagamentoDinheiro from "components/offline/PagamentoDinheiro.vue";

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
  sNegocio.dialog.valores = true;
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
    sNegocio.dialog.valores = false;
  });
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

const dialogPagamentoDinheiro = () => {
  sNegocio.dialog.pagamentoDinheiro = true;
};

const excluirPagamento = (pag) => {
  sNegocio.excluirPagamento(pag.uuid);
};

const valorSaldoLabel = computed(() => {
  return sNegocio.valorapagar > 0 ? "Faltando" : "Troco";
});

const valorSaldoClass = computed(() => {
  return sNegocio.valorapagar > 0 ? "text-red" : "text-green";
});
</script>
<template>
  <!-- Editar Valores Desconto / Frete / etc -->
  <q-dialog v-model="sNegocio.dialog.valores">
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
            @click="sNegocio.dialog.valores = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <pagamento-dinheiro />
  <q-list dense class="q-mt-md" v-if="sNegocio.negocio">
    <q-item
      v-if="
        parseFloat(sNegocio.negocio.valorprodutos) -
        parseFloat(sNegocio.negocio.valortotal)
      "
    >
      <q-item-section>
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
      <q-item-section>
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
      <q-item-section>
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
      <q-item-section>
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
      <q-item-section>
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
      <q-item-section>
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

    <q-item @click="editarValores()" v-ripple :clickable="sNegocio.podeEditar">
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

    <template v-if="sNegocio.negocio.pagamentos">
      <template v-for="pag in sNegocio.negocio.pagamentos" :key="pag.uuid">
        <q-item>
          <q-item-section>
            <q-item-label caption>{{ pag.formapagamento }}</q-item-label>
          </q-item-section>
          <q-item-section class="text-right">
            <q-item-label class="text-h5 text-grey-6">
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(pag.valorpagamento)
              }}
              <q-btn
                flat
                round
                @click="excluirPagamento(pag)"
                icon="delete"
                size="sm"
              />
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
      <!-- <q-item v-if="1 == 1"> -->
      <q-item v-if="sNegocio.negocio.pagamentos.length > 0">
        <q-item-section>
          <q-item-label caption>{{ valorSaldoLabel }}</q-item-label>
        </q-item-section>
        <q-item-section>
          <q-item-label :class="valorSaldoClass" class="text-right text-h5">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(Math.abs(sNegocio.valorapagar))
            }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-list>
  <q-list class="q-pa-md q-gutter-sm text-right">
    <q-btn
      round
      @click="dialogPagamentoDinheiro()"
      icon="local_atm"
      color="primary"
    >
      <q-tooltip class="bg-accent">Dinheiro</q-tooltip>
    </q-btn>
    <q-btn round icon="credit_card" color="primary">
      <q-tooltip class="bg-accent">Cartão</q-tooltip>
    </q-btn>
    <q-btn round icon="pix" color="primary">
      <q-tooltip class="bg-accent">PIX</q-tooltip>
    </q-btn>
  </q-list>
  <!--
  <pre v-if="sNegocio.negocio">
    {{ sNegocio.negocio.pagamentos }}
  </pre>
  -->
</template>
