<template>
  <div class="row">
    <div
      v-for="colaboradorCargo in colaboradorCargos.ColaboradorCargo"
      v-bind:key="colaboradorCargo.codcolaboradorcargo"
    >
      <div class="q-pa-md">
        <q-card bordered>
          <q-item-label header>
            {{ colaboradorCargo.Cargo }}
            <q-btn
              flat
              round
              icon="edit"
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
              icon="delete"
              @click="excluir(colaboradorCargo)"
            />
          </q-item-label>

          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-icon name="corporate_fare" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label v-if="colaboradorCargo.inicio">
                {{ colaboradorCargo.Filial }}
              </q-item-label>
              <q-item-label caption v-if="!colaboradorCargo.fim">
                {{ moment(colaboradorCargo.inicio).format("DD/MMM/YYYY") }} a
                ({{ Documentos.formataFromNow(colaboradorCargo.inicio) }})
              </q-item-label>
              <q-item-label caption v-else>
                {{ moment(colaboradorCargo.inicio).format("DD/MMM") }} a
                {{ moment(colaboradorCargo.fim).format("DD/MMM/YYYY") }}
              </q-item-label>
            </q-item-section>
          </q-item>

          <q-separator inset />

          <q-item :to="'/cargo/' + colaboradorCargo.codcargo" clickable>
            <q-item-section avatar>
              <q-icon name="engineering" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label v-if="colaboradorCargo.inicio">
                {{ colaboradorCargo.Cargo }}
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />
          <q-item
            v-if="
              colaboradorCargo.comissaoloja ||
              colaboradorCargo.comissaovenda ||
              colaboradorCargo.comissaoxerox
            "
          >
            <q-item-section avatar>
              <q-icon name="money" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <span> Loja: {{ colaboradorCargo.comissaoloja }}% </span>
                <span>Venda: {{ colaboradorCargo.comissaovenda }}% </span>
                <span>Xerox: {{ colaboradorCargo.comissaoxerox }}% </span>
              </q-item-label>
              <q-item-label caption> Comissão </q-item-label>
            </q-item-section>
          </q-item>

          <!--
          <template v-if="colaboradorCargo.gratificacao">
            <q-separator inset />
            <q-item>
              <q-item-section avatar>
                <q-icon name="payments" color="primary"></q-icon>
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "currency",
                      currency: "BRL",
                    }).format(colaboradorCargo.gratificacao)
                  }}
                </q-item-label>
                <q-item-label caption> Gratificação </q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <template v-if="colaboradorCargo.salario">
              <q-separator inset />
              <q-item>
                  <q-item-section avatar>
                      <q-icon name="attach_money" color="primary"></q-icon>
                  </q-item-section>
                  <q-item-section>
                      <q-item-label>
                          {{ new Intl.NumberFormat('pt-BR', {
                              style: 'currency', currency: 'BRL'
                          }).format(colaboradorCargo.salario) }}
                      </q-item-label>
                      <q-item-label caption>
                          Salário
                      </q-item-label>
                  </q-item-section>
              </q-item>
          </template>
          -->

          <template v-if="colaboradorCargo.observacoes">
            <q-separator inset />
            <q-item>
              <q-item-section avatar>
                <q-icon name="comment" color="primary"></q-icon>
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ colaboradorCargo.observacoes }}
                </q-item-label>
                <q-item-label caption> Observações </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-card>
      </div>
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
          ></select-cargo>

          <select-filial
            v-model="modelColaboradorCargo.codfilial"
            reactive-rules
            :rules="[
              (val) =>
                (val !== null && val !== '' && val !== undefined) ||
                'Filial obrigatório',
            ]"
          >
          </select-filial>

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

<script>
import { defineComponent, defineAsyncComponent, computed } from "vue";
import { useQuasar, debounce, TouchSwipe } from "quasar";
import { ref } from "vue";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { colaboradorStore } from "stores/colaborador";

import { formataDocumetos } from "src/stores/formataDocumentos";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

