<template>
  <template v-if="colaborador.Ferias.length > 0">
    <q-item>
      <q-item-section>
      <div class="row q-col-gutter-md">
        <div
          v-for="ferias in colaborador.Ferias"
          v-bind:key="ferias.codferias"
          class="q-pa-md"
        >
          <q-card
            bordered
            :class="ferias.prevista == true ? 'bg-orange-3' : null"
          >
            <q-item>
              <q-item-label header>
                {{ ferias.dias }} dias em
                {{ moment(ferias.gozoinicio).format("MMM/YY") }}
                <q-btn flat round icon="edit" @click="editar(ferias)" />
                <q-btn flat round icon="delete" @click="excluir(ferias)" />
              </q-item-label>
            </q-item>

            <q-separator inset />
            <q-item>
              <q-item-section avatar>
                <q-icon name="celebration" color="blue"></q-icon>
              </q-item-section>
              <q-item-section>
                <q-item-label v-if="ferias.diasgozo">
                  {{ moment(ferias.gozoinicio).format("DD/MMM") }} a
                  {{ moment(ferias.gozofim).format("DD/MMM/YYYY") }}
                </q-item-label>
                <q-item-label caption>
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
              </q-item-section>
            </q-item>

            <q-separator inset />
            <q-item>
              <q-item-section avatar>
                <q-icon name="event" color="blue"></q-icon>
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ moment(ferias.aquisitivoinicio).format("DD/MMM") }} a
                  {{ moment(ferias.aquisitivofim).format("DD/MMM/YYYY") }}
                </q-item-label>
                <q-item-label caption> Período Aquisitivo </q-item-label>
              </q-item-section>
            </q-item>

            <template v-if="ferias.observacoes">
              <q-separator inset />
              <q-item>
                <q-item-section avatar>
                  <q-icon name="comment" color="blue"></q-icon>
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ ferias.observacoes }}
                  </q-item-label>
                  <q-item-label caption> Observações </q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-card>
        </div>
      </div>
    </q-item-section>
    </q-item>
  </template>

  <!-- Dialog novo Colaborador Ferias -->
  <q-dialog v-model="dialogEditar">
    <q-card style="min-width: 350px">
      <q-form @submit="salvar()">
        <q-card-section>
          <div class="text-h6">Férias</div>
        </q-card-section>
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
              >
              </q-date>
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

<script>
import { defineComponent } from "vue";
import { useQuasar } from "quasar";
import { ref } from "vue";
import { colaboradorStore } from "stores/colaborador";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

