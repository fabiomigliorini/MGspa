<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
  <q-card bordered class="q-mb-md">
    <q-list>
      <q-item-label header>
        Registro de Colaborador
        <q-btn flat round icon="add" @click="
          (dialogNovoColaborador = true),
          (modelNovoColaborador = {}),
          (editColaborador = false)
          " />
      </q-item-label>
    </q-list>
  </q-card>

  <div v-for="(colaborador, iColaborador) in sColaborador.colaboradores" v-bind:key="colaborador.codcolaborador">
    <q-card bordered class="q-mb-md">
      <q-list>
        <q-item>
          <q-item-label header>
            <span v-if="colaborador.vinculo == 1">CLT</span>
            <span v-if="colaborador.vinculo == 2">Menor Aprendiz</span>
            <span v-if="colaborador.vinculo == 90">Terceirizado</span>
            <span v-if="colaborador.vinculo == 91">Diarista</span>
            em
            {{ colaborador.Filial }}
            <q-btn flat round icon="edit" @click="
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
              " />
            <q-btn flat round icon="delete" @click="excluirColaborador(colaborador)" />
            Cargo


            <q-btn flat round icon="add" @click="novoColaboradorCargo(iColaborador, colaborador)" />
            Férias
            <q-btn flat round icon="add" @click="novaFerias(iColaborador, colaborador)" />
            <q-btn flat round icon="description" @click="visualizarFicha(colaborador)">
              <q-tooltip>Ficha do Colaborador</q-tooltip>
            </q-btn>
          </q-item-label>
        </q-item>

        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-icon name="event" color="primary"></q-icon>
          </q-item-section>
          <q-item-section>
            <q-item-label v-if="!colaborador.rescisao">
              {{ moment(colaborador.contratacao).format("DD/MMM/YYYY") }} ({{
                moment(colaborador.contratacao).fromNow()
              }})
            </q-item-label>
            <q-item-label v-else>
              {{ moment(colaborador.contratacao).format("DD/MMM") }} a
              {{ moment(colaborador.rescisao).format("DD/MMM/YYYY") }}
            </q-item-label>
            <q-item-label caption> Contratação / Rescisão </q-item-label>
          </q-item-section>
        </q-item>

        <template v-if="colaborador.experiencia">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-icon name="event" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ moment(colaborador.experiencia).format("DD/MMM/YYYY") }} ({{
                  moment(colaborador.experiencia).fromNow()
                }}) /
                {{
                  moment(colaborador.renovacaoexperiencia).format("DD/MMM/YYYY")
                }}
                ({{ moment(colaborador.renovacaoexperiencia).fromNow() }})
              </q-item-label>
              <q-item-label caption> Experiência / Renovação </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <template v-if="colaborador.numeroponto || colaborador.numerocontabilidade">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-icon name="timer" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label v-if="colaborador.numeroponto || colaborador.numerocontabilidade
                ">
                <span v-if="colaborador.numeroponto">{{
                  colaborador.numeroponto
                }}</span>
                <span v-if="colaborador.numerocontabilidade">
                  / {{ colaborador.numerocontabilidade }}
                </span>
              </q-item-label>
              <q-item-label caption v-if="colaborador.numeroponto || colaborador.numerocontabilidade
                ">
                Ponto / Contabilidade
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <template v-if="colaborador.observacoes">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-icon name="comment" color="primary"></q-icon>
            </q-item-section>
            <q-item-section>
              <q-item-label v-if="colaborador.observacoes" style="white-space: pre-line">
                {{ colaborador.observacoes }}
              </q-item-label>
              <q-item-label caption v-if="colaborador.observacoes">
                Observações
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <q-separator inset />
        <card-colaborador-cargo ref="refCardColaboradorCargo" :colaboradorCargos="colaborador" />

        <card-ferias ref="refCardFerias" :colaborador="colaborador" />
      </q-list>
    </q-card>
  </div>

  <!-- Dialog novo Colaborador -->
  <q-dialog v-model="dialogNovoColaborador">
    <q-card style="min-width: 350px">
      <q-form @submit="
        editColaborador == true ? salvarColaborador() : novoColaborador()
        ">
        <q-card-section>
          <div v-if="editColaborador" class="text-h6">Editar Colaborador</div>
          <div v-else class="text-h6">Novo Colaborador</div>
        </q-card-section>
        <q-card-section>
          <select-filial v-model="modelNovoColaborador.codfilial" :rules="[
            (val) =>
              (val !== null && val !== '' && val !== undefined) ||
              'Filial Obrigatório',
          ]">
          </select-filial>

          <q-select outlined v-model="modelNovoColaborador.vinculo" label="Vinculo" :options="[
            { label: 'CLT', value: 1 },
            { label: 'Menor Aprendiz', value: 2 },
            { label: 'Terceirizado', value: 90 },
            { label: 'Diarista', value: 91 },
                ]" map-options emit-value :rules="[
        (val) =>
          (val !== null && val !== '' && val !== undefined) ||
          'Vinculo Obrigatório',
      ]" />

          <div class="row">
            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.contratacao" class="q-pr-md" mask="##/##/####"
                label="Contratação" :rules="[validaObrigatorio, validaData, validaContratacao]"
                @change="preencheExperiencia()" input-class="text-center">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="modelNovoColaborador.contratacao" :locale="brasil" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.experiencia" mask="##/##/####" label="Experiência"
                :rules="[validaData, validaExperiencia]" input-class="text-center">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="modelNovoColaborador.experiencia" :locale="brasil" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.renovacaoexperiencia" mask="##/##/####"
                label="Renovação Experiência" :rules="[validaData, validaRenovacaoExperiencia]" class="q-pr-md" input-class="text-center">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="modelNovoColaborador.renovacaoexperiencia" :locale="brasil" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>

            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.rescisao" mask="##/##/####" label="Rescisão"
                :rules="[validaData, validaRescisao]" input-class="text-center">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="modelNovoColaborador.rescisao" :locale="brasil" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fechar" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.numeroponto" label="Número Ponto" class="q-pr-md" />
            </div>
            <div class="col-6">
              <q-input outlined v-model="modelNovoColaborador.numerocontabilidade" label="Número Contabilidade" />
            </div>
          </div>

          <q-input outlined autogrow bordeless v-model="modelNovoColaborador.observacoes" class="q-pt-md"
            label="Observações" type="textarea" />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
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

