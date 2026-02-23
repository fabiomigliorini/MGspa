<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { feriadoStore } from "src/stores/feriado";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const sFeriado = feriadoStore();
const user = guardaToken();

// --- FILTRO ---

const filtroFeriado = ref("ativos");
const tabAno = ref(null);

const feriadosFiltrados = computed(() => {
  const lista = sFeriado.listagem || [];
  if (filtroFeriado.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

const anos = computed(() => {
  const set = new Set();
  feriadosFiltrados.value.forEach((f) => {
    if (f.data) set.add(f.data.substring(0, 4));
  });
  return Array.from(set).sort((a, b) => b - a);
});

const feriadosDoAno = computed(() => {
  if (!tabAno.value) return [];
  return feriadosFiltrados.value
    .filter((f) => f.data && f.data.substring(0, 4) === tabAno.value)
    .sort((a, b) => (a.data > b.data ? 1 : -1));
});

watch(anos, (val) => {
  if (val.length > 0 && !val.includes(tabAno.value)) {
    tabAno.value = val[0];
  }
});

// --- HELPERS ---

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

const parseData = (dataStr) => {
  if (!dataStr) return null;
  const iso = dataStr.substring(0, 10);
  return new Date(iso + "T12:00:00");
};

const formataDataCurta = (dataStr) => {
  const d = parseData(dataStr);
  if (!d) return "";
  return d.toLocaleDateString("pt-BR", { day: "2-digit", month: "short" });
};

const diaSemana = (dataStr) => {
  const d = parseData(dataStr);
  if (!d) return "";
  return d.toLocaleDateString("pt-BR", { weekday: "short" });
};

const formataDataMovel = (dataStr) => {
  const d = parseData(dataStr);
  if (!d) return "";
  return d.toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

// --- DIALOG ---

const dialogFeriado = ref(false);
const isNovo = ref(false);
const modelFeriado = ref({});

const abrirNovo = () => {
  modelFeriado.value = { data: "", feriado: "" };
  isNovo.value = true;
  dialogFeriado.value = true;
};

const editar = (feriado) => {
  modelFeriado.value = {
    codferiado: feriado.codferiado,
    data: feriado.data ? feriado.data.substring(0, 10) : "",
    feriado: feriado.feriado,
  };
  isNovo.value = false;
  dialogFeriado.value = true;
};

const submit = async () => {
  dialogFeriado.value = false;
  try {
    if (isNovo.value) {
      await sFeriado.criar(modelFeriado.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Feriado criado",
      });
    } else {
      await sFeriado.atualizar(
        modelFeriado.value.codferiado,
        modelFeriado.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Feriado alterado",
      });
    }
    await sFeriado.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar feriado"),
    });
  }
};

const excluir = (feriado) => {
  $q.dialog({
    title: "Excluir Feriado",
    message: 'Tem certeza que deseja excluir "' + feriado.feriado + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sFeriado.excluir(feriado.codferiado);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Feriado excluído",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir feriado"),
      });
    }
  });
};

const inativar = async (feriado) => {
  try {
    await sFeriado.inativar(feriado.codferiado);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao inativar"),
    });
  }
};

const ativar = async (feriado) => {
  try {
    await sFeriado.ativar(feriado.codferiado);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao ativar"),
    });
  }
};

// --- DIALOG GERAR ANO ---

const dialogGerarAno = ref(false);
const anoGerar = ref(new Date().getFullYear() + 1);
const gerandoAno = ref(false);
const resultadoGerar = ref(null);

const abrirGerarAno = () => {
  anoGerar.value = new Date().getFullYear() + 1;
  resultadoGerar.value = null;
  dialogGerarAno.value = true;
};

const submitGerarAno = async () => {
  gerandoAno.value = true;
  try {
    const ret = await sFeriado.gerarAno(anoGerar.value);
    resultadoGerar.value = ret.data;
    await sFeriado.getListagem();
    tabAno.value = String(anoGerar.value);
  } catch (error) {
    const data = error.response?.data;
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: data?.erro || extrairErro(error, "Erro ao gerar feriados"),
    });
  } finally {
    gerandoAno.value = false;
  }
};

// --- LIFECYCLE ---

onMounted(async () => {
  try {
    await sFeriado.getListagem();
    if (anos.value.length > 0) {
      tabAno.value = anos.value[0];
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar feriados"),
    });
  }
});
</script>

