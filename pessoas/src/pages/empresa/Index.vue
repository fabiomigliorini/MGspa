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
      <FiltroEmpresa @buscar="buscarEmpresas()" />
    </template>

    <template #content>
      <q-infinite-scroll
        @load="scrollInfinito"
        :disable="loading || acabouDados"
        style="min-height: 100vh"
      >
        <div class="row q-pa-md q-col-gutter-md">
          <div
            class="col-xs-12 col-sm-6 col-md-4 col-lg-4 col-xl-3"
            v-for="empresa in sEmpresa.empresas"
            :key="empresa.codempresa"
          >
            <CardEmpresa :empresa="empresa" />
          </div>
        </div>
      </q-infinite-scroll>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="add" color="accent" to="/empresa/nova" />
      </q-page-sticky>
    </template>
  </MGLayout>
</template>
