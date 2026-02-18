<script setup>
import { ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRouter } from "vue-router";
import { metaStore } from "src/stores/meta";
import { formataDataSemHora } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const router = useRouter();
const sMeta = metaStore();

// --- DIALOG NOVA META ---
const dialogNovaMeta = ref(false);
const loadingNovaMeta = ref(false);
const modelNovaMeta = ref({ periodoinicial: "", periodofinal: "" });

const formatDTLocal = (date, hours, minutes) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  const h = String(hours).padStart(2, "0");
  const min = String(minutes).padStart(2, "0");
  return `${y}-${m}-${d}T${h}:${min}`;
};

const abrirNovaMeta = () => {
  let pi = "";
  let pf = "";

  const ultima = sMeta.listagem[0];
  if (ultima?.periodofinal) {
    const partes = String(ultima.periodofinal).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (partes) {
      const lastEnd = new Date(
        parseInt(partes[1]),
        parseInt(partes[2]) - 1,
        parseInt(partes[3])
      );
      const nextDay = new Date(lastEnd);
      nextDay.setDate(nextDay.getDate() + 1);
      pi = formatDTLocal(nextDay, 0, 0);

      const endDate = new Date(
        nextDay.getFullYear(),
        nextDay.getMonth() + 1,
        25
      );
      pf = formatDTLocal(endDate, 23, 59);
    }
  }

  modelNovaMeta.value = { periodoinicial: pi, periodofinal: pf };
  dialogNovaMeta.value = true;
};

const criarMeta = async () => {
  loadingNovaMeta.value = true;
  try {
    const ret = await sMeta.criar(modelNovaMeta.value);
    const codmeta = ret.data.data.codmeta;
    dialogNovaMeta.value = false;
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Meta criada",
    });
    router.push({ name: "metaDashboard", params: { codmeta } });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao criar meta"),
    });
  } finally {
    loadingNovaMeta.value = false;
  }
};

const statusLabel = (status) => {
  switch (status) {
    case "A":
      return "Aberta";
    case "B":
      return "Bloqueada";
    case "F":
      return "Fechada";
    default:
      return status;
  }
};

const statusColor = (status) => {
  switch (status) {
    case "A":
      return "green";
    case "B":
      return "orange";
    case "F":
      return "grey";
    default:
      return "grey";
  }
};

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

const carregar = async () => {
  try {
    await sMeta.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar metas"),
    });
  }
};

const reprocessar = (meta) => {
  $q.dialog({
    title: "Reprocessar Meta",
    message:
      "Tem certeza que deseja reprocessar? Eventos automáticos serão recalculados.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.reprocessar(meta.codmeta);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Meta reprocessada",
      });
      await carregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao reprocessar"),
      });
    }
  });
};

const bloquear = (meta) => {
  $q.dialog({
    title: "Bloquear Meta",
    message:
      "Tem certeza que deseja bloquear? Novos lançamentos não serão aceitos.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.bloquear(meta.codmeta);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Meta bloqueada",
      });
      await carregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao bloquear"),
      });
    }
  });
};

const desbloquear = (meta) => {
  $q.dialog({
    title: "Desbloquear Meta",
    message:
      "Tem certeza que deseja desbloquear? A meta voltará a aceitar lançamentos.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.desbloquear(meta.codmeta);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Meta desbloqueada",
      });
      await carregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao desbloquear"),
      });
    }
  });
};

const finalizar = (meta) => {
  $q.dialog({
    title: "Finalizar Meta",
    message:
      "Tem certeza que deseja finalizar? A meta se tornará imutável.",
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.finalizar(meta.codmeta);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Meta finalizada",
      });
      await carregar();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao finalizar"),
      });
    }
  });
};

const excluir = (meta) => {
  $q.dialog({
    title: "Excluir Meta",
    message: "Tem certeza que deseja excluir esta meta?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sMeta.excluir(meta.codmeta);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Meta excluída",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir"),
      });
    }
  });
};

onMounted(() => {
  carregar();
});
</script>

<template>
  <!-- DIALOG NOVA META -->
  <q-dialog v-model="dialogNovaMeta">
    <q-card bordered flat style="width: 500px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        NOVA META
      </q-card-section>

      <q-form @submit="criarMeta()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input
                outlined
                v-model="modelNovaMeta.periodoinicial"
                label="Periodo Inicial"
                type="datetime-local"
                :rules="[(val) => !!val || 'Obrigatorio']"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                outlined
                v-model="modelNovaMeta.periodofinal"
                label="Periodo Final"
                type="datetime-local"
                :rules="[(val) => !!val || 'Obrigatorio']"
              />
            </div>
          </div>
          <div class="text-caption text-grey q-mt-sm">
            A configuracao (unidades, colaboradores, bonificacoes) sera
            copiada automaticamente da ultima meta.
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
            label="Criar Meta"
            type="submit"
            :loading="loadingNovaMeta"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout>
    <template #tituloPagina>
      <span class="q-pl-sm">Metas</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1100px; margin: auto" class="q-pa-md">
          <q-card bordered flat>
            <q-card-section
              class="text-grey-9 text-overline row items-center"
            >
              METAS
              <q-space />
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                @click="abrirNovaMeta()"
              >
                <q-tooltip>Nova Meta</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-markup-table
              flat
              separator="horizontal"
              v-if="sMeta.listagem.length > 0"
            >
              <thead>
                <tr class="text-left">
                  <th>Período Inicial</th>
                  <th>Período Final</th>
                  <th>Status</th>
                  <th class="text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="meta in sMeta.listagem"
                  :key="meta.codmeta"
                >
                  <td>{{ formataDataSemHora(meta.periodoinicial) }}</td>
                  <td>{{ formataDataSemHora(meta.periodofinal) }}</td>
                  <td>
                    <q-badge
                      :color="statusColor(meta.status)"
                      :label="statusLabel(meta.status)"
                    />
                  </td>
                  <td class="text-right">
                    <!-- DASHBOARD -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="dashboard"
                      size="sm"
                      color="grey-7"
                      :to="{
                        name: 'metaDashboard',
                        params: { codmeta: meta.codmeta },
                      }"
                    >
                      <q-tooltip>Dashboard</q-tooltip>
                    </q-btn>

                    <!-- REPROCESSAR -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="refresh"
                      size="sm"
                      color="grey-7"
                      @click="reprocessar(meta)"
                      v-if="meta.status === 'A' || meta.status === 'B'"
                    >
                      <q-tooltip>Reprocessar</q-tooltip>
                    </q-btn>

                    <!-- BLOQUEAR -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="lock"
                      size="sm"
                      color="orange-7"
                      @click="bloquear(meta)"
                      v-if="meta.status === 'A'"
                    >
                      <q-tooltip>Bloquear</q-tooltip>
                    </q-btn>

                    <!-- DESBLOQUEAR -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="lock_open"
                      size="sm"
                      color="grey-7"
                      @click="desbloquear(meta)"
                      v-if="meta.status === 'B'"
                    >
                      <q-tooltip>Desbloquear</q-tooltip>
                    </q-btn>

                    <!-- FINALIZAR -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="check_circle"
                      size="sm"
                      color="green-7"
                      @click="finalizar(meta)"
                      v-if="meta.status === 'B'"
                    >
                      <q-tooltip>Finalizar</q-tooltip>
                    </q-btn>

                    <!-- EXCLUIR -->
                    <q-btn
                      flat
                      dense
                      round
                      icon="delete"
                      size="sm"
                      color="grey-7"
                      @click="excluir(meta)"
                      v-if="meta.status !== 'F'"
                    >
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhuma meta cadastrada
            </div>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
