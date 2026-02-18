<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { colaboradorStore } from "stores/colaborador";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const props = defineProps(["colaborador"]);

const $q = useQuasar();
const sColaborador = colaboradorStore();
const model = ref({});
const dialogEditar = ref(false);
const editarFerias = ref(false);

const feriasOrdenadas = computed(() =>
  [...(props.colaborador.Ferias || [])].sort(
    (a, b) => new Date(b.gozoinicio) - new Date(a.gozoinicio)
  )
);

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

function calculaPeriodoAquisitivo(colaborador) {
  var inicio = moment(colaborador.contratacao);
  var dias = 0;
  colaborador.Ferias.forEach((ferias) => {
    var ultima = moment(ferias.aquisitivoinicio);
    if (inicio.isSame(ultima)) {
      dias += ferias.dias;
    } else if (inicio.isBefore(ultima)) {
      inicio = ultima;
      dias = ferias.dias;
    }
  });
  if (dias >= 30) {
    inicio.add(1, "year");
    dias = 30;
  } else {
    dias = 30 - dias;
  }
  return {
    inicio: inicio,
    fim: inicio.clone().add(1, "year").subtract(1, "day"),
    dias: dias,
  };
}

function sugereAquisitivoFim() {
  const inicio = moment(model.value.aquisitivoinicio, "DD/MM/YYYY");
  model.value.aquisitivofim = inicio
    .add(1, "year")
    .subtract(1, "day")
    .format("DD/MM/YYYY");
}

function nova(colaborador) {
  editarFerias.value = false;
  dialogEditar.value = true;
  const aquisitivo = calculaPeriodoAquisitivo(colaborador);
  const aquisitivoinicio = aquisitivo.inicio;
  const aquisitivofim = aquisitivo.fim;
  const dias = aquisitivo.dias;
  const gozoinicio = aquisitivofim.clone().add(1, "day");
  const gozofim = gozoinicio.clone().add(dias, "day");
  model.value = {
    codcolaborador: colaborador.codcolaborador,
    aquisitivoinicio: aquisitivoinicio.format("DD/MM/YYYY"),
    aquisitivofim: aquisitivofim.format("DD/MM/YYYY"),
    gozo: {
      from: gozoinicio.format("DD/MM/YYYY"),
      to: gozofim.format("DD/MM/YYYY"),
    },
    dias: dias,
    diasabono: 0,
    diasdescontados: 0,
    diasgozo: dias,
    observacoes: null,
    prevista: true,
  };
}

function preparaModel() {
  const m = { ...model.value };

  if (m.gozo == null) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Selecione o período de Gozo das Férias!",
    });
    return null;
  }

  m.aquisitivoinicio = moment(m.aquisitivoinicio, "DD/MM/YYYY").format(
    "YYYY-MM-DD"
  );
  m.aquisitivofim = moment(m.aquisitivofim, "DD/MM/YYYY").format("YYYY-MM-DD");
  m.gozoinicio = moment(m.gozo.from, "DD/MM/YYYY").format("YYYY-MM-DD");
  m.gozofim = moment(m.gozo.to, "DD/MM/YYYY").format("YYYY-MM-DD");
  delete m.gozo;
  return m;
}

async function novaFerias() {
  const m = preparaModel();
  if (!m) return;
  try {
    const ret = await sColaborador.postFerias(m);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Férias Criada!",
      });
      dialogEditar.value = false;
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

async function salvarFerias() {
  const m = preparaModel();
  if (!m) return;
  try {
    const ret = await sColaborador.putFerias(m);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Férias Alterada!",
      });
      dialogEditar.value = false;
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

const submit = () => {
  editarFerias.value ? salvarFerias() : novaFerias();
};

