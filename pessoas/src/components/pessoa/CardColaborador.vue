<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { colaboradorStore } from "stores/colaborador";
import { guardaToken } from "src/stores";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const DIAS_EXPERIENCIA = 30;

import CardColaboradorCargo from "components/pessoa/CardColaboradorCargo.vue";
import CardFerias from "components/pessoa/CardFerias.vue";
import SelectFilial from "components/pessoa/SelectFilial.vue";
import MgInputData from "@components/MgInputData.vue";

const $q = useQuasar();
const sPessoa = pessoaStore();
const sColaborador = colaboradorStore();
const route = useRoute();
const user = guardaToken();

const modelColaborador = ref({});
const editColaborador = ref(false);
const dialogNovoColaborador = ref(false);
const colaboradores = ref([]);
const refCardFerias = ref(null);
const refCardColaboradorCargo = ref(null);
const dialogPdfFicha = ref(false);
const pdfUrl = ref(null);
const colaboradorAtual = ref(null);
const uploadingFicha = ref(false);

const visualizarFicha = async (colaborador) => {
  try {
    colaboradorAtual.value = colaborador;
    const response = await sColaborador.getFichaColaborador(
      colaborador.codcolaborador
    );
    const blob = new Blob([response.data], { type: "application/pdf" });
    const blobUrl = URL.createObjectURL(blob);
    if ($q.platform.is.android) {
      window.open(blobUrl, "_blank");
    } else {
      pdfUrl.value = blobUrl;
      dialogPdfFicha.value = true;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message:
        error.response?.data?.message ||
        "Erro ao carregar a ficha do colaborador",
    });
  }
};

const uploadFichaParaDrive = async () => {
  if (!colaboradorAtual.value) return;

  uploadingFicha.value = true;
  try {
    const response = await sColaborador.uploadFichaColaborador(
      colaboradorAtual.value.codcolaborador
    );

    if (response.data.folder_url) {
      window.open(response.data.folder_url, "_blank");
    }

    if (response.data.file_url) {
      window.open(response.data.file_url, "_blank");
    }

    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ficha enviada para o Google Drive!",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message:
        error.response?.data?.message ||
        "Erro ao enviar a ficha para o Google Drive",
    });
  } finally {
    uploadingFicha.value = false;
  }
};

const fecharPdfFicha = () => {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value);
    pdfUrl.value = null;
  }
  colaboradorAtual.value = null;
  dialogPdfFicha.value = false;
};

const novaFerias = (iColaborador, colaborador) => {
  refCardFerias.value[iColaborador].nova(colaborador);
};

const novoColaboradorCargo = (iColaborador, colaborador) => {
  refCardColaboradorCargo.value[iColaborador].novoColaboradorCargo(colaborador);
};

const preencheExperiencia = () => {
  const diasExp = modelColaborador.value.diasExperiencia || DIAS_EXPERIENCIA;
  modelColaborador.value.diasExperiencia = diasExp;
  modelColaborador.value.experiencia = moment(
    modelColaborador.value.contratacao,
    "YYYY-MM-DD",
    true
  )
    .add(diasExp - 1, "days")
    .format("YYYY-MM-DD");
  const diasRen = modelColaborador.value.diasRenovacao || DIAS_EXPERIENCIA;
  modelColaborador.value.diasRenovacao = diasRen;
  modelColaborador.value.renovacaoexperiencia = moment(
    modelColaborador.value.experiencia,
    "YYYY-MM-DD",
    true
  )
    .add(diasRen, "days")
    .format("YYYY-MM-DD");
};

const alterouDiasExperiencia = () => {
  if (!modelColaborador.value.diasExperiencia) {
    modelColaborador.value.experiencia = null;
    modelColaborador.value.diasExperiencia = null;
    return;
  }
  if (modelColaborador.value.contratacao) {
    const contratacao = moment(
      modelColaborador.value.contratacao,
      "YYYY-MM-DD",
      true
    );
    if (contratacao.isValid()) {
      modelColaborador.value.experiencia = contratacao
        .add(modelColaborador.value.diasExperiencia - 1, "days")
        .format("YYYY-MM-DD");
      alterouDiasRenovacao();
    }
  }
};