export default defineComponent({
  name: "CardFerias",

  methods: {
    calculaPeriodoAquisitivo(colaborador) {
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
    },

    sugereAquisitivoFim() {
      const inicio = moment(this.model.aquisitivoinicio, "DD/MM/YYYY");
      this.model.aquisitivofim = inicio
        .add(1, "year")
        .subtract(1, "day")
        .format("DD/MM/YYYY");
    },

    nova(colaborador) {
      this.dialogEditar = true;
      const aquisitivo = this.calculaPeriodoAquisitivo(colaborador);
      const aquisitivoinicio = aquisitivo.inicio;
      const aquisitivofim = aquisitivo.fim;
      const dias = aquisitivo.dias;
      const gozoinicio = aquisitivofim.clone().add(1, "day");
      const gozofim = gozoinicio.clone().add(dias, "day");
      this.model = {
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
    },

    async salvar() {
      const model = { ...this.model };

      if (model.gozo == null) {
        this.$q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Selecione o período de Gozo das Férias!",
        });
        return;
      }

      model.aquisitivoinicio = moment(
        model.aquisitivoinicio,
        "DD/MM/YYYY"
      ).format("YYYY-MM-DD");
      model.aquisitivofim = moment(model.aquisitivofim, "DD/MM/YYYY").format(
        "YYYY-MM-DD"
      );
      model.gozoinicio = moment(model.gozo.from, "DD/MM/YYYY").format(
        "YYYY-MM-DD"
      );
      model.gozofim = moment(model.gozo.to, "DD/MM/YYYY").format("YYYY-MM-DD");
      delete model.gozo;

      if (model.codferias) {
        try {
          const ret = await this.sColaborador.putFerias(model);
          if (ret.data.data) {
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Férias Alterada!",
            });
            this.dialogEditar = false;
          }
        } catch (error) {
          console.log(error);
          this.$q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response.data.message,
          });
        }
      } else {
        try {
          const ret = await this.sColaborador.postFerias(model);
          if (ret.data.data) {
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Férias Criada!",
            });
            this.dialogEditar = false;
          }
        } catch (error) {
          console.log(error);
          this.$q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response.data.message,
          });
        }
      }
    },

    async excluir(ferias) {
      this.$q
        .dialog({
          title: "Excluir Férias",
          message: "Tem certeza que deseja excluir essas Férias?",
          cancel: true,
        })
        .onOk(async () => {
          try {
            const ret = await this.sColaborador.deleteFerias(ferias);
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Férias excluida!",
            });
          } catch (error) {
            console.log(error);
            this.$q.notify({
              color: "red-5",
              textColor: "white",
              icon: "error",
              message: error.response.data.message,
            });
          }
        });
    },

    editar(ferias) {
      const model = { ...ferias };
      model.aquisitivoinicio = moment(
        model.aquisitivoinicio,
        "YYYY-MM-DD"
      ).format("DD/MM/YYYY");
      model.aquisitivofim = moment(model.aquisitivofim, "YYYY-MM-DD").format(
        "DD/MM/YYYY"
      );
      model.gozo = {
        from: moment(model.gozoinicio, "YYYY-MM-DD").format("DD/MM/YYYY"),
        to: moment(model.gozofim, "YYYY-MM-DD").format("DD/MM/YYYY"),
      };
      delete model.gozoinicio;
      delete model.gozofim;
      this.model = model;
      this.dialogEditar = true;
    },

    validaObrigatorio(value) {
      if (!value) {
        return "Preenchimento Obrigatório!";
      }
      return true;
    },

    validaData(value) {
      if (!value) {
        return true;
      }
      const data = moment(value, "DD/MM/YYYY");
      if (!data.isValid()) {
        return "Data Inválida!";
      }
      return true;
    },

    validaAqInicio(value) {
      const colaborador = this.sColaborador.findColaborador(
        this.model.codcolaborador
      );
      const inicio = moment(value, "DD/MM/YYYY");
      const contratacao = moment(colaborador.contratacao);
      if (contratacao.isAfter(inicio)) {
        return "Aquisitivo início não pode ser anterior a contratação!";
      }
      return true;
    },

    validaAqFim(value) {
      const colaborador = this.sColaborador.findColaborador(
        this.model.codcolaborador
      );
      const aqFim = moment(value, "DD/MM/YYYY");
      const rescisao = moment(colaborador.rescisao);
      if (rescisao.isBefore(aqFim)) {
        return "Aquisitivo fim tem que ser anterior a rescisão!";
      }
      const inicio = moment(this.model.aquisitivoinicio, "DD/MM/YYYY");
      if (inicio.isAfter(aqFim)) {
        return "Aquisitivo fim tem que ser depois do inicio!";
      }
      return true;
    },

    validaDias(value) {
      if (value > 50) {
        return "Valor muito alto!";
      }
      if (value < 1) {
        return "Valor muito baixo!";
      }
      return true;
    },

    calculaDiasGozo() {
      if (!this.model.diasabono || !this.model.diasdescontados) {
        this.model.diasabono = "0";
        this.model.diasdescontados = "0";
      }
      var diasgozo =
        this.model.dias - this.model.diasabono - this.model.diasdescontados;
      this.model.diasgozo = diasgozo;
      const inicio = moment(this.model.gozo.from, "DD/MM/YYYY");
      this.model.gozo.to = inicio
        .add(diasgozo - 1, "days")
        .format("DD/MM/YYYY");
    },

    calculaFimGozo() {
      if (this.model.gozo == null) {
        return;
      }
      switch (typeof this.model.gozo) {
        case "string":
          var mFrom = moment(this.model.gozo, "DD/MM/YYYY");
          break;

        case "object":
          var mFrom = moment(this.model.gozo.from, "DD/MM/YYYY");
          break;

        default:
          var mFrom = moment();
          break;
      }
      const mTo = mFrom.clone().add(this.model.diasgozo - 1, "days");
      this.model.gozo = {
        from: mFrom.format("DD/MM/YYYY"),
        to: mTo.format("DD/MM/YYYY"),
      };
    },
  },

  props: ["colaborador"],

  setup() {
    const $q = useQuasar();
    const sColaborador = colaboradorStore();
    const model = ref({});
    const dialogEditar = ref(false);
    return {
      moment,
      sColaborador,
      dialogEditar,
      model,
      brasil: {
        days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
        daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
        months:
          "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
            "_"
          ),
        monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split(
          "_"
        ),
        firstDayOfWeek: 0,
        format24h: true,
        pluralDay: "dias",
      },
    };
  },
});
</script>

<style scoped></style>
