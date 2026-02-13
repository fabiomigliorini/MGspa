<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { colaboradorStore } from "stores/colaborador";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const DIAS_EXPERIENCIA = 30;

const CardColaboradorCargo = defineAsyncComponent(() =>
  import("components/pessoa/CardColaboradorCargo.vue")
);
const CardFerias = defineAsyncComponent(() =>
  import("components/pessoa/CardFerias.vue")
);
const SelectFilial = defineAsyncComponent(() =>
  import("components/pessoa/SelectFilial.vue")
);

const $q = useQuasar();
const sPessoa = pessoaStore();
const sColaborador = colaboradorStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

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

const brasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months:
    "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
      "_"
    ),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 0,
  format24h: true,
  pluralDay: "dias",
};

async function visualizarFicha(colaborador) {
  try {
    colaboradorAtual.value = colaborador;
    const response = await sColaborador.getFichaColaborador(
      colaborador.codcolaborador
    );
    const blob = new Blob([response.data], { type: "application/pdf" });
    pdfUrl.value = URL.createObjectURL(blob);
    dialogPdfFicha.value = true;
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
}

async function uploadFichaParaDrive() {
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
}

function fecharPdfFicha() {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value);
    pdfUrl.value = null;
  }
  colaboradorAtual.value = null;
  dialogPdfFicha.value = false;
}

function novaFerias(iColaborador, colaborador) {
  refCardFerias.value[iColaborador].nova(colaborador);
}

function novoColaboradorCargo(iColaborador, colaborador) {
  refCardColaboradorCargo.value[iColaborador].novoColaboradorCargo(colaborador);
}

function preencheExperiencia() {
  const diasExp = modelColaborador.value.diasExperiencia || DIAS_EXPERIENCIA;
  modelColaborador.value.diasExperiencia = diasExp;
  modelColaborador.value.experiencia = moment(
    modelColaborador.value.contratacao,
    "DD/MM/YYYY"
  )
    .add(diasExp - 1, "days")
    .format("DD/MM/YYYY");
  const diasRen = modelColaborador.value.diasRenovacao || DIAS_EXPERIENCIA;
  modelColaborador.value.diasRenovacao = diasRen;
  modelColaborador.value.renovacaoexperiencia = moment(
    modelColaborador.value.experiencia,
    "DD/MM/YYYY"
  )
    .add(diasRen, "days")
    .format("DD/MM/YYYY");
}

function alterouDiasExperiencia() {
  if (!modelColaborador.value.diasExperiencia) {
    modelColaborador.value.experiencia = null;
    modelColaborador.value.diasExperiencia = null;
    return;
  }
  if (modelColaborador.value.contratacao) {
    const contratacao = moment(
      modelColaborador.value.contratacao,
      "DD/MM/YYYY"
    );
    if (contratacao.isValid()) {
      modelColaborador.value.experiencia = contratacao
        .add(modelColaborador.value.diasExperiencia - 1, "days")
        .format("DD/MM/YYYY");
      alterouDiasRenovacao();
    }
  }
}

function alterouDiasRenovacao() {
  if (!modelColaborador.value.diasRenovacao) {
    modelColaborador.value.renovacaoexperiencia = null;
    modelColaborador.value.diasRenovacao = null;
    return;
  }
  if (modelColaborador.value.experiencia) {
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "DD/MM/YYYY"
    );
    if (experiencia.isValid()) {
      modelColaborador.value.renovacaoexperiencia = experiencia
        .add(modelColaborador.value.diasRenovacao, "days")
        .format("DD/MM/YYYY");
    }
  }
}

function alterouExperiencia() {
  if (
    modelColaborador.value.contratacao &&
    modelColaborador.value.experiencia
  ) {
    const contratacao = moment(
      modelColaborador.value.contratacao,
      "DD/MM/YYYY"
    );
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "DD/MM/YYYY"
    );
    if (contratacao.isValid() && experiencia.isValid()) {
      modelColaborador.value.diasExperiencia =
        experiencia.diff(contratacao, "days") + 1;
    }
  }
  alterouDiasRenovacao();
}

function alterouRenovacao() {
  if (
    modelColaborador.value.experiencia &&
    modelColaborador.value.renovacaoexperiencia
  ) {
    const experiencia = moment(
      modelColaborador.value.experiencia,
      "DD/MM/YYYY"
    );
    const renovacao = moment(
      modelColaborador.value.renovacaoexperiencia,
      "DD/MM/YYYY"
    );
    if (experiencia.isValid() && renovacao.isValid()) {
      modelColaborador.value.diasRenovacao = renovacao.diff(
        experiencia,
        "days"
      );
    }
  }
}