const alterouDiasRenovacao = () => {
  if (!modelColaborador.value.diasRenovacao) {
    modelColaborador.value.renovacaoexperiencia = null;
    modelColaborador.value.diasRenovacao = null;
    return;
  }
  if (modelColaborador.value.experiencia) {
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "YYYY-MM-DD",
      true
    );
    if (experiencia.isValid()) {
      modelColaborador.value.renovacaoexperiencia = experiencia
        .add(modelColaborador.value.diasRenovacao, "days")
        .format("YYYY-MM-DD");
    }
  }
};

const alterouExperiencia = () => {
  if (
    modelColaborador.value.contratacao &&
    modelColaborador.value.experiencia
  ) {
    const contratacao = moment(
      modelColaborador.value.contratacao,
      "YYYY-MM-DD",
      true
    );
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "YYYY-MM-DD",
      true
    );
    if (contratacao.isValid() && experiencia.isValid()) {
      modelColaborador.value.diasExperiencia =
        experiencia.diff(contratacao, "days") + 1;
    }
  }
  alterouDiasRenovacao();
};

const alterouRenovacao = () => {
  if (
    modelColaborador.value.experiencia &&
    modelColaborador.value.renovacaoexperiencia
  ) {
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "YYYY-MM-DD",
      true
    );
    const renovacao = moment(
      modelColaborador.value.renovacaoexperiencia,
      "YYYY-MM-DD",
      true
    );
    if (experiencia.isValid() && renovacao.isValid()) {
      modelColaborador.value.diasRenovacao = renovacao.diff(
        experiencia,
        "days"
      );
    }
  }
};

const novoColaborador = async () => {
  modelColaborador.value.codpessoa = route.params.id;

  const colab = { ...modelColaborador.value };
  delete colab.diasExperiencia;
  delete colab.diasRenovacao;

  try {
    const ret = await sColaborador.novoColaborador(colab);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador criado!",
      });
      dialogNovoColaborador.value = false;
      sColaborador.colaboradores.push(ret.data.data);
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
};

const salvarColaborador = async () => {
  const colab = { ...modelColaborador.value };
  delete colab.diasExperiencia;
  delete colab.diasRenovacao;

  try {
    const ret = await sColaborador.salvarColaborador(colab);

    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador Alterado!",
      });
      dialogNovoColaborador.value = false;
      const i = sColaborador.colaboradores.findIndex(
        (item) => item.codcolaborador === modelColaborador.value.codcolaborador
      );
      sColaborador.colaboradores[i] = ret.data.data;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
};

const excluirColaborador = async (colaborador) => {
  $q.dialog({
    title: "Excluir Colaborador",
    message: "Tem certeza que deseja excluir esse colaborador?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sColaborador.excluirColaborador(colaborador);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador excluido!",
      });
      await sColaborador.getColaboradores(route.params.id);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  });
};

const editarColaborador = (
  codcolaborador,
  codfilial,
  contratacao,
  vinculo,
  experiencia,
  renovacaoexperiencia,
  rescisao,
  numeroponto,
  numerocontabilidade,
  observacoes
) => {
  dialogNovoColaborador.value = true;
  editColaborador.value = true;

  modelColaborador.value = {
    codcolaborador,
    codfilial,
    contratacao: contratacao?.substring(0, 10) ?? null,
    vinculo,
    experiencia: experiencia?.substring(0, 10) ?? null,
    renovacaoexperiencia: renovacaoexperiencia?.substring(0, 10) ?? null,
    rescisao: rescisao?.substring(0, 10) ?? null,
    numeroponto,
    numerocontabilidade,
    observacoes,
  };

  if (contratacao && experiencia) {
    const cont = moment(modelColaborador.value.contratacao, "YYYY-MM-DD", true);
    const exp = moment(modelColaborador.value.experiencia, "YYYY-MM-DD", true);
    if (cont.isValid() && exp.isValid()) {
      modelColaborador.value.diasExperiencia = exp.diff(cont, "days") + 1;
    }
  }
  if (experiencia && renovacaoexperiencia) {
    const exp = moment(modelColaborador.value.experiencia, "YYYY-MM-DD", true);
    const ren = moment(
      modelColaborador.value.renovacaoexperiencia,
      "YYYY-MM-DD",
      true
    );
    if (exp.isValid() && ren.isValid()) {
      modelColaborador.value.diasRenovacao = ren.diff(exp, "days");
    }
  }
};

