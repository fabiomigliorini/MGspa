<script>
import { ref, onMounted, defineAsyncComponent } from "vue";
import { empresaStore } from "src/stores/empresa";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    FormEmpresa: defineAsyncComponent(() =>
      import("components/empresa/FormEmpresa.vue")
    ),
  },

  setup() {
    const sEmpresa = empresaStore();
    const $q = useQuasar();
    const route = useRoute();
    const router = useRouter();
    const loading = ref(false);
    const loadingPage = ref(false);

    const model = ref({
      empresa: "",
      modoemissaonfce: 1,
      contingenciadata: null,
      contingenciajustificativa: "",
    });

    const carregarEmpresa = async () => {
      loadingPage.value = true;
      try {
        const ret = await sEmpresa.get(route.params.codempresa);
        model.value = {
          empresa: ret.data.data.empresa,
          modoemissaonfce: ret.data.data.modoemissaonfce,
          contingenciadata: ret.data.data.contingenciadata,
          contingenciajustificativa: ret.data.data.contingenciajustificativa,
        };
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao carregar empresa",
        });
        router.push("/empresa");
      } finally {
        loadingPage.value = false;
      }
    };

    const salvar = async () => {
      loading.value = true;
      try {
        await sEmpresa.atualizarEmpresa(route.params.codempresa, model.value);
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "check",
          message: "Empresa atualizada com sucesso!",
        });
        router.push("/empresa/" + route.params.codempresa);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response?.data?.message || "Erro ao atualizar empresa",
        });
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      carregarEmpresa();
    });

    return {
      model,
      loading,
      loadingPage,
      salvar,
      sEmpresa,
    };
  },
};
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Editar Empresa</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="'/empresa/' + sEmpresa.item.codempresa"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page padding>
        <q-inner-loading :showing="loadingPage">
          <q-spinner-gears size="50px" color="primary" />
        </q-inner-loading>

        <q-card
          v-if="!loadingPage"
          class="q-pa-md"
          style="max-width: 600px; margin: 0 auto"
        >
          <q-card-section>
            <div class="text-h6">Editar Empresa</div>
            <div class="text-caption text-grey">
              Código: {{ $route.params.codempresa }}
            </div>
          </q-card-section>

          <q-card-section>
            <FormEmpresa v-model="model" :loading="loading" @submit="salvar" />
          </q-card-section>
        </q-card>
      </q-page>
    </template>
  </MGLayout>
</template>
