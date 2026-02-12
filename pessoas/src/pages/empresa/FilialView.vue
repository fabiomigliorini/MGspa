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
      return moment(sEmpresa.filial.criacao).format("DD/MM/YYYY HH:mm");
    });

    const alteracaoFormatada = computed(() => {
      if (!sEmpresa.filial.alteracao) return "-";
      return moment(sEmpresa.filial.alteracao).format("DD/MM/YYYY HH:mm");
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
  <MGLayout>
    <template #tituloPagina>Detalhes da Filial</template>
    <template #content>
      <q-page padding>
        <q-inner-loading :showing="loading">
          <q-spinner-gears size="50px" color="primary" />
        </q-inner-loading>

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

        <div v-if="!loading && !erro && sEmpresa.filial?.codfilial">
          <div class="q-pa-sm items-center row">
            <q-btn
              flat
              icon="arrow_back"
              :to="'/empresa/' + sEmpresa.filial.codempresa"
              round
            />
            <span class="text-h6">{{ sEmpresa.filial.Empresa.empresa }}</span>
          </div>

          <q-card style="max-width: 1000px; margin: 0 auto">
            <q-card-section>
              <div class="row items-center">
                <div class="col">
                  <div class="text-h5">
                    {{ sEmpresa.filial.filial }}
                    <div class="text-caption text-grey">
                      {{ formatarCodigo(sEmpresa.filial.codfilial) }}
                    </div>
                  </div>
                </div>
                <div class="col-auto q-gutter-sm">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="edit"
                    :to="'/filial/' + sEmpresa.filial.codfilial + '/editar'"
                  />
                  <q-btn
                    flat
                    round
                    color="negative"
                    icon="delete"
                    @click="confirmarExclusao"
                  />
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="row">
              <!-- Empresa -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="business" color="primary" size="sm" />
                <span class="text-caption text-grey-7">Empresa:</span>
                <router-link
                  :to="'/empresa/' + sEmpresa.filial.codempresa"
                  class="text-primary ellipsis"
                  style="text-decoration: none"
                >
                  {{
                    sEmpresa.filial.Empresa?.empresa ||
                    sEmpresa.filial.codempresa
                  }}
                </router-link>
              </div>

              <!-- Pessoa -->
              <div
                v-if="sEmpresa.filial.codpessoa"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="person" color="primary" size="sm" />
                <span class="text-caption text-grey-7">Pessoa:</span>
                <router-link
                  :to="'/pessoa/' + sEmpresa.filial.codpessoa"
                  class="text-primary ellipsis"
                  style="text-decoration: none"
                >
                  {{
                    sEmpresa.filial.Pessoa?.pessoa || sEmpresa.filial.codpessoa
                  }}
                </router-link>
              </div>

              <!-- CRT -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="receipt" color="grey" size="sm" />
                <span class="text-caption text-grey-7">CRT:</span>
                <span>{{ sEmpresa.filial.crt || "-" }}</span>
              </div>

              <!-- Ambiente NFe -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="cloud" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Ambiente NFe:</span>
                <q-badge
                  :color="
                    sEmpresa.filial.nfeambiente === 1 ? 'green' : 'orange'
                  "
                  :label="ambienteNfeLabel"
                />
              </div>

              <!-- Série NFe -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="tag" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Série NFe:</span>
                <span>{{ sEmpresa.filial.nfeserie || "-" }}</span>
              </div>

              <!-- Emite NFe -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="verified" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Emite NFe:</span>
                <q-badge
                  :color="sEmpresa.filial.emitenfe ? 'green' : 'grey'"
                  :label="sEmpresa.filial.emitenfe ? 'Sim' : 'Não'"
                />
              </div>

              <!-- DF-e -->
              <div class="col-6 row items-center q-pa-sm q-gutter-sm">
                <q-icon name="sync" color="grey" size="sm" />
                <span class="text-caption text-grey-7">DF-e:</span>
                <q-badge
                  :color="sEmpresa.filial.dfe ? 'green' : 'grey'"
                  :label="sEmpresa.filial.dfe ? 'Sim' : 'Não'"
                />
              </div>

              <!-- Empresa Domínio -->
              <div
                v-if="sEmpresa.filial.empresadominio"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="domain" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Empresa Domínio:</span>
                <span>{{ sEmpresa.filial.empresadominio }}</span>
              </div>

              <!-- Token NFCe -->
              <div
                v-if="sEmpresa.filial.tokennfce"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="key" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Token NFCe:</span>
                <span class="ellipsis">{{ sEmpresa.filial.tokennfce }}</span>
              </div>

              <!-- ID Token NFCe -->
              <div
                v-if="sEmpresa.filial.idtokennfce"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="pin" color="grey" size="sm" />
                <span class="text-caption text-grey-7">ID Token NFCe:</span>
                <span>{{ sEmpresa.filial.idtokennfce }}</span>
              </div>

              <!-- Token IBPT -->
              <div
                v-if="sEmpresa.filial.tokenibpt"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="token" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Token IBPT:</span>
                <span class="ellipsis">{{ sEmpresa.filial.tokenibpt }}</span>
              </div>

              <!-- ACBR Monitor -->
              <div
                v-if="
                  sEmpresa.filial.acbrmonitorip ||
                  sEmpresa.filial.acbrmonitorporta
                "
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="dns" color="grey" size="sm" />
                <span class="text-caption text-grey-7">ACBR Monitor:</span>
                <span>{{
                  [
                    sEmpresa.filial.acbrmonitorip,
                    sEmpresa.filial.acbrmonitorporta,
                  ]
                    .filter(Boolean)
                    .join(":")
                }}</span>
              </div>

              <!-- Caminho Monitor ACBR -->
              <div
                v-if="sEmpresa.filial.caminhomonitoracbr"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="folder" color="grey" size="sm" />
                <span class="text-caption text-grey-7">
                  Caminho Monitor ACBR:</span
                >
                <span style="word-break: break-all">{{
                  sEmpresa.filial.caminhomonitoracbr
                }}</span>
              </div>

              <!-- Caminho Rede ACBR -->
              <div
                v-if="sEmpresa.filial.caminhoredeacbr"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="lan" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Caminho Rede ACBR:</span>
                <span style="word-break: break-all">
                  {{ sEmpresa.filial.caminhoredeacbr }}
                </span>
              </div>

              <!-- Stone Code -->
              <div
                v-if="sEmpresa.filial.stonecode"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="payment" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Stone Code:</span>
                <span>{{ sEmpresa.filial.stonecode }}</span>
              </div>

              <!-- Último NSU -->
              <div
                v-if="sEmpresa.filial.ultimonsu"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="numbers" color="grey" size="sm" />
                <span class="text-caption text-grey-7">Último NSU:</span>
                <span>{{ sEmpresa.filial.ultimonsu }}</span>
              </div>

              <!-- Validade Certificado -->
              <div
                v-if="sEmpresa.filial.validadecertificado"
                class="col-6 row items-center q-pa-sm q-gutter-sm"
              >
                <q-icon name="security" color="grey" size="sm" />
                <span class="text-caption text-grey-7"> Certificado até: </span>
                <span>{{ validadeCertificadoFormatada }}</span>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="row q-gutter-md q-pa-sm">
              <q-item
                v-if="sEmpresa.filial.criacao"
                class="q-gutter-sm q-pa-none"
              >
                <q-icon name="add_circle" color="grey" size="sm" side />
                <q-item-section>
                  <q-item-label caption class="text-grey-7">
                    Criação
                  </q-item-label>
                  <q-item-label class="text-caption">
                    {{ criacaoFormatada }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item class="q-gutter-sm q-pa-none">
                <q-icon name="update" color="grey" size="sm" side />
                <q-item-section>
                  <q-item-label caption class="text-grey-7">
                    Última Alteração
                  </q-item-label>
                  <q-item-label class="text-caption">
                    {{ alteracaoFormatada }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-card-section>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