const validaObrigatorio = (value) => {
  if (!value) {
    return "Preenchimento Obrigatório!";
  }
  return true;
};

const validaData = (value) => {
  if (!value) {
    return true;
  }
  const data = moment(value, "DD/MM/YYYY", true);
  if (!data.isValid()) {
    return "Data Inválida!";
  }
  return true;
};

const validaContratacao = (value) => {
  if (!value) return true;
  const cont = moment(value, "DD/MM/YYYY", true);
  if (!cont.isValid()) return true;
  const maximo = moment().add(7, "days");
  if (maximo.isBefore(cont)) {
    return "Data Muito no Futuro!";
  }
  return true;
};

const validaPeriodo = (value, dataBase, msgAnterior) => {
  if (!value || !dataBase) return true;
  const data = moment(value, "DD/MM/YYYY", true);
  if (!data.isValid()) return true;
  const dataISO = data.format("YYYY-MM-DD");
  if (dataISO < dataBase) return msgAnterior;
  const limite = moment(dataBase, "YYYY-MM-DD", true)
    .add(60, "days")
    .format("YYYY-MM-DD");
  if (dataISO > limite) return "Data Muito no Futuro!";
  return true;
};

const validaExperiencia = (value) => {
  return validaPeriodo(
    value,
    modelColaborador.value.contratacao,
    "Experiência não pode ser anterior à Contratação!"
  );
};

const validaRenovacaoExperiencia = (value) => {
  return validaPeriodo(
    value,
    modelColaborador.value.experiencia,
    "Renovação não pode ser anterior à Experiência!"
  );
};

const validaRescisao = (value) => {
  if (!value) {
    return true;
  }
  const res = moment(value, "DD/MM/YYYY", true);
  if (!res.isValid()) return true;
  const resISO = res.format("YYYY-MM-DD");
  const contratacao = modelColaborador.value.contratacao;
  if (contratacao && contratacao > resISO) {
    return "Rescisão não pode ser anterior à Contratação!";
  }
  if (editColaborador.value == true) {
    const colaborador = sColaborador.colaboradores.find(
      (c) => c.codcolaborador === modelColaborador.value.codcolaborador
    );
    let minISO = contratacao;
    colaborador.ColaboradorCargo.forEach((cc) => {
      const inicioISO = cc.inicio?.substring(0, 10);
      if (inicioISO && (!minISO || inicioISO > minISO)) minISO = inicioISO;
      const fimISO = cc.fim?.substring(0, 10);
      if (fimISO && (!minISO || fimISO > minISO)) minISO = fimISO;
    });
    if (minISO && minISO > resISO) {
      return "Existe cargo com data inicial/final posterior à Recisão!";
    }
  }
  return true;
};

const alterouVinculo = () => {
  if (!isClt.value) {
    modelColaborador.value.diasExperiencia = null;
    modelColaborador.value.experiencia = null;
    modelColaborador.value.diasRenovacao = null;
    modelColaborador.value.renovacaoexperiencia = null;
  } else if (!modelColaborador.value.experiencia && modelColaborador.value.contratacao) {
    modelColaborador.value.diasExperiencia = DIAS_EXPERIENCIA;
    modelColaborador.value.diasRenovacao = DIAS_EXPERIENCIA;
    preencheExperiencia();
  }
};

