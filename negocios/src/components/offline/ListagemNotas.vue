<script setup>
import { ref } from "vue";
import { Notify, Platform } from "quasar";
import { api } from "boot/axios";
import { negocioStore } from "stores/negocio";
import { sincronizacaoStore } from "src/stores/sincronizacao";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sSinc = sincronizacaoStore();

const dialogPdf = ref(false);
const urlPdf = ref(null);
const codnotafiscalAberta = ref(null);

const urlNotaFiscal = (codnotafiscal) => {
  return (
    process.env.MGSIS_URL + "index.php?r=notaFiscal/view&id=" + codnotafiscal
  );
};

const formataNumeroNota = (nota) => {
  const prefixo = nota.emitida ? "N" : "T";
  const numero = String(nota.numero).padStart(8, "0");
  return `${prefixo}-${nota.serie}-${nota.modelo}-${numero}`;
};

const status = (nota) => {
  if (!nota.emitida) {
    return "Terceiro";
  }
  if (nota.nfecancelamento) {
    return "Cancelada";
  }
  if (nota.nfecancelamento) {
    return "Inutilizada";
  }
  if (nota.nfeautorizacao) {
    return "Autorizada";
  }
  if (nota.numero) {
    return "Não Autorizada";
  }
  return "Em Digitação";
};

// Route::post(
//   "negocio/{codnegocio}/nota-fiscal",
//   "\Mg\Pdv\PdvController@notaFiscal"
// );

const nova = async (nota) => {
  try {
    // busca registros na ApI
    const { data } = await api.post(
      `/api/v1/pdv/negocio/${sNegocio.negocio.codnegocio}/nota-fiscal`,
      { pdv: sSinc.pdv.uuid }
    );
    Notify.create({
      type: "positive",
      message: "Nota Fiscal Criada!",
    });
    sNegocio.negocio.notas = data.data.notas;
    sNegocio.salvar(false);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
    });
  }
};

const atualizarListagemNotas = (nota) => {
  var i = sNegocio.negocio.notas.findIndex((item) => {
    return item.codnotafiscal == nota.codnotafiscal;
  });
  sNegocio.negocio.notas[i] = nota;
  sNegocio.salvar(false);
};

const enviar = async (nota) => {
  try {
    var retCriar = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/criar`,
      { pdv: sSinc.pdv.uuid }
    );
    Notify.create({
      type: "positive",
      message: "XML da NFe Criado!",
    });
    const retEnviar = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/enviar`,
      { pdv: sSinc.pdv.uuid }
    );
    Notify.create({
      type: "positive",
      message: "NFe Enviada para Sefaz!",
    });
    Notify.create({
      type: "positive",
      message: `${retEnviar.data.respostaSefaz.cStat} - ${retEnviar.data.respostaSefaz.xMotivo}`,
    });
    atualizarListagemNotas(retEnviar.data.nota);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
    });
  }
};