async function novoColaborador() {
  modelColaborador.value.codpessoa = route.params.id;

  const colab = { ...modelColaborador.value };
  delete colab.diasExperiencia;
  delete colab.diasRenovacao;

  if (colab.contratacao) {
    colab.contratacao = Documentos.dataFormatoSql(colab.contratacao);
  }
  if (colab.experiencia) {
    colab.experiencia = Documentos.dataFormatoSql(colab.experiencia);
  }
  if (colab.renovacaoexperiencia) {
    colab.renovacaoexperiencia = Documentos.dataFormatoSql(
      colab.renovacaoexperiencia
    );
  }
  if (colab.rescisao) {
    colab.rescisao = Documentos.dataFormatoSql(colab.rescisao);
  }

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
}

async function salvarColaborador() {
  const colab = { ...modelColaborador.value };
  delete colab.diasExperiencia;
  delete colab.diasRenovacao;

  if (colab.contratacao) {
    colab.contratacao = Documentos.dataFormatoSql(colab.contratacao);
  }
  if (colab.experiencia) {
    colab.experiencia = Documentos.dataFormatoSql(colab.experiencia);
  }
  if (colab.renovacaoexperiencia) {
    colab.renovacaoexperiencia = Documentos.dataFormatoSql(
      colab.renovacaoexperiencia
    );
  }
  if (colab.rescisao) {
    colab.rescisao = Documentos.dataFormatoSql(colab.rescisao);
  }

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
}

async function excluirColaborador(colaborador) {
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
}

function editarColaborador(
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
) {
  dialogNovoColaborador.value = true;
  editColaborador.value = true;

  modelColaborador.value = {
    codcolaborador,
    codfilial,
    contratacao:
      contratacao !== null ? Documentos.formataDatasemHr(contratacao) : null,
    vinculo,
    experiencia:
      experiencia !== null ? Documentos.formataDatasemHr(experiencia) : null,
    renovacaoexperiencia:
      renovacaoexperiencia !== null
        ? Documentos.formataDatasemHr(renovacaoexperiencia)
        : null,
    rescisao: rescisao !== null ? Documentos.formataDatasemHr(rescisao) : null,
    numeroponto,
    numerocontabilidade,
    observacoes,
  };

  if (contratacao && experiencia) {
    const cont = moment(modelColaborador.value.contratacao, "DD/MM/YYYY");
    const exp = moment(modelColaborador.value.experiencia, "DD/MM/YYYY");
    if (cont.isValid() && exp.isValid()) {
      modelColaborador.value.diasExperiencia = exp.diff(cont, "days") + 1;
    }
  }
  if (experiencia && renovacaoexperiencia) {
    const exp = moment(modelColaborador.value.experiencia, "DD/MM/YYYY");
    const ren = moment(
      modelColaborador.value.renovacaoexperiencia,
      "DD/MM/YYYY"
    );
    if (exp.isValid() && ren.isValid()) {
      modelColaborador.value.diasRenovacao = ren.diff(exp, "days");
    }
  }
}

function validaObrigatorio(value) {
  if (!value) {
    return "Preenchimento Obrigatório!";
  }
  return true;
}

function validaData(value) {
  if (!value) {
    return true;
  }
  const data = moment(value, "DD/MM/YYYY");
  if (!data.isValid()) {
    return "Data Inválida!";
  }
  return true;
}

function validaContratacao(value) {
  const maximo = moment().add(7, "days");
  const cont = moment(value, "DD/MM/YYYY");
  if (maximo.isBefore(cont)) {
    return "Data Muito no Futuro!";
  }
  return true;
}

function validaPeriodo(value, dataBase, msgAnterior) {
  const base = moment(dataBase, "DD/MM/YYYY");
  const data = moment(value, "DD/MM/YYYY");
  if (data.isAfter(base.clone().add(60, "days"))) {
    return "Data Muito no Futuro!";
  }
  if (data.isBefore(base)) {
    return msgAnterior;
  }
  return true;
}

function validaExperiencia(value) {
  return validaPeriodo(
    value,
    modelColaborador.value.contratacao,
    "Experiência não pode ser anterior à Contratação!"
  );
}

