<script setup>
import { defineAsyncComponent, ref, computed } from "vue";
import { useQuasar } from "quasar";
import { colaboradorStore } from "stores/colaborador";
import { formataDocumetos } from "src/stores/formataDocumentos";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const SelectFilial = defineAsyncComponent(() =>
  import("components/pessoa/SelectFilial.vue")
);
const SelectCargo = defineAsyncComponent(() =>
  import("components/pessoa/SelectCargo.vue")
);

const props = defineProps(["colaboradorCargos"]);

const $q = useQuasar();
const sColaborador = colaboradorStore();
const Documentos = formataDocumetos();

const cargosOrdenados = computed(() =>
  [...(props.colaboradorCargos.ColaboradorCargo || [])].sort(
    (a, b) => new Date(b.inicio) - new Date(a.inicio)
  )
);

const dialogColaboradorCargo = ref(false);
const modelColaboradorCargo = ref({});
const editarCargo = ref(false);

const brasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months:
    "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
      "_"
    ),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 1,
  format24h: true,
  pluralDay: "dias",
};

function preencheCargo(colaborador) {
  if (colaborador.ColaboradorCargo.length > 0) {
    var dataAtual = moment();
    modelColaboradorCargo.value = {
      inicio: moment(dataAtual).format("DD/MM/YYYY"),
      codcolaborador: colaborador.codcolaborador,
    };
  } else {
    modelColaboradorCargo.value = {
      inicio: moment(colaborador.contratacao, "YYYY-MM-DD").format(
        "DD/MM/YYYY"
      ),
      codcolaborador: colaborador.codcolaborador,
    };
  }
}

function novoColaboradorCargo(colaborador) {
  editarCargo.value = false;
  dialogColaboradorCargo.value = true;
  preencheCargo(colaborador);
}

function preparaModel() {
  const model = { ...modelColaboradorCargo.value };
  if (model.inicio) {
    model.inicio = Documentos.dataFormatoSql(model.inicio);
  }
  if (model.fim) {
    model.fim = Documentos.dataFormatoSql(model.fim);
  }
  return model;
}

async function novoCargo() {
  try {
    const model = preparaModel();
    const ret = await sColaborador.novoColaboradorCargo(model);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador Cargo criado!",
      });
      dialogColaboradorCargo.value = false;
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

async function salvarCargo() {
  try {
    const model = preparaModel();
    const ret = await sColaborador.salvarColaboradorCargo(model);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador Cargo Alterado!",
      });
      dialogColaboradorCargo.value = false;
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
  editarCargo.value ? salvarCargo() : novoCargo();
};