export default defineComponent({
  name: "CardColaboradorCargo",

  methods: {
    preencheCargo(colaborador) {
      if (colaborador.ColaboradorCargo.length > 0) {
        var dataAtual = moment();
        this.modelColaboradorCargo = {
          inicio: moment(dataAtual).format("DD/MM/YYYY"),
          codcolaborador: colaborador.codcolaborador,
        };
      } else {
        this.modelColaboradorCargo = {
          inicio: moment(colaborador.contratacao, "YYYY-MM-DD").format(
            "DD/MM/YYYY"
          ),
          codcolaborador: colaborador.codcolaborador,
        };
      }
    },

    novoColaboradorCargo(colaborador) {
      this.dialogColaboradorCargo = true;

      const preencheCargo = this.preencheCargo(colaborador);
    },

    async salvar() {
      const model = { ...this.modelColaboradorCargo };

      if (this.modelColaboradorCargo.codcolaboradorcargo) {
        // editar colaborador cargo
        if (model.inicio) {
          model.inicio = this.Documentos.dataFormatoSql(model.inicio);
        }

        if (model.fim) {
          model.fim = this.Documentos.dataFormatoSql(model.fim);
        }

        try {
          const ret = await this.sColaborador.salvarColaboradorCargo(model);
          if (ret.data.data) {
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Colaborador Cargo Alterado!",
            });
            this.dialogColaboradorCargo = false;
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
        // novo colaborador cargo
        if (model.inicio) {
          model.inicio = this.Documentos.dataFormatoSql(model.inicio);
        }

        if (model.fim) {
          model.fim = this.Documentos.dataFormatoSql(model.fim);
        }

        try {
          const ret = await this.sColaborador.novoColaboradorCargo(model);
          if (ret.data.data) {
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Colaborador Cargo criado!",
            });
            this.dialogColaboradorCargo = false;
          }
          this.dialogColaboradorCargo = false;
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

    async excluir(colaboradorCargo) {
      this.$q
        .dialog({
          title: "Excluir Colaborador Cargo",
          message: "Tem certeza que deseja excluir esse Cargo?",
          cancel: true,
        })
        .onOk(async () => {
          try {
            const ret = await this.sColaborador.deleteColaboradorCargo(
              colaboradorCargo
            );
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Colaborador Cargo excluido!",
            });
          } catch (error) {
            this.$q.notify({
              color: "red-5",
              textColor: "white",
              icon: "error",
              message: error.response.data.message,
            });
          }
        });
    },

    async editarColaboradorCargo(
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
      this.modelColaboradorCargo = {
        codcolaboradorcargo: codcolaboradorcargo,
        codcolaborador: codcolaborador,
        codcargo: codcargo,
        codfilial: codfilial,
        inicio:
          inicio !== null ? this.Documentos.formataDatasemHr(inicio) : null,
        fim: fim !== null ? this.Documentos.formataDatasemHr(fim) : null,
        comissaoloja: comissaoloja,
        comissaovenda: comissaovenda,
        comissaoxerox: comissaoxerox,
        gratificacao: gratificacao,
        salario: salario,
        observacoes: observacoes,
      };
      this.dialogColaboradorCargo = true;
    },

    validaObrigatorio(value) {
      if (!value) {
        return "Preenchimento Obrigatório!";
      }
      return true;
    },

    validaDataValida(value) {
      if (!value) {
        return true;
      }
      const data = moment(value, "DD/MM/YYYY");
      if (!data.isValid()) {
        return "Data Inválida!";
      }
      return true;
    },

    validaInicio(value) {
      const inicio = moment(value, "DD/MM/YYYY");
      const contratacao = moment(this.colaboradorCargos.contratacao);

      if (contratacao.isAfter(inicio)) {
        return "Inicio não pode ser anterior á Contratação!";
      }

      return true;
    },

    validaFim(value) {
      const fim = moment(value, "DD/MM/YYYY");
      const inicio = moment(this.modelColaboradorCargo.inicio, "DD/MM/YYYY");

      if (inicio.isAfter(fim)) {
        return "Fim não pode ser anterior ao inicio!";
      }
      return true;
    },
  },

  components: {
    SelectFilial: defineAsyncComponent(() =>
      import("components/pessoa/SelectFilial.vue")
    ),
    SelectCargo: defineAsyncComponent(() =>
      import("components/pessoa/SelectCargo.vue")
    ),
  },

  props: ["colaboradorCargos"],

  setup(props, ctx) {
    const $q = useQuasar();
    const sPessoa = pessoaStore();
    const sColaborador = colaboradorStore();
    const route = useRoute();
    const user = guardaToken();
    const Documentos = formataDocumetos();
    const colaboradores = ref([]);
    const dialogColaboradorCargo = ref(false);
    const modelColaboradorCargo = ref({});

    return {
      sPessoa,
      Documentos,
      moment,
      dialogColaboradorCargo,
      modelColaboradorCargo,
      route,
      user,
      sColaborador,
      colaboradores,
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
        firstDayOfWeek: 1,
        format24h: true,
        pluralDay: "dias",
      },
    };
  },
});
</script>

<style scoped></style>
