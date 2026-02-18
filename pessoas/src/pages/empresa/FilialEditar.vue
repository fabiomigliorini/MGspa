<script>
import { ref, onMounted, defineAsyncComponent } from "vue";
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
    const loadingPage = ref(false);

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

    const carregarFilial = async () => {
      loadingPage.value = true;
      try {
        const ret = await sEmpresa.getFilial(route.params.codfilial);
        const f = ret.data.data;
        model.value = {
          filial: f.filial,
          codpessoa: f.codpessoa,
          crt: f.crt,
          nfeambiente: f.nfeambiente,
          nfeserie: f.nfeserie,
          emitenfe: f.emitenfe || false,
          dfe: f.dfe || false,
          tokennfce: f.tokennfce || "",
          idtokennfce: f.idtokennfce || "",
          tokenibpt: f.tokenibpt || "",
          empresadominio: f.empresadominio,
          stonecode: f.stonecode || "",
          senhacertificado: "",
        };
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao carregar filial",
        });
        router.push("/empresa");
      } finally {
        loadingPage.value = false;
      }
    };

    const salvar = async () => {
      loading.value = true;
      try {
        const m = model.value;
        const payload = {
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
        await sEmpresa.atualizarFilial(route.params.codfilial, payload);
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "check",
          message: "Filial atualizada com sucesso!",
        });
        router.push("/filial/" + route.params.codfilial);
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: error.response?.data?.message || "Erro ao atualizar filial",
        });
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      carregarFilial();
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
      <span class="q-pl-sm">Editar Filial</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="'/filial/' + $route.params.codfilial"
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
            <div class="text-h6">Editar Filial</div>
            <div class="text-caption text-grey">
              Código: {{ $route.params.codfilial }}
            </div>
          </q-card-section>

          <q-card-section>
            <FormFilial v-model="model" :loading="loading" @submit="salvar" />
          </q-card-section>
        </q-card>
      </q-page>
    </template>
  </MGLayout>
</template>