<template>
  <!-- DIALOG FERIADO -->
  <q-dialog v-model="dialogFeriado">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline">
          <template v-if="isNovo">NOVO FERIADO</template>
          <template v-else>EDITAR FERIADO</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="modelFeriado.data"
                label="Data"
                autofocus
                type="date"
                :rules="[(val) => !!val || 'Data obrigatória']"
              />
            </div>
            <div class="col-12">
              <q-input
                outlined
                v-model="modelFeriado.feriado"
                label="Descrição"
                :rules="[
                  (val) => (val && val.length > 0) || 'Descrição obrigatória',
                ]"
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
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG GERAR ANO -->
  <q-dialog v-model="dialogGerarAno">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitGerarAno()">
        <q-card-section class="text-grey-9 text-overline">
          GERAR FERIADOS
        </q-card-section>

        <q-separator inset />

        <q-card-section v-if="!resultadoGerar">
          <div class="text-body2 q-mb-md">
            Duplica os feriados do ano anterior e atualiza as datas dos feriados
            móveis via BrasilAPI.
          </div>
          <q-input
            outlined
            v-model.number="anoGerar"
            label="Ano"
            type="number"
            :min="2015"
            autofocus
          />
        </q-card-section>

        <q-card-section v-else>
          <div class="text-body2 q-mb-sm">
            <q-icon name="done" color="green" class="q-mr-xs" />
            {{ resultadoGerar.duplicados }} feriados duplicados do ano anterior
            para {{ resultadoGerar.ano }}
          </div>

          <template v-if="resultadoGerar.moveis_atualizados > 0">
            <div class="text-body2 q-mb-sm">
              <q-icon name="update" color="orange" class="q-mr-xs" />
              {{ resultadoGerar.moveis_atualizados }} feriados móveis
              atualizados:
            </div>
            <q-list dense>
              <q-item v-for="(m, i) in resultadoGerar.moveis" :key="i">
                <q-item-section>
                  <q-item-label>{{ m.feriado }}</q-item-label>
                  <q-item-label caption>
                    {{ formataDataMovel(m.data_anterior) }}
                    →
                    {{ formataDataMovel(m.data_nova) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <div
            v-else-if="resultadoGerar.moveis_atualizados === 0"
            class="text-body2 text-grey"
          >
            Nenhum feriado móvel precisou ser atualizado.
          </div>

          <q-banner
            v-if="
              resultadoGerar.preexistentes &&
              resultadoGerar.preexistentes.length > 0
            "
            class="bg-orange-1 text-orange-9 q-mt-md"
            rounded
          >
            <template v-slot:avatar>
              <q-icon name="warning" color="orange" />
            </template>
            {{ resultadoGerar.preexistentes.length }} feriado(s) não
            reconhecido(s) já existiam no ano:
            <q-list dense class="q-mt-xs">
              <q-item
                v-for="p in resultadoGerar.preexistentes"
                :key="p.codferiado"
                dense
              >
                <q-item-section>
                  <q-item-label class="text-orange-9">
                    {{ p.feriado }} — {{ formataDataMovel(p.data) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-banner>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Fechar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
            v-if="resultadoGerar"
          />
          <template v-else>
            <q-btn
              flat
              label="Cancelar"
              v-close-popup
              tabindex="-1"
              color="grey-8"
            />
            <q-btn flat label="Gerar" type="submit" :loading="gerandoAno" />
          </template>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout>
    <template #tituloPagina>
      <span class="q-pl-sm">Feriados</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 600px; margin: auto" class="q-pa-md">
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline row items-center">
              FERIADOS
              <q-space />
              <q-btn-toggle
                v-model="filtroFeriado"
                color="grey-3"
                toggle-color="primary"
                text-color="grey-7"
                toggle-text-color="grey-3"
                unelevated
                dense
                no-caps
                size="sm"
                :options="[
                  { label: 'Ativos', value: 'ativos' },
                  { label: 'Todos', value: 'todos' },
                ]"
              />
              <q-btn
                flat
                round
                dense
                icon="auto_awesome"
                size="sm"
                color="grey-7"
                @click="abrirGerarAno()"
              >
                <q-tooltip>Gerar Feriados do Ano</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                @click="abrirNovo()"
              >
                <q-tooltip>Novo Feriado</q-tooltip>
              </q-btn>
            </q-card-section>

            <template v-if="anos.length > 0">
              <q-tabs
                v-model="tabAno"
                dense
                active-color="primary"
                indicator-color="primary"
                align="left"
                narrow-indicator
              >
                <q-tab
                  v-for="ano in anos"
                  :key="ano"
                  :name="ano"
                  :label="ano"
                />
              </q-tabs>

              <q-separator />

              <q-list v-if="feriadosDoAno.length > 0">
                <template
                  v-for="feriado in feriadosDoAno"
                  :key="feriado.codferiado"
                >
                  <q-separator inset />
                  <q-item>
                    <q-item-section avatar>
                      <div class="text-center">
                        <div class="text-subtitle2 text-primary">
                          {{ formataDataCurta(feriado.data) }}
                        </div>
                        <div class="text-caption text-grey">
                          {{ diaSemana(feriado.data) }}
                        </div>
                      </div>
                    </q-item-section>

                    <q-item-section>
                      <q-item-label
                        :class="feriado.inativo ? 'text-strike' : ''"
                      >
                        {{ feriado.feriado }}
                      </q-item-label>
                      <q-item-label
                        caption
                        class="text-red-14"
                        v-if="feriado.inativo"
                      >
                        Inativo desde: {{ formataData(feriado.inativo) }}
                      </q-item-label>
                    </q-item-section>

                    <q-item-section side>
                      <q-item-label caption>
                        <q-btn
                          flat
                          dense
                          round
                          icon="edit"
                          size="sm"
                          color="grey-7"
                          @click="editar(feriado)"
                        >
                          <q-tooltip>Editar</q-tooltip>
                        </q-btn>
                        <q-btn
                          v-if="!feriado.inativo"
                          flat
                          dense
                          round
                          icon="pause"
                          size="sm"
                          color="grey-7"
                          @click="inativar(feriado)"
                        >
                          <q-tooltip>Inativar</q-tooltip>
                        </q-btn>
                        <q-btn
                          v-if="feriado.inativo"
                          flat
                          dense
                          round
                          icon="play_arrow"
                          size="sm"
                          color="grey-7"
                          @click="ativar(feriado)"
                        >
                          <q-tooltip>Ativar</q-tooltip>
                        </q-btn>
                        <q-btn
                          flat
                          dense
                          round
                          icon="delete"
                          size="sm"
                          color="grey-7"
                          @click="excluir(feriado)"
                        >
                          <q-tooltip>Excluir</q-tooltip>
                        </q-btn>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-list>
            </template>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum feriado cadastrado
            </div>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