const abrirNovoColaborador = () => {
  editColaborador.value = false;
  modelColaborador.value = {
    contratacao: moment().format("YYYY-MM-DD"),
    vinculo: 1,
    diasExperiencia: DIAS_EXPERIENCIA,
    diasRenovacao: DIAS_EXPERIENCIA,
  };
  preencheExperiencia();
  dialogNovoColaborador.value = true;
};

const submit = () => {
  editColaborador.value ? salvarColaborador() : novoColaborador();
};

const isClt = computed(() => modelColaborador.value.vinculo === 1);

const colaboradoresOrdenados = computed(() =>
  [...sColaborador.colaboradores].sort(
    (a, b) => new Date(b.contratacao) - new Date(a.contratacao)
  )
);

function formataDuracao(inicio, fim) {
  const start = moment(inicio);
  const end = moment(fim);
  const anos = end.diff(start, "years");
  const meses = end.diff(start.clone().add(anos, "years"), "months");
  const dias = end.diff(
    start.clone().add(anos, "years").add(meses, "months"),
    "days"
  );
  const partes = [];
  if (anos > 0) partes.push(`${anos} ${anos === 1 ? "ano" : "anos"}`);
  if (meses > 0) partes.push(`${meses} ${meses === 1 ? "mês" : "meses"}`);
  if (dias > 0) partes.push(`${dias} ${dias === 1 ? "dia" : "dias"}`);
  return partes.join(", ") || "0 dias";
}

onMounted(() => {
  sColaborador.getColaboradores(route.params.id);
});

watch(
  () => route.params.id,
  (novoId) => {
    if (novoId) {
      sColaborador.getColaboradores(novoId);
    }
  }
);
</script>

