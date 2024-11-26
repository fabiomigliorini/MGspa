<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { negocioStore } from "stores/negocio";
import { debounce, Notify } from "quasar";
import emitter from "../../utils/emitter.js";
const sNegocio = negocioStore();

const valorPagamento = ref(null);
const codtituloVale = ref(null);
const codtituloValeBipado = ref(null);
const titulo = ref(null);
const refDialogVale = ref(null);

const valorSaldo = computed(() => {
  return sNegocio.valorapagar - valorPagamento.value;
});

const inicializarValores = () => {
  valorPagamento.value = null;
  codtituloVale.value = null;
  titulo.value = null;
  if (codtituloValeBipado.value) {
    codtituloVale.value = codtituloValeBipado.value;
    codtituloValeBipado.value = null;
    buscarVale();
  }
};

const buscarVale = debounce(async () => {
  titulo.value = null;
  valorPagamento.value = null;
  if (!codtituloVale.value) {
    return;
  }
  const ja = sNegocio.negocio.pagamentos.filter((i) => {
    return i.codtitulo == codtituloVale.value;
  });
  if (ja.length > 0) {
    Notify.create({
      type: "negative",
      message: "Este vale já foi usado neste negócio!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }
  const ret = await sNegocio.buscarVale(codtituloVale.value);
  if (!ret) {
    return;
  }
  titulo.value = ret.data.data;
  if (sNegocio.valorapagar < titulo.value.creditosaldo) {
    valorPagamento.value = sNegocio.valorapagar;
  } else {
    valorPagamento.value = titulo.value.creditosaldo;
  }
}, 500);

watch(
  () => codtituloVale.value,
  () => {
    buscarVale();
  }
);

const maiorQueZeroRule = [
  (value) => {
    if (parseFloat(value) > 0) {
      return true;
    }
    return false;
  },
];

const valorMax = [
  (value) => {
    if (parseFloat(value) > titulo.value.creditosaldo) {
      return (
        "o valor máximo do vale que pode ser usado é R$ " +
        new Intl.NumberFormat("pt-BR", {
          style: "decimal",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }).format(titulo.value.creditosaldo)
      );
    }
    if (parseFloat(value) > sNegocio.valorapagar) {
      return (
        "o valor máximo do vale que pode ser usado é R$ " +
        new Intl.NumberFormat("pt-BR", {
          style: "decimal",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }).format(sNegocio.valorapagar)
      );
    }
    return true;
  },
];

const salvar = () => {
  var valortroco = null;
  if (valorSaldo.value < 0) {
    valortroco = Math.round(Math.abs(valorSaldo.value * 100)) / 100;
  }
  sNegocio.adicionarPagamento(
    parseInt(process.env.CODFORMAPAGAMENTO_VALE), // codformapagamento Vale
    12, // tipo tPag Vale Presente
    codtituloVale.value,
    valorPagamento.value,
    null, // valorjuros
    valortroco,
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    null, // parcelas
    null // valorparcela
  );
  if (sNegocio.negocio.codpessoa == 1) {
    sNegocio.informarPessoa(
      titulo.value.codpessoa,
      null, //cpf
    );
  }
  sNegocio.dialog.pagamentoVale = false;
};

onMounted(() => {
  emitter.on("valeComprasLido", (codigo) => {
    sNegocio.dialog.pagamentoVale = true;
    codtituloValeBipado.value = codigo;
  });
});

onUnmounted(() => {
  emitter.off("valeComprasLido");
});
</script>
<template>
  <q-dialog v-model="sNegocio.dialog.pagamentoVale" @before-show="inicializarValores()" ref="refDialogVale">
    <q-card>
      <q-form @submit="salvar()">
        <q-card-section style="min-height: 430px">
          <q-list>
            <q-item>
              <q-item-section side class="text-h5 text-grey">
                #Vale
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-h2 text-primary text-weight-bolder text-right">
                  <q-input type="number" borderless v-model.number="codtituloVale" autofocus
                    :rules="[(val) => !!val || 'Preenchimento Obrigatório']"
                    input-class="text-h2 text-weight-bolder text-right text-primary">
                  </q-input>
                </q-item-label>

                <q-item-label caption class="text-right" v-if="titulo">
                  {{ titulo.numero }} |
                  {{ titulo.fantasia }}
                </q-item-label>
                <q-item-label caption class="text-right" v-else>
                  Código do Vale
                </q-item-label>
              </q-item-section>
            </q-item>

            <template v-if="titulo != null">
              <q-item>
                <q-item-section side class="text-h5 text-grey">
                  R$
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-h4 text-secondary text-weight-bolder text-right">
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(titulo.creditosaldo)
                    }}
                  </q-item-label>
                  <q-item-label caption class="text-right">
                    Valor do Vale
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side class="text-h5 text-grey">
                  R$
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-h2 text-primary text-weight-bolder text-right">
                    <q-input type="number" step="0.01" min="0" borderless v-model.number="valorPagamento"
                      :rules="(maiorQueZeroRule, valorMax)"
                      input-class="text-h2 text-weight-bolder text-right text-primary">
                      <template v-slot:error>
                        <div class="text-right">Valor inválido!</div>
                      </template>
                    </q-input>
                  </q-item-label>
                  <q-item-label caption class="text-right">
                    Pagamento
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side class="text-h5 text-grey">
                  R$
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-h4 text-secondary text-weight-bolder text-right">
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(titulo.creditosaldo - valorPagamento)
                    }}
                  </q-item-label>
                  <q-item-label caption class="text-right">
                    Saldo do Vale
                  </q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" @click="sNegocio.dialog.pagamentoVale = false" tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" :disable="!(valorPagamento > 0)" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
