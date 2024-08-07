<script setup>
import { ref } from "vue";
import { Dialog, Notify, Platform } from "quasar";
import { api } from "boot/axios";
import { negocioStore } from "stores/negocio";
import { sincronizacaoStore } from "src/stores/sincronizacao";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sSinc = sincronizacaoStore();

const dialogPdf = ref(false);
const urlPdf = ref(null);
const btnImprimir = ref(false);
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

const nova = async (modelo) => {
  try {
    // busca registros na ApI
    const { data } = await api.post(
      `/api/v1/pdv/negocio/${sNegocio.negocio.codnegocio}/nota-fiscal`,
      { pdv: sSinc.pdv.uuid, modelo }
    );
    Notify.create({
      type: "positive",
      message: "Nota Fiscal Criada!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
    sNegocio.negocio.notas = data.data.notas;
    sNegocio.salvar(false);
    return data.data.notas.slice(-1)[0];
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
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
    // cria o XML
    var ret = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/criar`,
      { pdv: sSinc.pdv.uuid }
    );

    // pega nota do retorno
    nota = ret.data.data;
    Notify.create({
      type: "positive",
      message: "XML da NFe Criado!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });

    // se offline mostra o PDF
    if (offline(nota)) {
      abrirPdf(nota);
      if (nota.modelo == 65) {
        imprimir(nota.codnotafiscal);
      }
      atualizarListagemNotas(nota);
      return;
    }

    // enviar a NFe
    ret = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/enviar`,
      { pdv: sSinc.pdv.uuid }
    );
    Notify.create({
      type: "positive",
      message: "NFe Enviada para Sefaz!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
    Notify.create({
      type: "positive",
      message: `${ret.data.respostaSefaz.cStat} - ${ret.data.respostaSefaz.xMotivo}`,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    nota = ret.data.nota;

    // se tem autorizacao
    if (nota.nfeautorizacao) {
      mail(nota, null);
      abrirPdf(nota);
      if (nota.modelo == 65) {
        imprimir(nota.codnotafiscal);
      }
    }

    // atualiza litsagem de notas
    atualizarListagemNotas(nota);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
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
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    atualizarListagemNotas(data.nota);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

const excluir = async (nota) => {
  try {
    console.log(sSinc.pdv.uuid);
    await api.delete(`/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}`, {
      params: { pdv: sSinc.pdv.uuid },
    });
    Notify.create({
      type: "positive",
      message: "Nota Fiscal Exlcuída!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
    var i = sNegocio.negocio.notas.findIndex((item) => {
      return item.codnotafiscal == nota.codnotafiscal;
    });
    sNegocio.negocio.notas.splice(i, 1);
    sNegocio.salvar(false);
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundo
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

const cancelar = (nota) => {
  Dialog.create({
    title: "Justificativa de Cancelamento",
    message:
      "Esse texto será enviado à SEFAZ justificando o cancelamento. Precisa conter no mínimo 15 caracteres!",
    prompt: {
      model: "",
      isValid: (val) => val.length > 14,
      outlined: true,
      type: "text", // optional
      placeholder: "Justificativa de Cancelamento...",
    },
    cancel: true,
  }).onOk(async (justificativa) => {
    try {
      var { data } = await api.post(
        `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/cancelar`,
        {
          pdv: sSinc.pdv.uuid,
          justificativa: justificativa,
        }
      );
      Notify.create({
        type: "positive",
        message: `${data.respostaSefaz.cStat} - ${data.respostaSefaz.xMotivo}`,
        timeout: 1000, // 1 segundo
        actions: [{ icon: "close", color: "white" }],
      });
      atualizarListagemNotas(data.nota);
    } catch (error) {
      console.log(error);
      Notify.create({
        type: "negative",
        message: error.response.data.message,
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
    }
  });
};

const inutilizar = (nota) => {
  Dialog.create({
    title: "Justificativa de Inutilização",
    message:
      "Esse texto será enviado à SEFAZ justificando a inutilização. Precisa conter no mínimo 15 caracteres!",
    prompt: {
      model: "",
      isValid: (val) => val.length > 14,
      outlined: true,
      type: "text", // optional
      placeholder: "Justificativa de Inutilização...",
    },
    cancel: true,
  }).onOk(async (justificativa) => {
    try {
      var { data } = await api.post(
        `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/inutilizar`,
        {
          pdv: sSinc.pdv.uuid,
          justificativa: justificativa,
        }
      );
      Notify.create({
        type: "positive",
        message: `${data.respostaSefaz.cStat} - ${data.respostaSefaz.xMotivo}`,
        timeout: 1000, // 1 segundo
        actions: [{ icon: "close", color: "white" }],
      });
      atualizarListagemNotas(data.nota);
    } catch (error) {
      console.log(error);
      Notify.create({
        type: "negative",
        message: error.response.data.message,
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
    }
  });
};

const imprimir = async (codnotafiscal) => {
  if (!codnotafiscal) {
    codnotafiscal = codnotafiscalAberta.value;
  }
  if (!sNegocio.padrao.impressora) {
    Notify.create({
      type: "negative",
      message: "Nenhuma impressora termica selecionada!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return;
  }
  try {
    var { data } = await api.post(
      `/api/v1/pdv/nota-fiscal/${codnotafiscal}/imprimir`,
      { pdv: sSinc.pdv.uuid, impressora: sNegocio.padrao.impressora }
    );
    Notify.create({
      type: "positive",
      message: `Enviado para impressora ${sNegocio.padrao.impressora}!`,
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

const mail = async (nota, destinatario) => {
  try {
    Notify.create({
      type: "positive",
      message: "Envio de e-mail solicitado ao servidor!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
    var { data } = await api.post(
      `/api/v1/pdv/nota-fiscal/${nota.codnotafiscal}/mail`,
      {
        pdv: sSinc.pdv.uuid,
        destinatario: destinatario,
      }
    );
    Notify.create({
      type: data.sucesso ? "positive" : "negative",
      message: `${data.mensagem}`,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  } catch (error) {
    console.log(error);
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

const perguntarDestinatarioMail = (nota) => {
  Dialog.create({
    title: "Enviar NFe por e-mail",
    message:
      "Informe o endereço para envio, ou deixe em branco para enviar parar os endereços informados no cadastro! Caso deseje enviar para mais de um endereço, separe-os por vírgula!",
    prompt: {
      model: "",
      //isValid: (val) => val.length > 14,
      outlined: true,
      type: "email", // optional
      placeholder: "fulano@gmail.com,beltrano@hotmail.com",
    },
    cancel: true,
  }).onOk((destinatario) => {
    mail(nota, destinatario);
  });
};

const offline = (nota) => {
  if (!nota.nfechave) {
    return false;
  }
  return nota.nfechave.substring(34, 35) == "9" && nota.modelo == 65;
};

const montarUrlXml = (nota) => {
  return `${process.env.API_BASE_URL}/api/v1/nfe-php/${nota.codnotafiscal}/xml`;
};

const montarUrlPdf = (nota) => {
  return `${process.env.API_BASE_URL}/api/v1/nfe-php/${nota.codnotafiscal}/danfe`;
};

const abrirPdf = (nota) => {
  urlPdf.value = montarUrlPdf(nota);
  btnImprimir.value = nota.modelo == 65 ? true : false;
  codnotafiscalAberta.value = nota.codnotafiscal;
  if (Platform.is.desktop) {
    dialogPdf.value = true;
    return;
  }
  window.open(urlPdf.value, "_blank").focus();
};

defineExpose({
  nova,
  enviar,
});
</script>
<template>
  <q-dialog v-model="dialogPdf" full-width>
    <q-card>
      <q-card-section>
        <iframe :src="urlPdf" style="width: 100%; height: 80vh" />
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
          v-if="btnImprimir"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <div
    class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-2"
    v-for="nota in sNegocio.negocio.notas"
    :key="nota.codnotafiscal"
  >
    <q-card>
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
            color="negative"
            text-color="white"
            v-else-if="nota.nfecancelamento || nota.nfeinutilizacao"
          />
          <q-avatar
            icon="mdi-file-document-check"
            color="secondary"
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
            <template v-if="nota.modelo == 55"> Nota Fiscal</template>
            <template v-else-if="nota.modelo == 65"> Cupom</template>
            <template v-else> Documento</template>
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataNumeroNota(nota) }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            #{{ String(nota.codnotafiscal).padStart(8, "0") }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

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
        <q-btn-group flat>
          <!-- ENVIAR -->
          <q-btn
            dense
            flat
            round
            icon="mdi-send"
            color="primary"
            @click="enviar(nota)"
            v-if="!nota.nfeautorizacao && !nota.nfeinutilizacao"
          >
            <q-tooltip class="bg-accent">Enviar</q-tooltip>
          </q-btn>

          <!-- INUTILIZAR -->
          <q-btn
            dense
            flat
            round
            icon="mdi-cancel"
            color="negative"
            @click="inutilizar(nota)"
            v-if="
              nota.numero &&
              !nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent">Inutilizar</q-tooltip>
          </q-btn>

          <!-- DANFE -->
          <q-btn
            dense
            flat
            round
            icon="mdi-file-pdf-box"
            color="primary"
            @click="abrirPdf(nota)"
            v-if="
              offline(nota) ||
              (nota.nfeautorizacao &&
                !nota.nfeinutilizacao &&
                !nota.nfecancelamento)
            "
          >
            <q-tooltip class="bg-accent">Danfe</q-tooltip>
          </q-btn>

          <!-- XML -->
          <q-btn
            dense
            flat
            round
            icon="mdi-file-xml-box"
            color="primary"
            :href="montarUrlXml(nota)"
            target="_blank"
            v-if="nota.numero"
          >
            <q-tooltip class="bg-accent">Arquivo XML da NFe</q-tooltip>
          </q-btn>

          <!-- EMAIL -->
          <q-btn
            dense
            flat
            round
            icon="mdi-gmail"
            color="primary"
            @click="perguntarDestinatarioMail(nota)"
            v-if="
              nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent">E-mail</q-tooltip>
          </q-btn>

          <!-- CANCELAR -->
          <q-btn
            dense
            flat
            round
            icon="mdi-cancel"
            color="negative"
            @click="cancelar(nota)"
            v-if="
              nota.nfeautorizacao &&
              !nota.nfeinutilizacao &&
              !nota.nfecancelamento
            "
          >
            <q-tooltip class="bg-accent"> Cancelar </q-tooltip>
          </q-btn>

          <!-- CONSULTAR -->
          <q-btn
            dense
            flat
            round
            icon="mdi-cloud-refresh-outline"
            color="primary"
            @click="consultar(nota)"
            v-if="nota.nfechave"
          >
            <q-tooltip class="bg-accent">Consultar</q-tooltip>
          </q-btn>

          <!-- EXCLUIR -->
          <q-btn
            dense
            flat
            round
            icon="delete"
            color="negative"
            @click="excluir(nota)"
            v-if="!nota.numero"
          >
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>
</template>
