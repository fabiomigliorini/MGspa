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
      return moment(sEmpresa.item.criacao).format("DD/MM/YYYY HH:mm");
    });

    const alteracaoFormatada = computed(() => {
      if (!sEmpresa.item.alteracao) return "-";
      return moment(sEmpresa.item.alteracao).format("DD/MM/YYYY HH:mm");
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
      <q-page padding>
        <q-inner-loading :showing="loading">
          <q-spinner-gears size="50px" color="primary" />
        </q-inner-loading>

        <div v-if="!loading && sEmpresa.item.codempresa">
          <q-card style="max-width: 1000px; margin: 0 auto">
            <q-card-section>
              <div class="row items-center">
                <div class="col row items-center">
                  <div class="text-h5">
                    {{ sEmpresa.item.empresa }}
                    <div class="text-caption text-grey">
                      Código: {{ sEmpresa.item.codempresa }}
                    </div>
                  </div>
                </div>
                <div class="col-auto q-gutter-sm">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="edit"
                    :to="'/empresa/' + sEmpresa.item.codempresa + '/editar'"
                  >
                    <q-tooltip> Editar Empresa </q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    color="negative"
                    icon="delete"
                    @click="confirmarExclusao"
                  >
                    <q-tooltip> Excluir Empresa </q-tooltip>
                  </q-btn>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="row q-gutter-md q-pa-sm">
              <q-item class="q-gutter-sm q-pa-none">
                <q-icon name="receipt" color="primary" side size="sm" />
                <q-item-section>
                  <q-item-label caption>Modo Emissão NFCe</q-item-label>
                  <q-item-label>
                    <q-badge
                      :color="
                        sEmpresa.item.modoemissaonfce === 1 ? 'green' : 'orange'
                      "
                      :label="modoEmissaoLabel"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item
                v-if="sEmpresa.item.contingenciadata"
                class="q-gutter-sm q-pa-none"
              >
                <q-icon name="warning" color="orange" side size="sm" />
                <q-item-section>
                  <q-item-label caption>Data de Contingência</q-item-label>
                  <q-item-label class="text-caption">
                    {{ contingenciaFormatada }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item
                v-if="sEmpresa.item.contingenciajustificativa"
                class="q-gutter-sm q-pa-none"
              >
                <q-icon name="description" color="grey" side size="sm" />
                <q-item-section>
                  <q-item-label caption>
                    Justificativa de Contingência
                  </q-item-label>
                  <q-item-label class="text-caption">
                    {{ sEmpresa.item.contingenciajustificativa }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item
                v-if="sEmpresa.item.criacao"
                class="q-gutter-sm q-pa-none"
              >
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

          <!-- Seção Filiais -->
          <div style="max-width: 1000px; margin: 0 auto" class="q-mt-lg">
            <div class="row items-center q-py-md">
              <div class="text-h5 col">Filiais</div>
              <q-btn
                color="primary"
                flat
                round
                icon="add"
                :to="'/empresa/' + sEmpresa.item.codempresa + '/filial/nova'"
              >
                <q-tooltip> Nova Filial </q-tooltip>
              </q-btn>
            </div>

            <div class="row q-mb-md q-gutter-sm">
              <q-input
                v-model="filtroFilial"
                outlined
                clearable
                label="Filial"
                class="col"
                @keyup.enter="buscarFiliais"
                @clear="buscarFiliais"
              />
            </div>

            <q-inner-loading :showing="sEmpresa.loadingFiliais">
              <q-spinner-gears size="30px" color="primary" />
            </q-inner-loading>

            <div
              v-if="!sEmpresa.loadingFiliais && sEmpresa.filiais.length === 0"
              class="text-grey text-center q-pa-md"
            >
              Nenhuma filial cadastrada
            </div>

            <div v-if="!sEmpresa.loadingFiliais">
              <template
                v-for="(filial, index) in sEmpresa.filiais"
                :key="filial.codfilial"
              >
                <q-separator v-if="index > 0" />
                <router-link
                  :to="'/filial/' + filial.codfilial"
                  class="row items-center q-pa-sm"
                  style="text-decoration: none; color: inherit"
                >
                  <div class="text-caption text-grey q-mr-md">
                    {{ formatarCodigo(filial.codfilial) }}
                  </div>
                  <div class="text-bold text-primary" style="width: 100px">
                    {{ filial.filial }}
                  </div>
                  <div class="text-grey">
                    {{ filial.Pessoa?.fantasia || "-" }}
                  </div>
                </router-link>
              </template>
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
