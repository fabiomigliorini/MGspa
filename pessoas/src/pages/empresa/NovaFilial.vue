<script>
import { ref, defineAsyncComponent } from "vue";
import { empresaStore } from "src/stores/empresa";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    FormFilial: defineAsyncComponent(() =>
      import("components/empresa/FormFilial.vue")
    ),
  },

  setup() {
    const sEmpresa = empresaStore();
    const $q = useQuasar();
    const route = useRoute();
    const router = useRouter();
    const loading = ref(false);

    const model = ref({
      filial: "",
      codpessoa: null,
      crt: null,
      nfeambiente: 1,
      nfeserie: null,
      emitenfe: false,
      dfe: false,
      tokennfce: "",
      idtokennfce: "",
      tokenibpt: "",
      empresadominio: null,
      stonecode: "",
      senhacertificado: "",
    });

    const salvar = async () => {
      loading.value = true;
      try {
        const m = model.value;
        const payload = {
          codempresa: route.params.codempresa,
          filial: m.filial,
          codpessoa: m.codpessoa,
          crt: m.crt,
          nfeambiente: m.nfeambiente,
          nfeserie: m.nfeserie,
          emitenfe: m.emitenfe,
          dfe: m.dfe,
          nfcetoken: m.tokennfce,
          nfcetokenid: m.idtokennfce,
          tokenibpt: m.tokenibpt,
          empresadominio: m.empresadominio,
          stonecode: m.stonecode,
        };
        if (m.senhacertificado) {
          payload.senhacertificado = m.senhacertificado;
        }
        const ret = await sEmpresa.criarFilial(payload);
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "check",
          message: "Filial criada com sucesso!",
        });
        router.push("/filial/" + ret.data.data.codfilial);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response?.data?.message || "Erro ao criar filial",
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
    <template #tituloPagina>Nova Filial</template>
    <template #content>
      <q-page padding>
        <div class="q-pa-sm items-center row">
          <q-btn
            flat
            icon="arrow_back"
            :to="'/empresa/' + $route.params.codempresa"
            round
          />
          <span class="text-h6">Nova Filial</span>
        </div>

        <q-card class="q-pa-md" style="max-width: 600px; margin: 0 auto">
          <q-card-section>
            <FormFilial v-model="model" :loading="loading" @submit="salvar" />
          </q-card-section>
        </q-card>
      </q-page>
    </template>
  </MGLayout>
</template>