function validaRenovacaoExperiencia(value) {
  return validaPeriodo(
    value,
    modelColaborador.value.experiencia,
    "Renovação não pode ser anterior à Experiência!"
  );
}

function validaRescisao(value) {
  if (!value) {
    return true;
  }
  const res = moment(value, "DD/MM/YYYY");
  const contratacao = moment(modelColaborador.value.contratacao, "DD/MM/YYYY");
  if (contratacao.isAfter(res)) {
    return "Rescisão não pode ser anterior à Contratação!";
  }
  if (editColaborador.value == true) {
    const colaborador = sColaborador.colaboradores.find(
      (c) => c.codcolaborador === modelColaborador.value.codcolaborador
    );
    let min = contratacao;
    colaborador.ColaboradorCargo.forEach((cc) => {
      min = moment.max(min, moment(cc.inicio));
      if (cc.fim) {
        min = moment.max(min, moment(cc.fim));
      }
    });
    if (min.isAfter(res)) {
      return "Existe cargo com data inicial/final posterior à Recisão!";
    }
  }
  return true;
}

function abrirNovoColaborador() {
  editColaborador.value = false;
  modelColaborador.value = {
    contratacao: moment().format("DD/MM/YYYY"),
    diasExperiencia: DIAS_EXPERIENCIA,
    diasRenovacao: DIAS_EXPERIENCIA,
  };
  preencheExperiencia();
  dialogNovoColaborador.value = true;
}

onMounted(() => {
  sColaborador.getColaboradores(route.params.id);
});
</script>

