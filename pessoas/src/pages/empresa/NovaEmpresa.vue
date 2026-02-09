<script>
import { ref, defineAsyncComponent } from "vue";
import { empresaStore } from "src/stores/empresa";
import { useQuasar } from "quasar";
import { useRouter } from "vue-router";

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
    const router = useRouter();
    const loading = ref(false);

    const model = ref({
      empresa: "",
      modoemissaonfce: 1,
      contingenciadata: null,
      contingenciajustificativa: "",
    });

    const salvar = async () => {
      loading.value = true;
      try {
        const ret = await sEmpresa.criarEmpresa(model.value);
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "check",
          message: "Empresa criada com sucesso!",
        });
        router.push("/empresa/" + ret.data.data.codempresa);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response?.data?.message || "Erro ao criar empresa",
        });
      } finally {
        loading.value = false;
      }
    };

    return {
      model,
      loading,
      salvar,
    };
  },
};
</script>

<template>
  <MGLayout>
    <template #tituloPagina>Nova Empresa</template>
    <template #content>
      <q-page padding>
        <div class="q-px-sm items-center row">
          <q-btn flat icon="arrow_back" to="/empresa" round />
          <span class="text-h6">Empresas</span>
        </div>
        <q-card class="q-pa-md" style="max-width: 600px; margin: 0 auto">
          <q-card-section>
            <div class="text-h6">Cadastrar Nova Empresa</div>
          </q-card-section>
          <FormEmpresa v-model="model" :loading="loading" @submit="salvar" />
        </q-card>
      </q-page>
    </template>
  </MGLayout>
</template>