<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
  <q-card
    bordered
    flat
    class="q-mb-md"
    v-if="sColaborador.colaboradores.length === 0"
  >
    <q-card-section class="text-grey-9 text-overline row items-center">
      REGISTRO DE COLABORADOR
      <q-space />
      <q-btn
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        @click="abrirNovoColaborador()"
      />
    </q-card-section>
    <div class="q-pa-md text-center text-grey">
      Nenhum registro de colaborador
    </div>
  </q-card>

  <div
    v-for="(colaborador, iColaborador) in colaboradoresOrdenados"
    v-bind:key="colaborador.codcolaborador"
  >
    <q-card bordered flat class="q-mb-md q-pb-md">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <span>
          <span v-if="colaborador.vinculo == 1">CLT</span>
          <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
          <span v-if="colaborador.vinculo == 901">Estagiário</span>
          <span v-if="colaborador.vinculo == 90">Terceirizado</span>
          <span v-if="colaborador.vinculo == 91">Diarista</span>
          em {{ colaborador.Filial }}
        </span>
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="edit"
          size="sm"
          color="grey-7"
          @click="
            editarColaborador(
              colaborador.codcolaborador,
              colaborador.codfilial,
              colaborador.contratacao,
              colaborador.vinculo,
              colaborador.experiencia,
              colaborador.renovacaoexperiencia,
              colaborador.rescisao,
              colaborador.numeroponto,
              colaborador.numerocontabilidade,
              colaborador.observacoes
            )
          "
        />
        <q-btn
          flat
          round
          dense
          icon="delete"
          size="sm"
          color="grey-7"
          @click="excluirColaborador(colaborador)"
        />
        <q-btn
          flat
          round
          dense
          icon="description"
          size="sm"
          color="grey-7"
          @click="visualizarFicha(colaborador)"
        >
          <q-tooltip>Ficha do Colaborador</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="folder"
          size="sm"
          color="grey-7"
          :href="
            'https://drive.google.com/drive/folders/' +
            colaborador.googledrivefolderid
          "
          target="_blank"
        >
          <q-tooltip>Arquivos do Colaborador</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="primary"
          @click="abrirNovoColaborador()"
        >
          <q-tooltip>Novo Colaborador</q-tooltip>
        </q-btn>
      </q-card-section>

      <div class="row q-col-gutter-sm q-pa-md">
        <div class="col-xs-12 col-sm-6">
          <div class="text-overline text-grey-7">Contratação / Rescisão</div>
          <div class="text-body2">
            {{ moment(colaborador.contratacao).format("DD/MMM/YYYY") }}
            <template v-if="colaborador.rescisao">
              até
              {{ moment(colaborador.rescisao).format("DD/MMM/YYYY") }}
            </template>
          </div>
          <div class="text-caption text-grey-7">
            <template v-if="!colaborador.rescisao">
              {{ formataDuracao(colaborador.contratacao, moment()) }}
            </template>
            <template v-else>
              {{
                formataDuracao(colaborador.contratacao, colaborador.rescisao)
              }}
            </template>
          </div>
        </div>

        <div class="col-xs-12 col-sm-6" v-if="colaborador.experiencia">
          <div class="text-overline text-grey-7">Experiência / Renovação</div>
          <div class="text-body2">
            {{ moment(colaborador.experiencia).format("DD/MMM/YYYY") }}
            <template v-if="colaborador.renovacaoexperiencia">
              e
              {{
                moment(colaborador.renovacaoexperiencia).format("DD/MMM/YYYY")
              }}
            </template>
          </div>
          <div class="text-caption text-grey-7">
            {{
              moment(colaborador.experiencia).diff(
                moment(colaborador.contratacao),
                "days"
              ) + 1
            }}
            dias
            <template v-if="colaborador.renovacaoexperiencia">
              +
              {{
                moment(colaborador.renovacaoexperiencia).diff(
                  moment(colaborador.experiencia),
                  "days"
                )
              }}
              dias
            </template>
          </div>
        </div>

        <div
          class="col-xs-12 col-sm-6"
          v-if="colaborador.numeroponto || colaborador.numerocontabilidade"
        >
          <div class="text-overline text-grey-7">Ponto / Contabilidade</div>
          <div class="text-body2">
            <span v-if="colaborador.numeroponto">{{
              colaborador.numeroponto
            }}</span>
            <span v-if="colaborador.numerocontabilidade">
              / {{ colaborador.numerocontabilidade }}
            </span>
          </div>
        </div>

        <div class="col-12" v-if="colaborador.observacoes">
          <div class="text-overline text-grey-7">Observações</div>
          <div
            class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
            style="white-space: pre-line"
          >
            {{ colaborador.observacoes }}
          </div>
        </div>
      </div>

      <q-card-section
        class="text-grey-9 text-overline row items-center q-py-xs"
      >
        CARGOS
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="primary"
          @click="novoColaboradorCargo(iColaborador, colaborador)"
        />
      </q-card-section>

      <card-colaborador-cargo
        ref="refCardColaboradorCargo"
        :colaboradorCargos="colaborador"
      />

      <q-card-section
        class="text-grey-9 text-overline row items-center q-py-xs"
      >
        FÉRIAS
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="primary"
          @click="novaFerias(iColaborador, colaborador)"
        />
      </q-card-section>

      <card-ferias ref="refCardFerias" :colaborador="colaborador" />
    </q-card>
  </div>

  <!-- Dialog novo Colaborador -->
  <q-dialog v-model="dialogNovoColaborador">
    <q-card bordered flat style="min-width: 350px">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="editColaborador">EDITAR COLABORADOR</template>
          <template v-else>NOVO COLABORADOR</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- FILIAL -->
            <div class="col-6">
              <select-filial
                v-model="modelColaborador.codfilial"
                :rules="[
                  (val) =>
                    (val !== null && val !== '' && val !== undefined) ||
                    'Filial Obrigatório',
                ]"
                autofocus
              />
            </div>

            <!-- VINCULO -->
            <div class="col-6">
              <q-select
                outlined
                v-model="modelColaborador.vinculo"
                label="Vinculo"
                :options="[
                  { label: 'CLT', value: 1 },
                  { label: 'Menor Aprendiz', value: 2 },
                  { label: 'Estagiário', value: 901 },
                  { label: 'Terceirizado', value: 90 },
                  { label: 'Diarista', value: 91 },
                ]"
                map-options
                emit-value
                :rules="[
                  (val) =>
                    (val !== null && val !== '' && val !== undefined) ||
                    'Vinculo Obrigatório',
                ]"
                @update:model-value="alterouVinculo()"
              />
            </div>

            <!-- CONTRATACAO -->
            <div class="col-6">
              <MgInputData
                type="date"
                v-model="modelColaborador.contratacao"
                label="Contratação"
                :rules="[validaObrigatorio, validaData, validaContratacao]"
                @update:model-value="preencheExperiencia()"
              />
            </div>

            <!-- RESCISAO -->
            <div class="col-6">
              <MgInputData
                type="date"
                v-model="modelColaborador.rescisao"
                label="Rescisão"
                :rules="[validaData, validaRescisao]"
              />
            </div>

            <!-- DIAS DE EXPERIENCIA -->
            <div class="col-2">
              <q-input
                outlined
                v-model.number="modelColaborador.diasExperiencia"
                min="0"
                type="number"
                label="Dias Exp."
                input-class="text-center"
                :disable="!isClt"
                @change="alterouDiasExperiencia()"
              />
            </div>

            <!-- FIM DA EXPERIENCIA -->
            <div class="col-4">
              <MgInputData
                type="date"
                v-model="modelColaborador.experiencia"
                label="Experiência"
                :rules="[validaData, validaExperiencia]"
                :readonly="!isClt"
                @update:model-value="alterouExperiencia()"
              />
            </div>

            <!-- DIAS DA RENOVACAO -->
            <div class="col-2">
              <q-input
                outlined
                v-model.number="modelColaborador.diasRenovacao"
                min="0"
                type="number"
                label="Dias Ren."
                input-class="text-center"
                :disable="!isClt"
                @change="alterouDiasRenovacao()"
              />
            </div>

            <!-- FINAL DA RENOVACAO DA EXPERIENCIA -->
            <div class="col-4">
              <MgInputData
                type="date"
                v-model="modelColaborador.renovacaoexperiencia"
                label="Renovação Experiência"
                :rules="[validaData, validaRenovacaoExperiencia]"
                :readonly="!isClt"
                @update:model-value="alterouRenovacao()"
              />
            </div>

            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaborador.numeroponto"
                label="Número Ponto"
              />
            </div>
            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaborador.numerocontabilidade"
                label="Número Contabilidade"
              />
            </div>

            <div class="col-12">
              <q-input
                outlined
                autogrow
                bordeless
                v-model="modelColaborador.observacoes"
                class="q-pt-md"
                label="Observações"
                type="textarea"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            color="grey-8"
            tabindex="-1"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Dialog Visualizar Ficha PDF -->
  <q-dialog v-model="dialogPdfFicha" @hide="fecharPdfFicha">
    <q-card bordered flat style="width: 90vw; height: 90vh; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        FICHA DO COLABORADOR
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="upload"
          size="sm"
          color="grey-7"
          @click="uploadFichaParaDrive"
          :loading="uploadingFicha"
        >
          <q-tooltip>Upload para Google Drive</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="close"
          size="sm"
          color="grey-7"
          v-close-popup
        />
      </q-card-section>
      <q-card-section class="q-pt-none" style="height: calc(90vh - 70px)">
        <div
          v-if="pdfUrl"
          class="rounded-borders"
          style="width: 100%; height: 100%; overflow: hidden"
        >
          <iframe
            :src="pdfUrl"
            style="width: 100%; height: 100%; border: none"
          ></iframe>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
