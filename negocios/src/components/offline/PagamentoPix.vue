<script setup>
import { ref } from "vue";
import { Notify } from "quasar";
import { negocioStore } from "stores/negocio";
import { pixStore } from "stores/pix";
import { formataCpf } from "../../utils/formatador.js";
import { formataCnpj } from "../../utils/formatador.js";
import emitter from "../../utils/emitter.js";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sPix = pixStore();

const valorPagamento = ref(null);
const formPix = ref(null);

const inicializarValores = () => {
  valorPagamento.value = sNegocio.valorapagar;
};

const valorRule = [
  (value) => {
    if (!value) {
      return "Preencha o valor!";
    }
    if (parseFloat(value) <= 0.01) {
      return "Preencha o valor!";
    }
    if (parseFloat(value) > sNegocio.valorapagar) {
      return "Valor maior que o saldo do Negócio!";
    }
    return true;
  },
];

const salvar = async () => {
  const pixCob = await sNegocio.criarPixCob(valorPagamento.value);
  if (pixCob == false) {
    return;
  }
  sPix.pixCob = pixCob;
  sPix.dialog.detalhesPixCob = true;
  sNegocio.dialog.pagamentoPix = false;
};

const pixChave = async () => {
  const valido = await formPix.value.validate();
  if (!valido) {
    return false;
  }
  sNegocio.dialog.pagamentoPix = false;
  sNegocio.adicionarPagamento(
    parseInt(process.env.CODFORMAPAGAMENTO_PIXCHAVE), // codformapagamento Pix
    16, // tipo Deposito Bancario
    null, // codtitulo
    valorPagamento.value, // valorpagamento
    null, // valorjuros
    null, // valortroco
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    1, // parcelas
    valorPagamento.value // valorparcela
  );
};

const transmitir = () => {
  sPix.transmitirPixCob();
};

const transmitirSeNovo = () => {
  if (sPix.pixCob.qrcode != null) {
    return;
  }
  sPix.transmitirPixCob();
};

const consultar = async () => {
  await sPix.consultarPixCob();
  if (sPix.pixCob.status == "CONCLUIDA") {
    sPix.dialog.detalhesPixCob = false;
    emitter.emit("pagamentoAdicionado");
  }
};

const imprimir = () => {
  sPix.imprimirPixCob();
};

const textoMensagem = () => {
  var mensagem = "Olá,\n\n";
  mensagem +=
    "Você está recebendo um link para pagamento via PIX de sua compra na *MG Papelaria* no valor de R$ *" +
    sPix.pixCob.valororiginal.toLocaleString("pt-br", {
      minimumFractionDigits: 2,
    }) +
    "*!\n\n";
  mensagem +=
    "Abra https://pix.mgpapelaria.com.br/" +
    sPix.pixCob.codpixcob +
    " e siga as instruções:\n\n";
  mensagem += "*Obrigado* pela confiança!";
  return mensagem;
};
const mensagem = () => {
  const mensagem = textoMensagem();
  navigator.clipboard.writeText(mensagem).then(() => {
    Notify.create({
      type: "positive",
      message: "Mensagem copiada para a área de transferência!",
    });
  });
};

const whatsapp = () => {
  const mensagem = textoMensagem();
  window.open("whatsapp://send?text=" + encodeURI(mensagem));
};
</script>
<template>
  <!-- DIALOG -->
  <q-dialog
    v-model="sNegocio.dialog.pagamentoPix"
    @before-show="inicializarValores()"
  >
    <q-card>
      <q-form @submit="salvar()" ref="formPix">
        <q-card-section>
          <q-list>
            <q-item>
              <q-item-section>
                <q-input
                  prefix="R$"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :max="sNegocio.valorapagar"
                  borderless
                  v-model.number="valorPagamento"
                  :rules="valorRule"
                  autofocus
                  input-class="text-h2 text-weight-bolder text-right text-primary"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="sNegocio.dialog.pagamentoPix = false"
            tabindex="-1"
          />
          <q-btn
            type="button"
            flat
            label="PIX Pela Chave (Manual)"
            @click="pixChave()"
            color="primary"
          />
          <q-btn
            type="submit"
            flat
            label="PIX Automático (QR CODE)"
            color="primary"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DETALHES DO PIX -->
  <q-dialog v-model="sPix.dialog.detalhesPixCob" @show="transmitirSeNovo()">
    <q-card>
      <q-card-section>
        <div class="text-h6">
          Cobrança PIX de R$
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(sPix.pixCob.valororiginal)
          }}
        </div>
        <div class="text-subtitle2 text-grey">
          {{ sPix.pixCob.status }}
        </div>

        <template v-if="sPix.pixCob.status == 'CONCLUIDA'">
          <template v-for="pix in sPix.pixCob.PixS" :key="pix.codpix">
            <q-list>
              <q-separator spaced />

              <!-- VALOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(pix.valor)
                    }}
                  </q-item-label>
                  <q-item-label caption> Valor efetivamente Pago </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- NOME -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="person" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ pix.nome }}
                  </q-item-label>
                  <q-item-label caption>
                    <template v-if="pix.cpf">
                      {{ formataCpf(pix.cpf) }}
                    </template>
                    <template v-if="pix.cnpj">
                      {{ formataCnpj(pix.cnpj) }}
                    </template>
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- ID -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="fingerprint" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="ellipsis">
                    {{ pix.e2eid }}
                  </q-item-label>
                  <q-item-label caption class="ellipsis">
                    {{ pix.txid }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- PORTADOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="place" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ sPix.pixCob.portador }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ moment(pix.horario).format("LLLL") }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </template>
        </template>
        <template v-else>
          <q-img
            v-if="sPix.pixCob.qrcode"
            class="q-my-lg"
            :src="
              'https://api.qrserver.com/v1/create-qr-code/?size=513x513&data=' +
              sPix.pixCob.qrcode
            "
            ratio="1"
          />
          <q-list>
            <!-- ID -->
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="fingerprint" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  {{ sPix.pixCob.qrcode }}
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  {{ sPix.pixCob.txid }}
                </q-item-label>
              </q-item-section>
            </q-item>

            <div class="gt-xs">
              <q-separator spaced />

              <!-- PORTADOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="place" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ sPix.pixCob.portador }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ moment(sPix.pixCob.criacao).format("LLLL") }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
          </q-list>
        </template>
      </q-card-section>
      <q-card-actions align="right">
        <template v-if="sPix.pixCob.status != 'CONCLUIDA'">
          <q-btn
            flat
            label="transmitir"
            color="primary"
            @click="transmitir()"
            tabindex="-1"
          />
          <q-btn
            flat
            label="whatsapp"
            color="primary"
            @click="whatsapp()"
            tabindex="-1"
            class="mobile-only"
          />
          <q-btn
            flat
            label="Mensagem"
            color="primary"
            @click="mensagem()"
            tabindex="-1"
            class="desktop-only"
          />
          <q-btn
            flat
            label="imprimir"
            color="primary"
            @click="imprimir()"
            tabindex="-1"
          />
        </template>
        <q-btn
          flat
          label="consultar"
          color="primary"
          @click="consultar()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sPix.dialog.detalhesPixCob = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
