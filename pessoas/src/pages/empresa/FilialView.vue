<script>
import { ref, onMounted, defineAsyncComponent, computed } from "vue";
import { empresaStore } from "src/stores/empresa";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import moment from "moment";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
  },

  setup() {
    const sEmpresa = empresaStore();
    const $q = useQuasar();
    const route = useRoute();
    const router = useRouter();
    const loading = ref(false);
    const erro = ref(false);

    const formatarCodigo = (cod) => {
      if (!cod) return "";
      return "#" + String(cod).padStart(8, "0");
    };

    const ambienteNfeLabel = computed(() => {
      const ambientes = {
        1: "Produção",
        2: "Homologação",
      };
      return ambientes[sEmpresa.filial.nfeambiente] || "-";
    });

    const criacaoFormatada = computed(() => {
      if (!sEmpresa.filial.criacao) return "-";
      return moment(sEmpresa.filial.criacao).format("DD/MM/YYYY - HH:mm");
    });

    const alteracaoFormatada = computed(() => {
      if (!sEmpresa.filial.alteracao) return "-";
      return moment(sEmpresa.filial.alteracao).format("DD/MM/YYYY - HH:mm");
    });

    const validadeCertificadoFormatada = computed(() => {
      if (!sEmpresa.filial.validadecertificado) return "-";
      return moment(sEmpresa.filial.validadecertificado).format("DD/MM/YYYY");
    });

    const carregarFilial = async () => {
      loading.value = true;
      erro.value = false;
      try {
        await sEmpresa.getFilial(route.params.codfilial);
        if (!sEmpresa.filial || !sEmpresa.filial.codfilial) {
          erro.value = true;
          $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: "Filial não encontrada",
          });
        }
      } catch (error) {
        erro.value = true;
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao carregar filial",
        });
      } finally {
        loading.value = false;
      }
    };

    const confirmarExclusao = () => {
      $q.dialog({
        title: "Confirmar Exclusão",
        message: `Para excluir a filial "${sEmpresa.filial.filial}", digite EXCLUIR abaixo:`,
        prompt: {
          model: "",
          type: "text",
          isValid: (val) => val === "EXCLUIR",
        },
        cancel: true,
        persistent: true,
      }).onOk(async () => {
        try {
          await sEmpresa.removerFilial(sEmpresa.filial.codfilial);
          $q.notify({
            color: "green-5",
            textColor: "white",
            icon: "check",
            message: "Filial excluída com sucesso!",
          });
          router.push("/empresa/" + sEmpresa.filial.codempresa);
        } catch (error) {
          $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response?.data?.message || "Erro ao excluir filial",
          });
        }
      });
    };

    onMounted(() => {
      carregarFilial();
    });

    return {
      sEmpresa,
      loading,
      erro,
      formatarCodigo,
      ambienteNfeLabel,
      criacaoFormatada,
      alteracaoFormatada,
      validadeCertificadoFormatada,
      confirmarExclusao,
    };
  },
};
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">
        <template v-if="sEmpresa.filial.codfilial">
          {{ sEmpresa.filial.Empresa?.empresa }} - filial
          {{ sEmpresa.filial.filial }}
        </template>
        <template v-else>Detalhes da Filial</template>
      </span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="'/empresa/' + sEmpresa.filial.codempresa"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div v-if="!loading && erro" class="text-center q-pa-xl">
          <q-icon name="error_outline" size="64px" color="grey" />
          <div class="text-h6 text-grey q-mt-md">Filial não encontrada</div>
          <q-btn
            flat
            color="primary"
            label="Voltar para empresas"
            icon="arrow_back"
            to="/empresa"
            class="q-mt-md"
          />
        </div>

        <div
          v-if="!loading && !erro && sEmpresa.filial?.codfilial"
          class="container-detalhes"
        >
          <!-- HEADER -->
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar
                color="grey-8"
                text-color="grey-4"
                size="80px"
                icon="store"
              />
            </q-item-section>
            <q-item-section>
              <div class="text-h4 text-grey-9">
                #{{ sEmpresa.filial.codfilial }} {{ sEmpresa.filial.filial }}
              </div>
              <div class="text-h5 text-grey-7">
                {{ sEmpresa.filial.Empresa?.empresa }}
              </div>
            </q-item-section>
          </q-item>

          <!-- CONTEÚDO -->
          <div class="row q-col-gutter-md q-pa-md">
            <!-- COLUNA PRINCIPAL -->
            <div class="col-xs-12 col-md-8">
              <div class="row q-col-gutter-md">
                <!-- CARD DETALHES -->
                <div class="col-12">
                  <q-card bordered flat>
                    <q-card-section
                      class="text-grey-9 text-overline row items-center"
                    >
                      DETALHES DA FILIAL
                      <q-space />
                      <q-btn
                        flat
                        round
                        dense
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        :to="'/filial/' + sEmpresa.filial.codfilial + '/editar'"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        round
                        dense
                        icon="delete"
                        size="sm"
                        color="grey-7"
                        @click="confirmarExclusao"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        round
                        dense
                        icon="info"
                        size="sm"
                        color="grey-7"
                      >
                        <q-tooltip>
                          <div>Criado em: {{ criacaoFormatada }}</div>
                          <div>Alterado em: {{ alteracaoFormatada }}</div>
                        </q-tooltip>
                      </q-btn>
                    </q-card-section>

                    <!-- Info Grid -->
                    <div class="row q-col-gutter-sm q-pa-md">
                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Codigo</div>
                        <div class="text-body2">
                          {{ formatarCodigo(sEmpresa.filial.codfilial) }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Empresa</div>
                        <div class="text-body2">
                          <q-btn
                            flat
                            dense
                            no-caps
                            padding="0"
                            color="primary"
                            :to="'/empresa/' + sEmpresa.filial.codempresa"
                            :label="
                              sEmpresa.filial.Empresa?.empresa ||
                              sEmpresa.filial.codempresa
                            "
                          />
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.codpessoa"
                      >
                        <div class="text-overline text-grey-7">Pessoa</div>
                        <div class="text-body2">
                          <q-btn
                            flat
                            dense
                            no-caps
                            padding="0"
                            color="primary"
                            :to="'/pessoa/' + sEmpresa.filial.codpessoa"
                            :label="
                              sEmpresa.filial.Pessoa?.pessoa ||
                              sEmpresa.filial.codpessoa
                            "
                          />
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">CRT</div>
                        <div class="text-body2">
                          {{ sEmpresa.filial.crt || "-" }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">
                          Ambiente NFe
                        </div>
                        <div class="text-body2">
                          <q-badge
                            :color="
                              sEmpresa.filial.nfeambiente === 1
                                ? 'green'
                                : 'orange'
                            "
                            :label="ambienteNfeLabel"
                          />
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Série NFe</div>
                        <div class="text-body2">
                          {{ sEmpresa.filial.nfeserie || "-" }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">Emite NFe</div>
                        <div class="text-body2">
                          <q-badge
                            :color="sEmpresa.filial.emitenfe ? 'green' : 'grey'"
                            :label="sEmpresa.filial.emitenfe ? 'Sim' : 'Não'"
                          />
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">DF-e</div>
                        <div class="text-body2">
                          <q-badge
                            :color="sEmpresa.filial.dfe ? 'green' : 'grey'"
                            :label="sEmpresa.filial.dfe ? 'Sim' : 'Não'"
                          />
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.empresadominio"
                      >
                        <div class="text-overline text-grey-7">
                          Empresa Domínio
                        </div>
                        <div class="text-body2">
                          {{ sEmpresa.filial.empresadominio }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.stonecode"
                      >
                        <div class="text-overline text-grey-7">Stone Code</div>
                        <div class="text-body2">
                          {{ sEmpresa.filial.stonecode }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.ultimonsu"
                      >
                        <div class="text-overline text-grey-7">Último NSU</div>
                        <div class="text-body2">
                          {{ sEmpresa.filial.ultimonsu }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.validadecertificado"
                      >
                        <div class="text-overline text-grey-7">
                          Validade Certificado
                        </div>
                        <div class="text-body2">
                          {{ validadeCertificadoFormatada }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.filial.criacao"
                      >
                        <div class="text-overline text-grey-7">Criação</div>
                        <div class="text-body2">
                          {{ criacaoFormatada }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">
                          Última Alteração
                        </div>
                        <div class="text-body2">
                          {{ alteracaoFormatada }}
                        </div>
                      </div>
                    </div>
                  </q-card>
                </div>
              </div>
            </div>

            <!-- COLUNA LATERAL -->
            <div class="col-xs-12 col-md-4">
              <div class="row q-col-gutter-md">
                <!-- CARD TOKENS -->
                <div
                  class="col-12"
                  v-if="
                    sEmpresa.filial.tokennfce ||
                    sEmpresa.filial.idtokennfce ||
                    sEmpresa.filial.tokenibpt
                  "
                >
                  <q-card bordered flat>
                    <q-card-section
                      class="text-grey-9 text-overline row items-center"
                    >
                      TOKENS
                    </q-card-section>

                    <q-list>
                      <q-item v-if="sEmpresa.filial.tokennfce">
                        <q-item-section avatar>
                          <q-icon color="primary" name="key" size="xs" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="text-caption">
                            Token NFCe
                          </q-item-label>
                          <q-item-label caption class="ellipsis">
                            {{ sEmpresa.filial.tokennfce }}
                          </q-item-label>
                        </q-item-section>
                      </q-item>

                      <q-separator
                        inset
                        v-if="
                          sEmpresa.filial.tokennfce &&
                          sEmpresa.filial.idtokennfce
                        "
                      />

                      <q-item v-if="sEmpresa.filial.idtokennfce">
                        <q-item-section avatar>
                          <q-icon color="primary" name="pin" size="xs" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="text-caption">
                            ID Token NFCe
                          </q-item-label>
                          <q-item-label caption>
                            {{ sEmpresa.filial.idtokennfce }}
                          </q-item-label>
                        </q-item-section>
                      </q-item>

                      <q-separator
                        inset
                        v-if="
                          (sEmpresa.filial.tokennfce ||
                            sEmpresa.filial.idtokennfce) &&
                          sEmpresa.filial.tokenibpt
                        "
                      />

                      <q-item v-if="sEmpresa.filial.tokenibpt">
                        <q-item-section avatar>
                          <q-icon color="primary" name="token" size="xs" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="text-caption">
                            Token IBPT
                          </q-item-label>
                          <q-item-label caption class="ellipsis">
                            {{ sEmpresa.filial.tokenibpt }}
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-card>
                </div>

                <!-- CARD ACBR -->
                <div
                  class="col-12"
                  v-if="
                    sEmpresa.filial.acbrmonitorip ||
                    sEmpresa.filial.acbrmonitorporta ||
                    sEmpresa.filial.caminhomonitoracbr ||
                    sEmpresa.filial.caminhoredeacbr
                  "
                >
                  <q-card bordered flat>
                    <q-card-section
                      class="text-grey-9 text-overline row items-center"
                    >
                      ACBR MONITOR
                    </q-card-section>

                    <div class="row q-col-gutter-sm q-pa-md">
                      <div
                        class="col-12"
                        v-if="
                          sEmpresa.filial.acbrmonitorip ||
                          sEmpresa.filial.acbrmonitorporta
                        "
                      >
                        <div class="text-overline text-grey-7">
                          IP : Porta
                        </div>
                        <div class="text-body2">
                          {{
                            [
                              sEmpresa.filial.acbrmonitorip,
                              sEmpresa.filial.acbrmonitorporta,
                            ]
                              .filter(Boolean)
                              .join(":")
                          }}
                        </div>
                      </div>

                      <div
                        class="col-12"
                        v-if="sEmpresa.filial.caminhomonitoracbr"
                      >
                        <div class="text-overline text-grey-7">
                          Caminho Monitor
                        </div>
                        <div class="text-body2 text-break">
                          {{ sEmpresa.filial.caminhomonitoracbr }}
                        </div>
                      </div>

                      <div
                        class="col-12"
                        v-if="sEmpresa.filial.caminhoredeacbr"
                      >
                        <div class="text-overline text-grey-7">
                          Caminho Rede
                        </div>
                        <div class="text-body2 text-break">
                          {{ sEmpresa.filial.caminhoredeacbr }}
                        </div>
                      </div>
                    </div>
                  </q-card>
                </div>
              </div>
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>

<style scoped>
.container-detalhes {
  max-width: 1280px;
  margin: auto;
}

.text-break {
  word-break: break-all;
}
</style>