<script>
import { defineComponent, defineAsyncComponent, watch } from "vue";
import { useQuasar, debounce } from "quasar";
import { ref } from "vue";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { colaboradorStore } from "stores/colaborador";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

export default defineComponent({
  name: "CardColaborador",

  methods: {
    async visualizarFicha(colaborador) {
      try {
        const response = await this.sColaborador.getFichaColaborador(colaborador.codcolaborador);
        const blob = new Blob([response.data], { type: 'application/pdf' });
        this.pdfUrl = URL.createObjectURL(blob);
        this.dialogPdfFicha = true;
      } catch (error) {
        this.$q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response?.data?.message || "Erro ao carregar a ficha do colaborador",
        });
      }
    },

    fecharPdfFicha() {
      if (this.pdfUrl) {
        URL.revokeObjectURL(this.pdfUrl);
        this.pdfUrl = null;
      }
      this.dialogPdfFicha = false;
    },

    novaFerias(iColaborador, colaborador) {
      this.$refs.refCardFerias[iColaborador].nova(colaborador);
    },

    novoColaboradorCargo(iColaborador, colaborador) {
      this.$refs.refCardColaboradorCargo[iColaborador].novoColaboradorCargo(colaborador);
    },

    preencheExperiencia() {
      this.modelNovoColaborador.experiencia = moment(
        this.modelNovoColaborador.contratacao,
        "DD/MM/YYYY"
      )
        .add(44, "days")
        .format("DD/MM/YYYY");
      this.modelNovoColaborador.renovacaoexperiencia = moment(
        this.modelNovoColaborador.experiencia,
        "DD/MM/YYYY"
      )
        .add(44, "days")
        .format("DD/MM/YYYY");
    },

    async novoColaborador() {
      this.modelNovoColaborador.codpessoa = this.route.params.id;

      const colab = { ...this.modelNovoColaborador };

      if (colab.contratacao) {
        colab.contratacao = this.Documentos.dataFormatoSql(colab.contratacao);
      }

      if (colab.experiencia) {
        colab.experiencia = this.Documentos.dataFormatoSql(colab.experiencia);
      }
      if (colab.renovacaoexperiencia) {
        colab.renovacaoexperiencia = this.Documentos.dataFormatoSql(
          colab.renovacaoexperiencia
        );
      }
      if (colab.rescisao) {
        colab.rescisao = this.Documentos.dataFormatoSql(colab.rescisao);
      }

      try {
        const ret = await this.sColaborador.novoColaborador(colab);
        if (ret.data.data) {
          this.$q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Colaborador criado!",
          });
          this.dialogNovoColaborador = false;
          this.sColaborador.colaboradores.push(ret.data.data);
        }
      } catch (error) {
        this.$q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response.data.message,
        });
      }
    },

    async salvarColaborador() {
      const colab = { ...this.modelNovoColaborador };

      if (colab.contratacao) {
        colab.contratacao = this.Documentos.dataFormatoSql(colab.contratacao);
      }

      if (colab.experiencia) {
        colab.experiencia = this.Documentos.dataFormatoSql(colab.experiencia);
      }
      if (colab.renovacaoexperiencia) {
        colab.renovacaoexperiencia = this.Documentos.dataFormatoSql(
          colab.renovacaoexperiencia
        );
      }
      if (colab.rescisao) {
        colab.rescisao = this.Documentos.dataFormatoSql(colab.rescisao);
      }

      try {
        const ret = await this.sColaborador.salvarColaborador(colab);

        if (ret.data.data) {
          this.$q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Colaborador Alterado!",
          });
          this.dialogNovoColaborador = false;
          const i = this.sColaborador.colaboradores.findIndex(
            (item) =>
              item.codcolaborador === this.modelNovoColaborador.codcolaborador
          );
          this.sColaborador.colaboradores[i] = ret.data.data;
        }
      } catch (error) {
        this.$q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response.data.message,
        });
      }
    },

    async excluirColaborador(colaborador) {
      this.$q
        .dialog({
          title: "Excluir Colaborador",
          message: "Tem certeza que deseja excluir esse colaborador?",
          cancel: true,
        })
        .onOk(async () => {
          try {
            const ret = await this.sColaborador.excluirColaborador(colaborador);
            this.$q.notify({
              color: "green-5",
              textColor: "white",
              icon: "done",
              message: "Colaborador excluido!",
            });
            await this.sColaborador.getColaboradores(this.route.params.id);
          } catch (error) {
            console.log(error)
            this.$q.notify({
              color: "red-5",
              textColor: "white",
              icon: "error",
              message: error.response.data.message,
            });
          }
        });
    },

    editarColaborador(
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
      this.dialogNovoColaborador = true;
      this.editColaborador = true;

      this.modelNovoColaborador = {
        codcolaborador: codcolaborador,
        codfilial: codfilial,
        contratacao:
          contratacao !== null
            ? this.Documentos.formataDatasemHr(contratacao)
            : null,
        vinculo: vinculo,
        experiencia:
          experiencia !== null
            ? this.Documentos.formataDatasemHr(experiencia)
            : null,
        renovacaoexperiencia:
          renovacaoexperiencia !== null
            ? this.Documentos.formataDatasemHr(renovacaoexperiencia)
            : null,
        rescisao:
          rescisao !== null ? this.Documentos.formataDatasemHr(rescisao) : null,
        numeroponto: numeroponto,
        numerocontabilidade: numerocontabilidade,
        observacoes: observacoes,
      };
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

    validaContratacao(value) {
      const maximo = moment().add(7, "days");
      const cont = moment(value, "DD/MM/YYYY");
      if (maximo.isBefore(cont)) {
        return "Data Muito no Futuro!";
      }

      return true;
    },

    validaExperiencia(value) {
      const minimo = moment(
        this.modelNovoColaborador.contratacao,
        "DD/MM/YYYY"
      );
      const maximo = moment(
        this.modelNovoColaborador.contratacao,
        "DD/MM/YYYY"
      ).add(44, "days");
      const exp = moment(value, "DD/MM/YYYY");
      if (maximo.isBefore(exp)) {
        return "Data Muito no Futuro!";
      }
      if (minimo.isAfter(exp)) {
        return "Experiencia não pode ser anterior á Constratação!";
      }
      return true;
    },

    validaRenovacaoExperiencia(value) {
      const minimo = moment(
        this.modelNovoColaborador.experiencia,
        "DD/MM/YYYY"
      );
      const maximo = moment(
        this.modelNovoColaborador.experiencia,
        "DD/MM/YYYY"
      ).add(44, "days");
      const exp = moment(value, "DD/MM/YYYY");
      if (maximo.isBefore(exp)) {
        return "Data Muito no Futuro!";
      }
      if (minimo.isAfter(exp)) {
        return "Renovação não pode ser anterior a experiência";
      }
      return true;
    },

    validaRescisao(value) {
      if (!value) {
        return true;
      }
      const res = moment(value, "DD/MM/YYYY");
      const contratacao = moment(
        this.modelNovoColaborador.contratacao,
        "DD/MM/YYYY"
      );
      if (contratacao.isAfter(res)) {
        return "Rescisão não pode ser anterior à Contratação!";
      }
      if (this.editColaborador == true) {
        const colaborador = this.sColaborador.colaboradores.find(
          (colaborador) =>
            colaborador.codcolaborador ===
            this.modelNovoColaborador.codcolaborador
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
    },
  },

  components: {
    CardColaboradorCargo: defineAsyncComponent(() =>
      import("components/pessoa/CardColaboradorCargo.vue")
    ),
    CardFerias: defineAsyncComponent(() =>
      import("components/pessoa/CardFerias.vue")
    ),
    SelectFilial: defineAsyncComponent(() =>
      import("components/pessoa/SelectFilial.vue")
    ),
    // SelectCargo: defineAsyncComponent(() =>
    //   import("components/pessoa/SelectCargo.vue")
    // ),
  },

  setup() {
    const $q = useQuasar();
    const sPessoa = pessoaStore();
    const sColaborador = colaboradorStore();
    const route = useRoute();
    const modelNovoColaborador = ref({});
    const user = guardaToken();
    const Documentos = formataDocumetos();
    const editColaborador = ref(false);
    const dialogNovoColaborador = ref(false);
    const colaboradores = ref([]);
    const refCardFerias = ref(null);
    const refCardColaboradorCargo = ref(null);
    const dialogPdfFicha = ref(false);
    const pdfUrl = ref(null);

    return {
      sPessoa,
      sColaborador,
      Documentos,
      route,
      user,
      colaboradores,
      editColaborador,
      dialogNovoColaborador,
      moment,
      modelNovoColaborador,
      refCardFerias,
      refCardColaboradorCargo,
      dialogPdfFicha,
      pdfUrl,
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

  mounted() {
    this.sColaborador.getColaboradores(this.route.params.id);
  },
});
</script>

<style scoped></style>