async function excluir(colaboradorCargo) {
  $q.dialog({
    title: "Excluir Colaborador Cargo",
    message: "Tem certeza que deseja excluir esse Cargo?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sColaborador.deleteColaboradorCargo(colaboradorCargo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Colaborador Cargo excluido!",
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

function editarColaboradorCargo(
  codcolaboradorcargo,
  codcolaborador,
  codcargo,
  codfilial,
  inicio,
  fim,
  comissaoloja,
  comissaovenda,
  comissaoxerox,
  gratificacao,
  salario,
  observacoes
) {
  modelColaboradorCargo.value = {
    codcolaboradorcargo: codcolaboradorcargo,
    codcolaborador: codcolaborador,
    codcargo: codcargo,
    codfilial: codfilial,
    inicio: inicio !== null ? Documentos.formataDatasemHr(inicio) : null,
    fim: fim !== null ? Documentos.formataDatasemHr(fim) : null,
    comissaoloja: comissaoloja,
    comissaovenda: comissaovenda,
    comissaoxerox: comissaoxerox,
    gratificacao: gratificacao,
    salario: salario,
    observacoes: observacoes,
  };
  editarCargo.value = true;
  dialogColaboradorCargo.value = true;
}

function validaObrigatorio(value) {
  if (!value) {
    return "Preenchimento Obrigatório!";
  }
  return true;
}

function validaDataValida(value) {
  if (!value) {
    return true;
  }
  const data = moment(value, "DD/MM/YYYY");
  if (!data.isValid()) {
    return "Data Inválida!";
  }
  return true;
}

function validaInicio(value) {
  const inicio = moment(value, "DD/MM/YYYY");
  const contratacao = moment(props.colaboradorCargos.contratacao);
  if (contratacao.isAfter(inicio)) {
    return "Inicio não pode ser anterior á Contratação!";
  }
  return true;
}

function validaFim(value) {
  const fim = moment(value, "DD/MM/YYYY");
  const inicio = moment(modelColaboradorCargo.value.inicio, "DD/MM/YYYY");
  if (inicio.isAfter(fim)) {
    return "Fim não pode ser anterior ao inicio!";
  }
  return true;
}

defineExpose({ novoColaboradorCargo });
</script>

<template>
  <q-list v-if="colaboradorCargos.ColaboradorCargo?.length > 0">
    <template
      v-for="colaboradorCargo in cargosOrdenados"
      v-bind:key="colaboradorCargo.codcolaboradorcargo"
    >
      <q-separator inset />
      <q-item>
        <q-item-section avatar>
          <q-btn round flat icon="work" color="primary" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="text-weight-bold">
            {{ colaboradorCargo.Cargo }}
          </q-item-label>

          <q-item-label caption>
            {{ colaboradorCargo.Filial }}
          </q-item-label>

          <q-item-label caption v-if="!colaboradorCargo.fim">
            {{ moment(colaboradorCargo.inicio).format("DD/MMM/YYYY") }} a ({{
              Documentos.formataFromNow(colaboradorCargo.inicio)
            }})
          </q-item-label>
          <q-item-label caption v-else>
            {{ moment(colaboradorCargo.inicio).format("DD/MMM") }} a
            {{ moment(colaboradorCargo.fim).format("DD/MMM/YYYY") }}
          </q-item-label>

          <q-item-label
            caption
            v-if="
              colaboradorCargo.comissaoloja ||
              colaboradorCargo.comissaovenda ||
              colaboradorCargo.comissaoxerox
            "
          >
            Loja: {{ colaboradorCargo.comissaoloja }}%
            | Venda: {{ colaboradorCargo.comissaovenda }}%
            | Xerox: {{ colaboradorCargo.comissaoxerox }}%
          </q-item-label>

          <q-item-label caption v-if="colaboradorCargo.salario || colaboradorCargo.gratificacao">
            <span v-if="colaboradorCargo.salario">
              Salário: R$ {{ colaboradorCargo.salario }}
            </span>
            <span v-if="colaboradorCargo.salario && colaboradorCargo.gratificacao"> | </span>
            <span v-if="colaboradorCargo.gratificacao">
              Gratificação: R$ {{ colaboradorCargo.gratificacao }}
            </span>
          </q-item-label>

          <q-item-label caption v-if="colaboradorCargo.observacoes">
            {{ colaboradorCargo.observacoes }}
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
              @click="
                editarColaboradorCargo(
                  colaboradorCargo.codcolaboradorcargo,
                  colaboradorCargo.codcolaborador,
                  colaboradorCargo.codcargo,
                  colaboradorCargo.codfilial,
                  colaboradorCargo.inicio,
                  colaboradorCargo.fim,
                  colaboradorCargo.comissaoloja,
                  colaboradorCargo.comissaovenda,
                  colaboradorCargo.comissaoxerox,
                  colaboradorCargo.gratificacao,
                  colaboradorCargo.salario,
                  colaboradorCargo.observacoes
                )
              "
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
              @click="excluir(colaboradorCargo)"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-list>
  <div v-else class="q-pa-md text-center text-grey">Nenhum cargo cadastrado</div>

  <!-- Dialog Colaborador Cargo -->
  <q-dialog v-model="dialogColaboradorCargo">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="editarCargo">EDITAR CARGO</template>
          <template v-else>NOVO CARGO</template>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <select-cargo
            v-model="modelColaboradorCargo.codcargo"
            :permite-adicionar="true"
            reactive-rules
            :rules="[
              (val) =>
                (val !== null && val !== '' && val !== undefined) ||
                'Cargo Obrigatório',
            ]"
          />

          <select-filial
            v-model="modelColaboradorCargo.codfilial"
            reactive-rules
            :rules="[
              (val) =>
                (val !== null && val !== '' && val !== undefined) ||
                'Filial obrigatório',
            ]"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaboradorCargo.inicio"
                mask="##/##/####"
                label="Início"
                :rules="[validaDataValida, validaInicio, validaObrigatorio]"
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
                        v-model="modelColaboradorCargo.inicio"
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
            <div class="col-6">
              <q-input
                outlined
                v-model="modelColaboradorCargo.fim"
                mask="##/##/####"
                label="Fim"
                :rules="[validaDataValida, validaFim]"
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
                        v-model="modelColaboradorCargo.fim"
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
            <div class="col-4">
              <q-input
                outlined
                type="number"
                step="0.01"
                min="0.01"
                suffix="%"
                v-model="modelColaboradorCargo.comissaoloja"
                input-class="text-right"
                label="Comissão Loja"
              />
            </div>
            <div class="col-4">
              <q-input
                outlined
                type="number"
                step="0.01"
                min="0.01"
                suffix="%"
                v-model="modelColaboradorCargo.comissaovenda"
                input-class="text-right"
                label="Comissão Venda"
              />
            </div>
            <div class="col-4">
              <q-input
                outlined
                type="number"
                step="0.01"
                min="0.01"
                suffix="%"
                v-model="modelColaboradorCargo.comissaoxerox"
                input-class="text-right"
                label="Comissão Xerox"
              />
            </div>

            <div class="col-6">
              <q-input
                outlined
                prefix="R$"
                type="number"
                step="0.01"
                min="0.01"
                input-class="text-right"
                v-model="modelColaboradorCargo.salario"
                label="Salário"
              />
            </div>

            <div class="col-6">
              <q-input
                outlined
                prefix="R$"
                type="number"
                step="0.01"
                min="0.01"
                input-class="text-right"
                v-model="modelColaboradorCargo.gratificacao"
                label="Gratificação"
              />
            </div>

            <div class="col-12">
              <q-input
                outlined
                autogrow
                bordeless
                v-model="modelColaboradorCargo.observacoes"
                label="Observações"
                type="textarea"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
