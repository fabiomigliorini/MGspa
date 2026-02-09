<script>
import { ref, onMounted, defineAsyncComponent, watch } from "vue";
import { empresaStore } from "src/stores/empresa";
import { debounce, useQuasar } from "quasar";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    CardEmpresa: defineAsyncComponent(() =>
      import("components/empresa/CardEmpresa.vue")
    ),
    FiltroEmpresa: defineAsyncComponent(() =>
      import("components/empresa/FiltroEmpresa.vue")
    ),
  },

  setup() {
    const sEmpresa = empresaStore();
    const $q = useQuasar();
    const loading = ref(false);
    const acabouDados = ref(false);

    const buscarEmpresas = async () => {
      loading.value = true;
      acabouDados.value = false;
      sEmpresa.filtroPesquisa.page = 1;
      try {
        const ret = await sEmpresa.buscarEmpresas();
        // Se retornou menos que per_page, acabou os dados
        const qtdRetornada = ret.data.data ? ret.data.data.length : 0;
        if (qtdRetornada < sEmpresa.filtroPesquisa.per_page) {
          acabouDados.value = true;
        }
      } catch (error) {
        $q.notify({
          color: "red-5",
          textColor: "white",
          icon: "error",
          message: "Erro ao buscar empresas",
        });
      } finally {
        loading.value = false;
      }
    };

    const buscarEmpresasDebounce = debounce(() => {
      buscarEmpresas();
    }, 500);

    watch(
      () => sEmpresa.filtroPesquisa.empresa,
      () => buscarEmpresasDebounce()
    );

    watch(
      () => sEmpresa.filtroPesquisa.codempresa,
      () => buscarEmpresasDebounce()
    );

    onMounted(() => {
      buscarEmpresas();
    });

    const scrollInfinito = async (index, done) => {
      if (acabouDados.value) {
        done(true);
        return;
      }
      sEmpresa.filtroPesquisa.page++;
      try {
        const ret = await sEmpresa.buscarEmpresas();
        // Se retornou menos que per_page ou zero, acabou
        const qtdRetornada = ret.data.data ? ret.data.data.length : 0;
        if (qtdRetornada < sEmpresa.filtroPesquisa.per_page) {
          acabouDados.value = true;
          done(true);
        } else {
          done();
        }
      } catch (error) {
        acabouDados.value = true;
        done(true);
      }
    };

    return {
      sEmpresa,
      loading,
      acabouDados,
      buscarEmpresas,
      scrollInfinito,
    };
  },
};
</script>

<template>
  <MGLayout drawer>
    <template #tituloPagina>Empresas</template>

    <template #drawer>
      <FiltroEmpresa @limpar="buscarEmpresas" />
    </template>

    <template #content>
      <q-page padding>
        <q-inner-loading :showing="loading">
          <q-spinner-gears size="50px" color="primary" />
        </q-inner-loading>

        <q-infinite-scroll
          @load="scrollInfinito"
          :disable="loading || acabouDados"
        >
          <div class="row q-col-gutter-md">
            <div
              class="col-12 col-md-6 col-lg-4"
              v-for="empresa in sEmpresa.empresas"
              :key="empresa.codempresa"
            >
              <CardEmpresa :empresa="empresa" />
            </div>
          </div>

          <template v-slot:loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="40px" />
            </div>
          </template>
        </q-infinite-scroll>

        <div
          v-if="!loading && sEmpresa.empresas.length === 0"
          class="text-center q-pa-lg"
        >
          <q-icon name="business" size="64px" color="grey" />
          <div class="text-h6 text-grey q-mt-md">
            Nenhuma empresa encontrada
          </div>
        </div>

        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab icon="add" color="primary" to="/empresa/nova" />
        </q-page-sticky>
      </q-page>
    </template>
  </MGLayout>
</template>