const consultar = async (nota) => {
  try {
    var { data } = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/consultar`,
      { pdv: sSinc.pdv.uuid }
    );
    Notify.create({
      type: "positive",
      message: `${data.respostaSefaz.cStat} - ${data.respostaSefaz.xMotivo}`,
    });
    atualizarListagemNotas(data.nota);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
    });
  }
};

const imprimir = async (codnotafiscal) => {
  if (!codnotafiscal) {
    codnotafiscal = codnotafiscalAberta.value;
  }
  try {
    var { data } = await api.post(
      `/api/v1/pdv/nota-fiscal/${codnotafiscal}/imprimir`,
      { pdv: sSinc.pdv.uuid, impressora: sNegocio.padrao.impressora }
    );
    Notify.create({
      type: "positive",
      message: `Enviado para impressora ${sNegocio.padrao.impressora}!`,
    });
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
    });
  }
};

const montarUrlXml = (nota) => {
  return `${process.env.API_BASE_URL}/api/v1/nfe-php/${nota.codnotafiscal}/xml`;
};

const montarUrlPdf = (nota) => {
  return `${process.env.API_BASE_URL}/api/v1/nfe-php/${nota.codnotafiscal}/danfe`;
};

const abrirPdf = (nota) => {
  urlPdf.value = montarUrlPdf(nota);
  codnotafiscalAberta.value = nota.codnotafiscal;
  if (Platform.is.desktop) {
    dialogPdf.value = true;
    return;
  }
  window.open(urlPdf.value, "_blank").focus();
};
</script>
<template>
  <q-dialog v-model="dialogPdf" full-width>
    <q-card>
      <q-card-section>
        <div class="text-h6">Danfe</div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <iframe :src="urlPdf" style="width: 100%; height: 60vh" />
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="fechar" color="primary" v-close-popup />
        <q-btn
          flat
          label="imprimir"
          color="primary"
          v-close-popup
          @click="imprimir()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <q-item-label header v-if="sNegocio.negocio.codnegociostatus == 2">
    Notas Fiscais
    <q-btn flat color="primary" @click="nova()" icon="add" size="md" dense />
  </q-item-label>
  <div
    class="row q-col-gutter-md q-px-md"
    v-if="sNegocio.negocio.notas.length > 0"
  >
    <div
      class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
      v-for="nota in sNegocio.negocio.notas"
      :key="nota.codnotafiscal"
    >
      <q-card class="my-card">
        <q-item
          clickable
          v-ripple
          :href="urlNotaFiscal(nota.codnotafiscal)"
          target="_blank"
        >
          <q-item-section avatar>
            <q-avatar
              icon="mdi-file-document"
              color="primary"
              text-color="white"
              v-if="nota.emitida == false"
            />
            <q-avatar
              icon="mdi-file-document-remove"
              color="red"
              text-color="white"
              v-else-if="nota.nfecancelamento || nota.nfeinutilizacao"
            />
            <q-avatar
              icon="mdi-file-document-check"
              color="green"
              text-color="white"
              v-else-if="nota.nfeautorizacao"
            />
            <q-avatar
              icon="mdi-file-document-alert"
              color="orange"
              text-color="white"
              v-else-if="nota.numero"
            />
            <q-avatar
              icon="mdi-file-document-edit"
              color="grey"
              text-color="white"
              v-else
            />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ formataNumeroNota(nota) }}
            </q-item-label>
            <q-item-label caption class="ellipsis">
              #{{ String(nota.codnotafiscal).padStart(8, "0") }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ nota.filial }}
            </q-item-label>
            <q-item-label caption>
              {{ moment(nota.saida).fromNow() }},
              {{ moment(nota.saida).format("LLLL") }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ nota.fantasia }}
            </q-item-label>
            <q-item-label caption class="ellipsis">
              {{ nota.naturezaoperacao }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis">
              R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(nota.valortotal)
              }}
            </q-item-label>
            <q-item-label caption class="ellipsis">
              {{ status(nota) }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-card-actions v-if="nota.emitida">
          <q-btn
            round
            icon="mdi-send"
            color="primary"
            @click="enviar(nota)"
            v-if="!nota.nfeautorizacao"
          >
            <q-tooltip class="bg-accent">Enviar</q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-cancel"
            color="negative"
            v-if="
              !nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent">Inutilizar</q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-file-pdf-box"
            color="primary"
            @click="abrirPdf(nota)"
            v-if="
              nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent">Danfe</q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-file-xml-box"
            color="primary"
            :href="montarUrlXml(nota)"
            target="_blank"
            v-if="nota.numero"
          >
            <q-tooltip class="bg-accent">Arquivo XML da NFe</q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-gmail"
            color="primary"
            v-if="
              nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent">Email</q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-cancel"
            color="negative"
            v-if="
              nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent"> Cancelar </q-tooltip>
          </q-btn>
          <q-btn
            round
            icon="mdi-cloud-refresh-outline"
            color="primary"
            @click="consultar(nota)"
          >
            <q-tooltip class="bg-accent">Consultar</q-tooltip>
          </q-btn>
        </q-card-actions>
      </q-card>
    </div>
  </div>
</template>
