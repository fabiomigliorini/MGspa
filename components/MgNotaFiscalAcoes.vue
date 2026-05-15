<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { abrirPdf } from "@components/abrirPdf";

const props = defineProps({
  nota: { type: Object, required: true },
  api: { type: Function, required: true },
  compact: { type: Boolean, default: false },
  showExtras: { type: Boolean, default: false },
});

const emit = defineEmits(["action-completed"]);

const $q = useQuasar();

const loadingEnviar = ref(false);
const loadingConsultar = ref(false);
const loadingCancelar = ref(false);
const loadingInutilizar = ref(false);
const loadingEmail = ref(false);
const progressoNfe = ref({ status: "", percent: 0 });

const codnotafiscal = computed(() => props.nota?.codnotafiscal);
const podeEnviar = computed(
  () => props.nota?.emitida && ["DIG", "ERR"].includes(props.nota?.status),
);
const podeConsultar = computed(
  () =>
    props.nota?.emitida && ["AUT", "CAN", "ERR"].includes(props.nota?.status),
);
const podeCancelar = computed(
  () => props.nota?.emitida && props.nota?.status === "AUT",
);
const podeInutilizar = computed(
  () => props.nota?.emitida && props.nota?.status === "ERR",
);
const podeEnviarEmail = computed(
  () => props.nota?.emitida && props.nota?.status === "AUT",
);
const podeAbrirDanfe = computed(
  () => props.nota?.emitida && props.nota?.status === "AUT",
);
const podeAbrirXml = computed(
  () => props.nota?.emitida && props.nota?.nfechave,
);
const temAcoes = computed(
  () =>
    podeEnviar.value ||
    podeConsultar.value ||
    podeCancelar.value ||
    podeInutilizar.value,
);

const btnSize = computed(() => (props.compact ? "sm" : undefined));

function stop(event) {
  if (event) {
    event.preventDefault?.();
    event.stopPropagation?.();
  }
}

function mensagemErro(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback;
}

async function xmlUrlFromApi(url) {
  const { data } = await props.api.get(url, { responseType: "blob" });
  return URL.createObjectURL(new Blob([data], { type: "application/xml" }));
}

async function enviarNfe(event) {
  stop(event);
  loadingEnviar.value = true;
  progressoNfe.value = { status: "Criando Arquivo XML...", percent: 0 };
  try {
    const respCriar = await props.api.post(
      `/v1/nota-fiscal/${codnotafiscal.value}/criar`,
    );
    const xml = respCriar.data?.resultado ?? respCriar.data;
    progressoNfe.value = { status: "Arquivo XML Criado...", percent: 25 };

    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(
      typeof xml === "string" ? xml : "",
      "text/xml",
    );
    const tpEmis = xmlDoc.querySelector("tpEmis")?.textContent;

    if (tpEmis === "9") {
      await abrirDanfe();
    } else {
      progressoNfe.value = {
        status: "Enviando NFe para Sefaz...",
        percent: 50,
      };
      const respEnv = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/enviar-sincrono`,
      );
      const envio = respEnv.data?.resultado ?? respEnv.data;
      if (envio?.sucesso) {
        progressoNfe.value = { status: "Enviando Email...", percent: 75 };
        try {
          await props.api.post(
            `/v1/nota-fiscal/${codnotafiscal.value}/mail`,
            {},
          );
        } catch {
          /* email falhou; segue */
        }
        progressoNfe.value = { status: "Finalizado...", percent: 100 };
        $q.notify({ type: "positive", message: "NFe enviada com sucesso!" });
        if (!props.compact) {
          await abrirDanfe();
        }
      } else {
        throw new Error(
          `${envio?.cStat ?? ""} - ${envio?.xMotivo ?? "Erro desconhecido"}`,
        );
      }
    }
    emit("action-completed", "enviar");
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao enviar NFe",
      caption: mensagemErro(error),
    });
  } finally {
    loadingEnviar.value = false;
    progressoNfe.value = { status: "", percent: 0 };
  }
}

async function consultarNfe(event) {
  stop(event);
  loadingConsultar.value = true;
  try {
    const resp = await props.api.post(
      `/v1/nota-fiscal/${codnotafiscal.value}/consultar`,
    );
    const r = resp.data?.resultado ?? resp.data;
    const tipo = r?.sucesso ? "positive" : "negative";
    $q.dialog({
      title: "Consulta NFe",
      message: `${r?.cStat ?? ""} - ${r?.xMotivo ?? ""}`,
      ok: { label: "OK", flat: true, color: tipo },
    });
    emit("action-completed", "consultar");
  } catch (error) {
    $q.notify({ type: "negative", message: mensagemErro(error) });
  } finally {
    loadingConsultar.value = false;
  }
}

function cancelarNfe(event) {
  stop(event);
  $q.dialog({
    title: "Cancelar NFe",
    message: "Digite a justificativa para cancelar a NFe",
    prompt: {
      model: "",
      type: "text",
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: "Cancelar", flat: true },
    ok: { label: "Confirmar Cancelamento", flat: true, color: "negative" },
  }).onOk(async (justificativa) => {
    loadingCancelar.value = true;
    try {
      const resp = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/cancelar`,
        { justificativa },
      );
      const r = resp.data?.resultado ?? resp.data;
      const tipo = r?.sucesso ? "positive" : "negative";
      $q.dialog({
        title: "Cancelamento NFe",
        message: `${r?.cStat ?? ""} - ${r?.xMotivo ?? ""}`,
        ok: { label: "OK", flat: true, color: tipo },
      });
      emit("action-completed", "cancelar");
    } catch (error) {
      $q.notify({ type: "negative", message: mensagemErro(error) });
    } finally {
      loadingCancelar.value = false;
    }
  });
}

