<script setup>
import { ref, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRouter, useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { guardaToken } from "src/stores";
import { formataDataSemHora } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const router = useRouter();
const route = useRoute();
const sRh = rhStore();
const user = guardaToken();

// --- DIALOG NOVO PERÍODO ---
const dialogNovoPeriodo = ref(false);
const loadingNovoPeriodo = ref(false);
const modelNovoPeriodo = ref({
  periodoinicial: "",
  periodofinal: "",
  observacoes: "",
});

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

const statusLabel = (status) => {
  return status === "A" ? "Aberto" : "Fechado";
};

const statusColor = (status) => {
  return status === "A" ? "green" : "grey";
};

const formatDTLocal = (date, time = "00:00") => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}T${time}`;
};

const abrirNovoPeriodo = () => {
  let pi = "";
  let pf = "";

  const ultimo = sRh.periodos[0];
  if (ultimo?.periodofinal) {
    const partes = String(ultimo.periodofinal).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (partes) {
      const lastEnd = new Date(
        parseInt(partes[1]),
        parseInt(partes[2]) - 1,
        parseInt(partes[3])
      );
      const nextDay = new Date(lastEnd);
      nextDay.setDate(nextDay.getDate() + 1);
      pi = formatDTLocal(nextDay, "00:00");

      const endDate = new Date(
        nextDay.getFullYear(),
        nextDay.getMonth() + 1,
        25
      );
      pf = formatDTLocal(endDate, "23:59");
    }
  }

  modelNovoPeriodo.value = {
    periodoinicial: pi,
    periodofinal: pf,
    observacoes: "",
  };
  dialogNovoPeriodo.value = true;
};

const criarPeriodo = async () => {
  loadingNovoPeriodo.value = true;
  try {
    const ret = await sRh.criarPeriodo(modelNovoPeriodo.value);
    dialogNovoPeriodo.value = false;
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Período criado",
    });
    await sRh.getPeriodos();
    const codperiodo = ret.data.data.codperiodo;
    router.push({ name: "rhDashboard", params: { codperiodo } });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao criar período"),
    });
  } finally {
    loadingNovoPeriodo.value = false;
  }
};

const periodoSelecionado = (codperiodo) => {
  return String(route.params.codperiodo) === String(codperiodo);
};

const selecionarPeriodo = (codperiodo) => {
  router.push({ name: "rhDashboard", params: { codperiodo } });
};

const carregar = async () => {
  try {
    await sRh.getPeriodos();
    if (!route.params.codperiodo && sRh.periodos.length > 0) {
      const aberto = sRh.periodos.find((p) => p.status === "A");
      const periodo = aberto || sRh.periodos[0];
      router.replace({
        name: "rhDashboard",
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
  }
};

onMounted(() => {
  carregar();
});
</script>

<template>
  <!-- DIALOG NOVO PERÍODO -->
  <q-dialog v-model="dialogNovoPeriodo">
    <q-card bordered flat style="width: 500px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        NOVO PERÍODO
      </q-card-section>

      <q-form @submit="criarPeriodo()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input
                outlined
                v-model="modelNovoPeriodo.periodoinicial"
                label="Período Inicial"
                type="datetime-local"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                outlined
                v-model="modelNovoPeriodo.periodofinal"
                label="Período Final"
                type="datetime-local"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <q-input
                outlined
                v-model="modelNovoPeriodo.observacoes"
                label="Observações"
                type="textarea"
                autogrow
                input-style="min-height: 3em"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn
            flat
            label="Criar Período"
            type="submit"
            :loading="loadingNovoPeriodo"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout drawer>
    <template #tituloPagina>
      <span class="q-pl-sm">Metas &amp; Variáveis</span>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header class="row items-center">
              Períodos
              <q-space />
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                v-if="user.verificaPermissaoUsuario('Recursos Humanos')"
                @click="abrirNovoPeriodo()"
              >
                <q-tooltip>Novo Período</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-list>
        </q-card>

        <q-list separator v-if="sRh.periodos.length > 0">
          <q-item
            v-for="periodo in sRh.periodos"
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
              <q-item-label caption :class="periodoSelecionado(periodo.codperiodo) ? 'text-white' : ''">
                {{ periodo.total_colaboradores || 0 }} colaboradores
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
        <div v-else class="q-pa-md text-center text-grey">
          Nenhum período cadastrado
        </div>
      </div>
    </template>

    <template #content>
      <q-page v-if="sRh.periodos.length > 0 && route.params.codperiodo">
        <router-view />
      </q-page>
      <q-page v-else-if="sRh.periodos.length === 0" class="flex flex-center">
        <div class="text-center">
          <q-icon name="event_note" size="64px" color="grey-5" />
          <div class="text-h6 text-grey-7 q-mt-md">
            Nenhum período cadastrado
          </div>
          <q-btn
            v-if="user.verificaPermissaoUsuario('Recursos Humanos')"
            color="primary"
            label="Criar Primeiro Período"
            class="q-mt-md"
            @click="abrirNovoPeriodo()"
          />
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
