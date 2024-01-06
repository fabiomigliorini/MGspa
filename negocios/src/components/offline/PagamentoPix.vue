<script setup>
import { ref, computed } from "vue";
import { negocioStore } from "stores/negocio";
import { pixStore } from "stores/pix";
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
    valorPagamento.value,
    null, // valorjuros
    null, // valor
    false, // integracao
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    null, // codpixcob
    null // codpagarmepedido
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
  }
};

const mensagem = () => {};
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
              <q-item-section side class="text-h5 text-grey">
                R$
              </q-item-section>
              <q-item-section>
                <q-item-label
                  class="text-h2 text-primary text-weight-bolder text-right"
                >
                  <q-input
                    type="number"
                    step="0.01"
                    min="0.01"
                    :max="sNegocio.valorapagar"
                    borderless
                    v-model.number="valorPagamento"
                    :rules="valorRule"
                    autofocus
                    input-class="text-h2 text-weight-bolder text-right text-primary"
                  >
                    <template v-slot:error>
                      <div class="text-right">Valor inválido!</div>
                    </template>
                  </q-input>
                </q-item-label>
                <q-item-label caption class="text-right">
                  valor do PIX
                </q-item-label>
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
        <h4>
          R$
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(sPix.pixCob.valororiginal)
          }}
          <small class="text-grey">
            {{ sPix.pixCob.status }}
          </small>
        </h4>
        <q-img
          v-if="sPix.pixCob.qrcode"
          :src="
            'https://api.qrserver.com/v1/create-qr-code/?size=513x513&data=' +
            sPix.pixCob.qrcode
          "
        />
        <!-- <pre>{{ sPix.pixCob }}</pre> -->
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          flat
          label="Mensagem"
          color="primary"
          @click="mensagem()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="transmitir"
          color="primary"
          @click="transmitir()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="consultar"
          color="primary"
          @click="consultar()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="imprimir"
          color="primary"
          @click="imprimir()"
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