function inutilizarNfe(event) {
  stop(event);
  $q.dialog({
    title: "Inutilizar NFe",
    message: "Digite a justificativa para inutilizar a NFe",
    prompt: {
      model: "",
      type: "text",
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: "Cancelar", flat: true },
    ok: { label: "Confirmar Inutilização", flat: true, color: "negative" },
  }).onOk(async (justificativa) => {
    loadingInutilizar.value = true;
    try {
      const resp = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/inutilizar`,
        { justificativa },
      );
      const r = resp.data?.resultado ?? resp.data;
      const tipo = r?.sucesso ? "positive" : "negative";
      $q.dialog({
        title: "Inutilização NFe",
        message: `${r?.cStat ?? ""} - ${r?.xMotivo ?? ""}`,
        ok: { label: "OK", flat: true, color: tipo },
      });
      emit("action-completed", "inutilizar");
    } catch (error) {
      $q.notify({ type: "negative", message: mensagemErro(error) });
    } finally {
      loadingInutilizar.value = false;
    }
  });
}

function enviarEmailNfe(event) {
  stop(event);
  $q.dialog({
    title: "Enviar Email",
    message: "Digite o endereço de e-mail",
    prompt: {
      model: props.nota?.pessoa?.email || "",
      type: "email",
      outlined: true,
    },
    cancel: { label: "Cancelar", flat: true },
    ok: { label: "Enviar", flat: true, color: "primary" },
  }).onOk(async (email) => {
    loadingEmail.value = true;
    try {
      await props.api.post(`/v1/nota-fiscal/${codnotafiscal.value}/mail`, {
        destinatario: email,
      });
      $q.notify({ type: "positive", message: "Email enviado com sucesso" });
    } catch (error) {
      $q.notify({ type: "negative", message: mensagemErro(error) });
    } finally {
      loadingEmail.value = false;
    }
  });
}

async function abrirDanfe(event) {
  stop(event);
  await abrirPdf(
    props.api,
    `/v1/nota-fiscal/${codnotafiscal.value}/danfe`,
    {},
    { title: props.nota?.modelo === 65 ? "DANFE NFCe" : "DANFE NFe" },
  );
}

async function abrirXml(event) {
  stop(event);
  try {
    const url = await xmlUrlFromApi(
      `/v1/nota-fiscal/${codnotafiscal.value}/xml`,
    );
    window.open(url, "_blank");
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao abrir XML",
      caption: mensagemErro(error),
    });
  }
}

defineExpose({ enviarNfe, podeEnviar, loadingEnviar, progressoNfe });
</script>

<template>
  <div
    class="row no-wrap q-gutter-xs"
    v-if="
      temAcoes ||
      (showExtras && (podeAbrirDanfe || podeAbrirXml || podeEnviarEmail))
    "
  >
    <q-btn
      v-if="podeEnviar"
      flat
      dense
      round
      :size="btnSize"
      color="secondary"
      icon="send"
      :loading="loadingEnviar"
      @click="enviarNfe"
    >
      <q-tooltip>Criar XML e enviar para SEFAZ</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeConsultar"
      flat
      dense
      round
      :size="btnSize"
      color="primary"
      icon="refresh"
      :loading="loadingConsultar"
      @click="consultarNfe"
    >
      <q-tooltip>Consultar situação na SEFAZ</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeAbrirDanfe"
      flat
      dense
      round
      :size="btnSize"
      color="secondary"
      icon="picture_as_pdf"
      @click="abrirDanfe"
    >
      <q-tooltip>Abrir DANFE</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeAbrirXml"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="code"
      @click="abrirXml"
    >
      <q-tooltip>Abrir XML</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeEnviarEmail"
      flat
      dense
      round
      :size="btnSize"
      color="primary"
      icon="email"
      :loading="loadingEmail"
      @click="enviarEmailNfe"
    >
      <q-tooltip>Enviar por email</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeCancelar"
      flat
      dense
      round
      :size="btnSize"
      color="negative"
      icon="cancel"
      :loading="loadingCancelar"
      @click="cancelarNfe"
    >
      <q-tooltip>Cancelar NFe</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeInutilizar"
      flat
      dense
      round
      :size="btnSize"
      color="warning"
      icon="block"
      :loading="loadingInutilizar"
      @click="inutilizarNfe"
    >
      <q-tooltip>Inutilizar NFe</q-tooltip>
    </q-btn>
  </div>

  <q-dialog :model-value="loadingEnviar && !compact" persistent>
    <q-card style="min-width: 350px">
      <q-card-section class="text-grey-9 text-overline">
        Processando NFe
      </q-card-section>

      <q-card-section class="q-pt-none">
        <div class="text-body2 q-mb-md">{{ progressoNfe.status }}</div>
        <q-linear-progress
          :value="progressoNfe.percent / 100"
          color="primary"
          class="q-mt-md"
        />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