async function excluir(ferias) {
  $q.dialog({
    title: "Excluir Férias",
    message: "Tem certeza que deseja excluir essas Férias?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sColaborador.deleteFerias(ferias);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Férias excluida!",
      });
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

function editar(ferias) {
  const m = { ...ferias };
  m.aquisitivoinicio = moment(m.aquisitivoinicio, "YYYY-MM-DD").format(
    "DD/MM/YYYY"
  );
  m.aquisitivofim = moment(m.aquisitivofim, "YYYY-MM-DD").format("DD/MM/YYYY");
  m.gozo = {
    from: moment(m.gozoinicio, "YYYY-MM-DD").format("DD/MM/YYYY"),
    to: moment(m.gozofim, "YYYY-MM-DD").format("DD/MM/YYYY"),
  };
  delete m.gozoinicio;
  delete m.gozofim;
  model.value = m;
  editarFerias.value = true;
  dialogEditar.value = true;
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

function validaAqInicio(value) {
  const colaborador = sColaborador.findColaborador(model.value.codcolaborador);
  const inicio = moment(value, "DD/MM/YYYY");
  const contratacao = moment(colaborador.contratacao);
  if (contratacao.isAfter(inicio)) {
    return "Aquisitivo início não pode ser anterior a contratação!";
  }
  return true;
}

function validaAqFim(value) {
  const colaborador = sColaborador.findColaborador(model.value.codcolaborador);
  const aqFim = moment(value, "DD/MM/YYYY");
  const rescisao = moment(colaborador.rescisao);
  if (rescisao.isBefore(aqFim)) {
    return "Aquisitivo fim tem que ser anterior a rescisão!";
  }
  const inicio = moment(model.value.aquisitivoinicio, "DD/MM/YYYY");
  if (inicio.isAfter(aqFim)) {
    return "Aquisitivo fim tem que ser depois do inicio!";
  }
  return true;
}

function validaDias(value) {
  if (value > 50) {
    return "Valor muito alto!";
  }
  if (value < 1) {
    return "Valor muito baixo!";
  }
  return true;
}

function calculaDiasGozo() {
  if (!model.value.diasabono || !model.value.diasdescontados) {
    model.value.diasabono = "0";
    model.value.diasdescontados = "0";
  }
  var diasgozo =
    model.value.dias - model.value.diasabono - model.value.diasdescontados;
  model.value.diasgozo = diasgozo;
  const inicio = moment(model.value.gozo.from, "DD/MM/YYYY");
  model.value.gozo.to = inicio.add(diasgozo - 1, "days").format("DD/MM/YYYY");
}

function calculaFimGozo() {
  if (model.value.gozo == null) {
    return;
  }
  var mFrom;
  switch (typeof model.value.gozo) {
    case "string":
      mFrom = moment(model.value.gozo, "DD/MM/YYYY");
      break;
    case "object":
      mFrom = moment(model.value.gozo.from, "DD/MM/YYYY");
      break;
    default:
      mFrom = moment();
      break;
  }
  const mTo = mFrom.clone().add(model.value.diasgozo - 1, "days");
  model.value.gozo = {
    from: mFrom.format("DD/MM/YYYY"),
    to: mTo.format("DD/MM/YYYY"),
  };
}

defineExpose({ nova });
</script>

<template>
  <q-list v-if="colaborador.Ferias?.length > 0">
    <template
      v-for="ferias in feriasOrdenadas"
      v-bind:key="ferias.codferias"
    >
      <q-separator inset />
      <q-item>
        <q-item-section avatar>
          <q-btn round flat icon="celebration" color="primary" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="text-weight-bold">
            {{ ferias.dias }} dias em
            {{ moment(ferias.gozoinicio).format("MMM/YY") }}
            <q-badge v-if="ferias.prevista" color="orange" class="q-ml-sm">
              Prevista
            </q-badge>
          </q-item-label>

          <q-item-label caption v-if="ferias.diasgozo">
            Gozo: {{ moment(ferias.gozoinicio).format("DD/MMM") }} a
            {{ moment(ferias.gozofim).format("DD/MMM/YYYY") }}
          </q-item-label>

          <q-item-label caption v-if="ferias.diasgozo">
            {{ ferias.diasgozo }} Dias Gozo
            <span v-if="ferias.diasabono">
              / {{ ferias.diasabono }} de Abono
            </span>
            <span v-if="ferias.diasdescontados">
              / {{ ferias.diasdescontados }} Descontados
            </span>
            <span v-if="ferias.dias != ferias.diasgozo">
              = {{ ferias.dias }} Total
            </span>
          </q-item-label>

          <q-item-label caption>
            Aquisitivo: {{ moment(ferias.aquisitivoinicio).format("DD/MMM") }} a
            {{ moment(ferias.aquisitivofim).format("DD/MMM/YYYY") }}
          </q-item-label>

          <q-item-label caption v-if="ferias.observacoes">
            {{ ferias.observacoes }}
          </q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-item-label caption>
            <q-btn
              flat
              dense
              round
              icon="edit"
              size="sm"
              color="grey-7"
              @click="editar(ferias)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              icon="delete"
              size="sm"
              color="grey-7"
              @click="excluir(ferias)"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-list>
  <div v-else class="q-pa-md text-center text-grey">Nenhuma férias cadastrada</div>

  <!-- Dialog novo Colaborador Ferias -->
  <q-dialog v-model="dialogEditar">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="editarFerias">EDITAR FÉRIAS</template>
          <template v-else>NOVA FÉRIAS</template>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- DIAS -->
            <div class="col-3">
              <q-input
                outlined
                v-model="model.dias"
                label="Dias"
                @change="calculaDiasGozo()"
                autofocus
                type="number"
                step="1"
                input-class="text-right"
                inputmode="numeric"
                min="1"
                max="50"
                :rules="[validaDias]"
              />
            </div>
            <div class="col-3">
              <q-input
                outlined
                v-model="model.diasabono"
                label="Abono"
                @change="calculaDiasGozo()"
                type="number"
                step="1"
                input-class="text-right"
                inputmode="numeric"
                min="0"
                :max="model.dias - model.diasdescontados"
              />
            </div>
            <div class="col-3">
              <q-input
                outlined
                v-model="model.diasdescontados"
                label="Desconto"
                @change="calculaDiasGozo()"
                type="number"
                step="1"
                input-class="text-right"
                inputmode="numeric"
                min="0"
                :max="model.dias - model.diasabono"
              />
            </div>

            <div class="col-3">
              <q-input
                outlined
                v-model="model.diasgozo"
                label="Dias Gozo"
                :rules="[
                  (val) =>
                    (val !== null && val !== '' && val !== undefined) ||
                    'Dias Gozo Obrigatório',
                ]"
                @change="calculaDiasGozo()"
                type="number"
                step="1"
                input-class="text-right"
                inputmode="numeric"
                :min="model.dias - model.diasabono - model.diasdescontados"
                :max="model.dias - model.diasabono - model.diasdescontados"
              />
            </div>

            <!-- AQUISITIVO -->
            <div class="col-6">
              <q-input
                outlined
                v-model="model.aquisitivoinicio"
                mask="##/##/####"
                label="Aquisitivo de"
                :rules="[validaObrigatorio, validaData, validaAqInicio]"
                input-class="text-center"
                @change="sugereAquisitivoFim"
              >
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy
                      cover
                      transition-show="scale"
                      transition-hide="scale"
                    >
                      <q-date
                        v-model="model.aquisitivoinicio"
                        :locale="brasil"
                        mask="DD/MM/YYYY"
                        @update:model-value="sugereAquisitivoFim"
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
                v-model="model.aquisitivofim"
                mask="##/##/####"
                label="Aquisitivo até"
                :rules="[validaObrigatorio, validaData, validaAqFim]"
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
                        v-model="model.aquisitivofim"
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

            <div class="col-12">
              <q-toggle
                outlined
                v-model="model.prevista"
                :label="
                  model.prevista
                    ? 'Prevista! Colaborador ainda não tirou as férias, somente previsão!'
                    : 'Efetivada! Colaborador já tirou as férias!'
                "
              />
            </div>
            <div class="col-12 flex flex-center">
              <q-date
                v-model="model.gozo"
                label="Periodo Gozo"
                :locale="brasil"
                range
                mask="DD/MM/YYYY"
                landscape
                @update:model-value="calculaFimGozo"
              />
            </div>
          </div>

          <q-input
            outlined
            autogrow
            bordeless
            v-model="model.observacoes"
            label="Observações"
            type="textarea"
            class="q-pt-md"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