<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
  <q-card bordered class="q-mb-md">
    <q-card-section class="bg-yellow text-grey-9 q-py-sm">
      <div class="row items-center no-wrap q-gutter-x-sm">
        <q-icon name="badge" size="sm" />
        <span class="text-subtitle1 text-weight-medium"
          >Registro de Colaborador</span
        >
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="grey-9"
          @click="abrirNovoColaborador()"
        />
      </div>
    </q-card-section>
  </q-card>

  <div
    v-for="(colaborador, iColaborador) in sColaborador.colaboradores"
    v-bind:key="colaborador.codcolaborador"
  >
    <q-card bordered class="q-mb-md">
      <q-card-section class="bg-yellow text-grey-9 q-py-sm">
        <div class="row items-center no-wrap q-gutter-x-sm">
          <q-icon name="badge" size="sm" />
          <span class="text-subtitle1 text-weight-medium">
            <span v-if="colaborador.vinculo == 1">CLT</span>
            <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
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
            color="grey-9"
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
            color="grey-9"
            @click="excluirColaborador(colaborador)"
          />
          <q-btn
            flat
            round
            dense
            icon="description"
            size="sm"
            color="grey-9"
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
            color="grey-9"
            :href="
              'https://drive.google.com/drive/folders/' +
              colaborador.googledrivefolderid
            "
            target="_blank"
          >
            <q-tooltip>Arquivos do Colaborador</q-tooltip>
          </q-btn>
        </div>
      </q-card-section>

      <div class="row q-col-gutter-sm q-pa-md">
        <div class="col-6">
          <div class="text-overline text-grey-7">Contratação / Rescisão</div>
          <div class="text-body2" v-if="!colaborador.rescisao">
            {{ moment(colaborador.contratacao).format("DD/MMM/YYYY") }} ({{
              moment(colaborador.contratacao).fromNow()
            }})
          </div>
          <div class="text-body2" v-else>
            {{ moment(colaborador.contratacao).format("DD/MMM") }} a
            {{ moment(colaborador.rescisao).format("DD/MMM/YYYY") }}
          </div>
        </div>

        <div class="col-6" v-if="colaborador.experiencia">
          <div class="text-overline text-grey-7">Experiência / Renovação</div>
          <div class="text-body2">
            {{ moment(colaborador.experiencia).format("DD/MMM/YYYY") }} ({{
              moment(colaborador.experiencia).fromNow()
            }}) /
            {{ moment(colaborador.renovacaoexperiencia).format("DD/MMM/YYYY") }}
            ({{ moment(colaborador.renovacaoexperiencia).fromNow() }})
          </div>
        </div>

        <div
          class="col-6"
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

      <q-separator />
      <q-card-section class="q-py-xs q-px-sm">
        <div class="row items-center q-gutter-x-xs">
          <span class="text-caption text-weight-medium text-grey-7">Cargo</span>
          <q-btn
            flat
            round
            dense
            icon="add"
            size="xs"
            color="grey-7"
            @click="novoColaboradorCargo(iColaborador, colaborador)"
          />
          <q-separator vertical class="q-mx-xs" />
          <span class="text-caption text-weight-medium text-grey-7"
            >Férias</span
          >
          <q-btn
            flat
            round
            dense
            icon="add"
            size="xs"
            color="grey-7"
            @click="novaFerias(iColaborador, colaborador)"
          />
        </div>
      </q-card-section>

      <card-colaborador-cargo
        ref="refCardColaboradorCargo"
        :colaboradorCargos="colaborador"
      />

      <card-ferias ref="refCardFerias" :colaborador="colaborador" />
    </q-card>
  </div>

  <!-- Dialog novo Colaborador -->
  <q-dialog v-model="dialogNovoColaborador">
    <q-card style="min-width: 350px">
      <q-form
        @submit="
          editColaborador == true ? salvarColaborador() : novoColaborador()
        "
      >
        <q-card-section class="bg-yellow text-grey-9 text-h6 q-mb-md">
          <template v-if="editColaborador"> Editar Colaborador </template>
          <template v-else> Novo Colaborador </template>
        </q-card-section>

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
              />
            </div>

            <!-- CONTRATACAO -->
            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaborador.contratacao"
                mask="##/##/####"
                label="Contratação"
                :rules="[validaObrigatorio, validaData, validaContratacao]"
                @change="preencheExperiencia()"
                input-class="text-center"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-date
                        v-model="modelColaborador.contratacao"
                        :locale="brasil"
                        mask="DD/MM/YYYY"
                        @update:model-value="preencheExperiencia()"
                      >
                        <div class="row items-center justify-end">
                          <q-btn
                            v-close-popup
                            label="Fechar"
                            color="primary"
                            flat
                          />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>

            <!-- RESCISAO -->
            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaborador.rescisao"
                mask="##/##/####"
                label="Rescisão"
                :rules="[validaData, validaRescisao]"
                input-class="text-center"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-date
                        v-model="modelColaborador.rescisao"
                        :locale="brasil"
                        mask="DD/MM/YYYY"
                      >
                        <div class="row items-center justify-end">
                          <q-btn
                            v-close-popup
                            label="Fechar"
                            color="primary"
                            flat
                          />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
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
                @change="alterouDiasExperiencia()"
              />
            </div>

            <!-- FIM DA EXPERIENCIA -->
            <div class="col-4">
              <q-input
                outlined
                v-model="modelColaborador.experiencia"
                mask="##/##/####"
                label="Experiência"
                :rules="[validaData, validaExperiencia]"
                input-class="text-center"
                @change="alterouExperiencia()"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-date
                        v-model="modelColaborador.experiencia"
                        :locale="brasil"
                        mask="DD/MM/YYYY"
                        @update:model-value="alterouExperiencia()"
                      >
                        <div class="row items-center justify-end">
                          <q-btn
                            v-close-popup
                            label="Fechar"
                            color="primary"
                            flat
                          />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
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
                @change="alterouDiasRenovacao()"
              />
            </div>

            <!-- FINAL DA RENOVACAO DA EXPERIENCIA -->
            <div class="col-4">
              <q-input
                outlined
                v-model="modelColaborador.renovacaoexperiencia"
                mask="##/##/####"
                label="Renovação Experiência"
                :rules="[validaData, validaRenovacaoExperiencia]"
                input-class="text-center"
                @change="alterouRenovacao()"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-date
                        v-model="modelColaborador.renovacaoexperiencia"
                        :locale="brasil"
                        mask="DD/MM/YYYY"
                        @update:model-value="alterouRenovacao()"
                      >
                        <div class="row items-center justify-end">
                          <q-btn
                            v-close-popup
                            label="Fechar"
                            color="primary"
                            flat
                          />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
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
    <q-card style="width: 90vw; height: 90vh; max-width: 90vw">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Ficha do Colaborador</div>
        <q-space />
        <q-btn
          icon="upload"
          flat
          round
          dense
          @click="uploadFichaParaDrive"
          :loading="uploadingFicha"
        >
          <q-tooltip>Upload para Google Drive</q-tooltip>
        </q-btn>
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-card-section class="q-pt-none" style="height: calc(90vh - 60px)">
        <iframe
          v-if="pdfUrl"
          :src="pdfUrl"
          style="width: 100%; height: 100%; border: none"
        ></iframe>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
