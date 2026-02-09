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
      try {
        await sEmpresa.getFilial(route.params.codfilial);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao carregar filial",
        });
        router.push("/empresa");
      } finally {
        loading.value = false;
      }
    };

    const confirmarExclusao = () => {
      $q.dialog({
        title: "Confirmar Exclusão",
        message: `Deseja realmente excluir a filial "${sEmpresa.filial.filial}"?`,
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
            message:
              error.response?.data?.message || "Erro ao excluir filial",
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

        <div v-if="!loading && sEmpresa.filial.codfilial">
          <div class="q-pa-sm items-center row">
            <q-btn
              flat
              icon="arrow_back"
              :to="'/empresa/' + sEmpresa.filial.codempresa"
              round
            />
            <span class="text-h6">{{ sEmpresa.filial.filial }}</span>
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
                    color="negative"
                    icon="delete"
                    @click="confirmarExclusao"
                  />
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-list>
              <q-item>
                <q-item-section side>
                  <q-icon name="business" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Empresa</q-item-label>
                  <q-item-label>
                    <router-link
                      :to="'/empresa/' + sEmpresa.filial.codempresa"
                      class="text-primary"
                    >
                      {{ sEmpresa.filial.Empresa?.empresa || sEmpresa.filial.codempresa }}
                    </router-link>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sEmpresa.filial.codpessoa">
                <q-item-section side>
                  <q-icon name="person" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Pessoa</q-item-label>
                  <q-item-label>
                    <router-link
                      :to="'/pessoa/' + sEmpresa.filial.codpessoa"
                      class="text-primary"
                    >
                      {{ sEmpresa.filial.Pessoa?.pessoa || sEmpresa.filial.codpessoa }}
                    </router-link>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side>
                  <q-icon name="receipt" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>CRT - Código do Regime Tributário</q-item-label>
                  <q-item-label>{{ sEmpresa.filial.crt || '-' }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side>
                  <q-icon name="cloud" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Ambiente NFe</q-item-label>
                  <q-item-label>
                    <q-badge
                      :color="sEmpresa.filial.nfeambiente === 1 ? 'green' : 'orange'"
                      :label="ambienteNfeLabel"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side>
                  <q-icon name="tag" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Série NFe</q-item-label>
                  <q-item-label>{{ sEmpresa.filial.nfeserie || '-' }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side>
                  <q-icon name="verified" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Emite NFe</q-item-label>
                  <q-item-label>
                    <q-badge
                      :color="sEmpresa.filial.emitenfe ? 'green' : 'grey'"
                      :label="sEmpresa.filial.emitenfe ? 'Sim' : 'Não'"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section side>
                  <q-icon name="sync" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>DF-e</q-item-label>
                  <q-item-label>
                    <q-badge
                      :color="sEmpresa.filial.dfe ? 'green' : 'grey'"
                      :label="sEmpresa.filial.dfe ? 'Sim' : 'Não'"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sEmpresa.filial.empresadominio">
                <q-item-section side>
                  <q-icon name="domain" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Empresa Domínio</q-item-label>
                  <q-item-label>{{ sEmpresa.filial.empresadominio }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sEmpresa.filial.stonecode">
                <q-item-section side>
                  <q-icon name="payment" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Stone Code</q-item-label>
                  <q-item-label>{{ sEmpresa.filial.stonecode }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sEmpresa.filial.ultimonsu">
                <q-item-section side>
                  <q-icon name="numbers" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Último NSU</q-item-label>
                  <q-item-label>{{ sEmpresa.filial.ultimonsu }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="sEmpresa.filial.validadecertificado">
                <q-item-section side>
                  <q-icon name="security" color="grey" />
                </q-item-section>
                <q-item-section>
                  <q-item-label caption>Validade Certificado</q-item-label>
                  <q-item-label>{{ validadeCertificadoFormatada }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>

            <q-separator />

            <q-card-section class="row q-gutter-md q-pa-sm">
              <q-item v-if="sEmpresa.filial.criacao" class="q-gutter-sm q-pa-none">
                <q-icon name="add_circle" color="grey" size="sm" side />
                <q-item-section>
                  <q-item-label caption>Criação</q-item-label>
                  <q-item-label class="text-caption">
                    {{ criacaoFormatada }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item class="q-gutter-sm q-pa-none">
                <q-icon name="update" color="grey" size="sm" side />
                <q-item-section>
                  <q-item-label caption>Última Alteração</q-item-label>
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
