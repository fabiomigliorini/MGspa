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
    const filtroFilial = ref("");

    const buscarFiliais = async () => {
      sEmpresa.filtroFilial.filial = filtroFilial.value;
      await sEmpresa.buscarFiliais(route.params.codempresa);
    };

    const formatarCodigo = (cod) => {
      return "#" + String(cod).padStart(8, "0");
    };

    const modoEmissaoLabel = computed(() => {
      const modos = {
        1: "Normal",
        9: "Offline",
      };
      return modos[sEmpresa.item.modoemissaonfce] || "-";
    });

    const contingenciaFormatada = computed(() => {
      if (!sEmpresa.item.contingenciadata) return "-";
      return moment(sEmpresa.item.contingenciadata).format("DD/MM/YYYY HH:mm");
    });

    const criacaoFormatada = computed(() => {
      if (!sEmpresa.item.criacao) return "-";
      return moment(sEmpresa.item.criacao).format("DD/MM/YYYY - HH:mm");
    });

    const alteracaoFormatada = computed(() => {
      if (!sEmpresa.item.alteracao) return "-";
      return moment(sEmpresa.item.alteracao).format("DD/MM/YYYY - HH:mm");
    });

    const carregarEmpresa = async () => {
      loading.value = true;
      try {
        await sEmpresa.get(route.params.codempresa);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao carregar empresa",
        });
        router.push("/empresa");
      } finally {
        loading.value = false;
      }
    };

    const confirmarExclusao = () => {
      if (sEmpresa.filiais.length > 0) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message:
            "Não é possível excluir uma empresa que possui filiais. Exclua as filiais primeiro.",
        });
        return;
      }

      $q.dialog({
        title: "Confirmar Exclusão",
        message: `Para excluir a empresa "${sEmpresa.item.empresa}", digite EXCLUIR abaixo:`,
        prompt: {
          model: "",
          type: "text",
          isValid: (val) => val === "EXCLUIR",
        },
        cancel: true,
        persistent: true,
      }).onOk(async () => {
        try {
          await sEmpresa.removerEmpresa(sEmpresa.item.codempresa);
          $q.notify({
            color: "green-5",
            textColor: "white",
            icon: "check",
            message: "Empresa excluída com sucesso!",
          });
          router.push("/empresa");
        } catch (error) {
          $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: error.response?.data?.message || "Erro ao excluir empresa",
          });
        }
      });
    };

    onMounted(() => {
      carregarEmpresa();
      buscarFiliais();
    });

    return {
      sEmpresa,
      loading,
      filtroFilial,
      modoEmissaoLabel,
      contingenciaFormatada,
      criacaoFormatada,
      alteracaoFormatada,
      confirmarExclusao,
      buscarFiliais,
      formatarCodigo,
    };
  },
};
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Detalhes da Empresa</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        to="/empresa"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div
          v-if="!loading && sEmpresa.item.codempresa"
          class="container-detalhes"
        >
          <!-- HEADER -->
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar
                color="grey-8"
                text-color="grey-4"
                size="80px"
                icon="business"
              />
            </q-item-section>
            <q-item-section>
              <div class="text-h4 text-grey-9">
                #{{ sEmpresa.item.codempresa }} {{ sEmpresa.item.empresa }}
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
                  <q-card bordered flat class="q-pa-none">
                    <q-card-section
                      class="text-grey-9 text-overline row items-center q-pa-md"
                    >
                      DETALHES DA EMPRESA
                      <q-space />
                      <q-btn
                        flat
                        round
                        dense
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        :to="'/empresa/' + sEmpresa.item.codempresa + '/editar'"
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
                          #{{
                            String(sEmpresa.item.codempresa).padStart(8, "0")
                          }}
                        </div>
                      </div>

                      <div class="col-xs-12 col-sm-6">
                        <div class="text-overline text-grey-7">
                          Modo Emissão NFCe
                        </div>
                        <div class="text-body2">
                          <q-badge
                            :color="
                              sEmpresa.item.modoemissaonfce === 1
                                ? 'green'
                                : 'orange'
                            "
                            :label="modoEmissaoLabel"
                          />
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.item.contingenciadata"
                      >
                        <div class="text-overline text-grey-7">
                          Data de Contingência
                        </div>
                        <div class="text-body2">
                          {{ contingenciaFormatada }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.item.contingenciajustificativa"
                      >
                        <div class="text-overline text-grey-7">
                          Justificativa de Contingência
                        </div>
                        <div class="text-body2">
                          {{ sEmpresa.item.contingenciajustificativa }}
                        </div>
                      </div>

                      <div
                        class="col-xs-12 col-sm-6"
                        v-if="sEmpresa.item.criacao"
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
                <!-- CARD FILIAIS -->
                <div class="col-12">
                  <q-card bordered flat>
                    <q-card-section
                      class="text-grey-9 text-overline row items-center"
                    >
                      FILIAIS
                      <q-space />
                      <q-btn
                        flat
                        round
                        dense
                        icon="add"
                        size="sm"
                        color="grey-7"
                        :to="
                          '/empresa/' +
                          sEmpresa.item.codempresa +
                          '/filial/nova'
                        "
                      >
                        <q-tooltip>Nova Filial</q-tooltip>
                      </q-btn>
                    </q-card-section>

                    <q-card-section class="q-pt-none">
                      <q-input
                        v-model="filtroFilial"
                        outlined
                        dense
                        clearable
                        label="Buscar filial"
                        @keyup.enter="buscarFiliais"
                        @clear="buscarFiliais"
                      >
                        <template v-slot:prepend>
                          <q-icon name="search" />
                        </template>
                      </q-input>
                    </q-card-section>

                    <q-inner-loading :showing="sEmpresa.loadingFiliais">
                      <q-spinner-gears size="30px" color="primary" />
                    </q-inner-loading>

                    <div
                      v-if="
                        !sEmpresa.loadingFiliais &&
                        sEmpresa.filiais.length === 0
                      "
                      class="text-grey text-center q-pa-md"
                    >
                      Nenhuma filial cadastrada
                    </div>

                    <q-list v-if="!sEmpresa.loadingFiliais">
                      <template
                        v-for="(filial, index) in sEmpresa.filiais"
                        :key="filial.codfilial"
                      >
                        <q-separator v-if="index > 0" inset />
                        <q-item clickable :to="'/filial/' + filial.codfilial">
                          <q-item-section avatar>
                            <q-icon color="primary" name="store" size="xs" />
                          </q-item-section>
                          <q-item-section>
                            <q-item-label class="text-caption text-bold">
                              {{ filial.filial }}
                            </q-item-label>
                            <q-item-label caption>
                              {{ formatarCodigo(filial.codfilial) }}
                            </q-item-label>
                            <q-item-label caption class="ellipsis">
                              {{ filial.Pessoa?.fantasia || "-" }}
                            </q-item-label>
                          </q-item-section>
                          <q-item-section side>
                            <q-icon name="chevron_right" color="grey" />
                          </q-item-section>
                        </q-item>
                      </template>
                    </q-list>
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
</style>
