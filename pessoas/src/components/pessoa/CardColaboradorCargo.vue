<script setup>
import { defineAsyncComponent, ref } from "vue";
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

const dialogColaboradorCargo = ref(false);
const modelColaboradorCargo = ref({});

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
  dialogColaboradorCargo.value = true;
  preencheCargo(colaborador);
}

async function salvar() {
  const model = { ...modelColaboradorCargo.value };

  if (modelColaboradorCargo.value.codcolaboradorcargo) {
    if (model.inicio) {
      model.inicio = Documentos.dataFormatoSql(model.inicio);
    }
    if (model.fim) {
      model.fim = Documentos.dataFormatoSql(model.fim);
    }
    try {
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
  } else {
    if (model.inicio) {
      model.inicio = Documentos.dataFormatoSql(model.inicio);
    }
    if (model.fim) {
      model.fim = Documentos.dataFormatoSql(model.fim);
    }
    try {
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
      dialogColaboradorCargo.value = false;
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  }
}

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
  <div class="row q-col-gutter-md q-pa-md">
    <div
      v-for="colaboradorCargo in colaboradorCargos.ColaboradorCargo"
      v-bind:key="colaboradorCargo.codcolaboradorcargo"
      class="col-4"
    >
      <q-card bordered>
        <q-card-section class="bg-yellow text-grey-9 q-py-xs">
          <div class="row items-center no-wrap q-gutter-x-xs">
            <span class="text-caption text-weight-medium">
              {{ colaboradorCargo.Cargo }}
            </span>
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="edit"
              size="xs"
              color="grey-9"
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
            />
            <q-btn
              flat
              round
              dense
              icon="delete"
              size="xs"
              color="grey-9"
              @click="excluir(colaboradorCargo)"
            />
          </div>
        </q-card-section>

        <div class="row q-col-gutter-xs q-pa-sm">
          <div class="col-12">
            <div class="text-overline text-grey-7">Filial / Período</div>
            <div class="text-body2">
              {{ colaboradorCargo.Filial }}
            </div>
            <div class="text-caption text-grey-7" v-if="!colaboradorCargo.fim">
              {{ moment(colaboradorCargo.inicio).format("DD/MMM/YYYY") }} a ({{
                Documentos.formataFromNow(colaboradorCargo.inicio)
              }})
            </div>
            <div class="text-caption text-grey-7" v-else>
              {{ moment(colaboradorCargo.inicio).format("DD/MMM") }} a
              {{ moment(colaboradorCargo.fim).format("DD/MMM/YYYY") }}
            </div>
          </div>

          <div
            class="col-12"
            v-if="
              colaboradorCargo.comissaoloja ||
              colaboradorCargo.comissaovenda ||
              colaboradorCargo.comissaoxerox
            "
          >
            <div class="text-overline text-grey-7">Comissão</div>
            <div class="text-body2">
              <span>Loja: {{ colaboradorCargo.comissaoloja }}% </span>
              <span>Venda: {{ colaboradorCargo.comissaovenda }}% </span>
              <span>Xerox: {{ colaboradorCargo.comissaoxerox }}% </span>
            </div>
          </div>

          <div class="col-12" v-if="colaboradorCargo.observacoes">
            <div class="text-overline text-grey-7">Observações</div>
            <div class="text-caption">
              {{ colaboradorCargo.observacoes }}
            </div>
          </div>
        </div>
      </q-card>
    </div>
  </div>

  <!-- Dialog Colaborador Cargo -->
  <q-dialog v-model="dialogColaboradorCargo">
    <q-card style="min-width: 350px">
      <q-form @submit="salvar()">
        <q-card-section>
          <div class="text-h6">Colaborador Cargo</div>
        </q-card-section>
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
