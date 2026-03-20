<script setup>
import { ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRouter, useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { formataDataSemHora } from "src/utils/formatador";
import { extrairErro } from "src/utils/rhFormatters";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const router = useRouter();
const route = useRoute();
const sRh = rhStore();

const periodos = ref([]);
const loading = ref(false);

const statusLabel = (status) => (status === "A" ? "Aberto" : "Fechado");
const statusColor = (status) => (status === "A" ? "green" : "grey");

const periodoSelecionado = (codperiodo) =>
  String(route.params.codperiodo) === String(codperiodo);

const selecionarPeriodo = (codperiodo) => {
  router.push({ name: "rhMeuPainelDashboard", params: { codperiodo } });
};

const carregar = async () => {
  loading.value = true;
  try {
    periodos.value = await sRh.getMeuPainelPeriodos();
    if (!route.params.codperiodo && periodos.value.length > 0) {
      const aberto = periodos.value.find((p) => p.status === "A");
      const periodo = aberto || periodos.value[0];
      router.replace({
        name: "rhMeuPainelDashboard",
        params: { codperiodo: periodo.codperiodo },
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar períodos"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregar();
});
</script>

<template>
  <MGLayout drawer>
    <template #tituloPagina>
      <span class="q-pl-sm">Meu Painel</span>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>Períodos</q-item-label>
          </q-list>
        </q-card>

        <q-list separator v-if="periodos.length > 0">
          <q-item
            v-for="periodo in periodos"
            :key="periodo.codperiodo"
            clickable
            v-ripple
            :active="periodoSelecionado(periodo.codperiodo)"
            active-class="bg-primary text-white"
            @click="selecionarPeriodo(periodo.codperiodo)"
          >
            <q-item-section>
              <q-item-label>
                {{ formataDataSemHora(periodo.periodoinicial) }} a
                {{ formataDataSemHora(periodo.periodofinal) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge
                :color="periodoSelecionado(periodo.codperiodo) ? 'white' : statusColor(periodo.status)"
                :text-color="periodoSelecionado(periodo.codperiodo) ? 'primary' : 'white'"
                :label="statusLabel(periodo.status)"
              />
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else-if="!loading" class="q-pa-md text-center text-grey">
          Nenhum período encontrado
        </div>
      </div>
    </template>

    <template #content>
      <q-page v-if="periodos.length > 0 && route.params.codperiodo">
        <router-view />
      </q-page>
      <q-page v-else-if="!loading && periodos.length === 0" class="flex flex-center">
        <div class="text-center">
          <q-icon name="assignment_ind" size="64px" color="grey-5" />
          <div class="text-h6 text-grey-7 q-mt-md">
            Você não está vinculado a nenhum período
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
