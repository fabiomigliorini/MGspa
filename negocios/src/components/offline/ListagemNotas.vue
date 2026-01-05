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

const STATUS_OPTIONS = [
  { label: "Lançada", value: "LAN", icon: "description", color: "blue-grey" },
  { label: "Em Digitação", value: "DIG", icon: "edit_note", color: "blue" },
  {
    label: "Não Autorizada",
    value: "ERR",
    icon: "error",
    color: "deep-orange",
  },
  {
    label: "Autorizada",
    value: "AUT",
    icon: "check_circle",
    color: "positive",
  },
  {
    label: "Cancelada",
    value: "CAN",
    icon: "highlight_off",
    color: "negative",
  },
  { label: "Inutilizada", value: "INU", icon: "block", color: "warning" },
  { label: "Denegada", value: "DEN", icon: "report", color: "negative" },
];

const getStatusOption = (status) => {
  return (
    STATUS_OPTIONS.find((opt) => opt.value === status) || STATUS_OPTIONS[0]
  );
};

// Funções para controlar visibilidade dos botões
const podeEnviar = (nota) => {
  return nota && ["DIG", "ERR"].includes(nota.status);
};

const podeConsultar = (nota) => {
  return nota && ["AUT", "CAN", "ERR"].includes(nota.status);
};

const podeCancelar = (nota) => {
  return nota && nota.status === "AUT";
};

const podeInutilizar = (nota) => {
  return nota && nota.status === "ERR";
};

const podeEnviarEmail = (nota) => {
  return nota && nota.status === "AUT";
};

const podeAbrirDanfe = (nota) => {
  return nota && ["AUT", "CAN"].includes(nota.status);
};

const podeAbrirXml = (nota) => {
  return nota && nota.emitida && nota.nfechave;
};

const podeExcluir = (nota) => {
  return nota && nota.status === "DIG";
};

const dialogPdf = ref(false);
const urlPdf = ref(null);
const btnImprimir = ref(false);
const codnotafiscalAberta = ref(null);

const urlNotaFiscal = (codnotafiscal) => {
  return process.env.NOTAS_URL + "/nota/" + codnotafiscal;
};

const formataNumeroNota = (nota) => {
  const prefixo = nota.emitida ? "N" : "T";
  const numero = String(nota.numero).padStart(8, "0");
  return `${prefixo}-${nota.serie}-${nota.modelo}-${numero}`;
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
    class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-3"
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
            :icon="getStatusOption(nota.status).icon"
            :color="getStatusOption(nota.status).color"
            text-color="white"
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
            <q-icon
              :name="getStatusOption(nota.status).icon"
              :color="getStatusOption(nota.status).color"
              size="xs"
              class="q-mr-xs"
            />
            {{ getStatusOption(nota.status).label }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-card-actions align="right" v-if="nota.emitida">
        <!-- Enviar -->
        <q-btn
          dense
          round
          flat
          color="positive"
          icon="send"
          @click="enviar(nota)"
          v-if="podeEnviar(nota)"
        >
          <q-tooltip>Criar XML e enviar para SEFAZ</q-tooltip>
        </q-btn>

        <!-- Consultar -->
        <q-btn
          dense
          round
          flat
          color="primary"
          icon="refresh"
          @click="consultar(nota)"
          v-if="podeConsultar(nota)"
        >
          <q-tooltip>Consultar situação na SEFAZ</q-tooltip>
        </q-btn>

        <!-- Abrir DANFE -->
        <q-btn
          dense
          round
          flat
          color="positive"
          icon="picture_as_pdf"
          @click="abrirPdf(nota)"
          v-if="podeAbrirDanfe(nota)"
        >
          <q-tooltip>Abrir DANFE</q-tooltip>
        </q-btn>

        <!-- Abrir XML -->
        <q-btn
          dense
          round
          flat
          color="orange"
          icon="code"
          :href="montarUrlXml(nota)"
          target="_blank"
          v-if="podeAbrirXml(nota)"
        >
          <q-tooltip>Abrir XML</q-tooltip>
        </q-btn>

        <!-- Enviar Email -->
        <q-btn
          dense
          round
          flat
          color="primary"
          icon="email"
          @click="perguntarDestinatarioMail(nota)"
          v-if="podeEnviarEmail(nota)"
        >
          <q-tooltip>Enviar por email</q-tooltip>
        </q-btn>

        <!-- Cancelar -->
        <q-btn
          dense
          round
          flat
          color="negative"
          icon="cancel"
          @click="cancelar(nota)"
          v-if="podeCancelar(nota)"
        >
          <q-tooltip>Cancelar NFe</q-tooltip>
        </q-btn>

        <!-- Inutilizar -->
        <q-btn
          dense
          round
          flat
          color="warning"
          icon="block"
          @click="inutilizar(nota)"
          v-if="podeInutilizar(nota)"
        >
          <q-tooltip>Inutilizar NFe</q-tooltip>
        </q-btn>

        <!-- Excluir -->
        <q-btn
          dense
          round
          flat
          color="negative"
          icon="delete"
          @click="excluir(nota)"
          v-if="podeExcluir(nota)"
        >
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </q-card-actions>
    </q-card>
  </div>
</template>
