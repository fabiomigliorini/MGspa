<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { cargoStore } from "src/stores/cargo";
import { pessoaStore } from "src/stores/pessoa";
import { formataData, formataDataSemHora } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import DialogCargo from "components/cargo/DialogCargo.vue";

const $q = useQuasar();
const sCargo = cargoStore();
const sPessoa = pessoaStore();
const route = useRoute();
const router = useRouter();

// --- REFS ---

const cargo = ref({});
const pessoasCargo = ref([]);

const atuais = computed(() => pessoasCargo.value.filter((p) => !p.fim));

const anteriores = computed(() => pessoasCargo.value.filter((p) => p.fim));

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

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(valor || 0);
};

// --- DIALOG ---

const dialogCargoRef = ref(null);

const onSubmit = async (model) => {
  try {
    await sCargo.atualizar(model.codcargo, model);
    cargo.value = { ...cargo.value, ...model };
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Cargo alterado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar cargo"),
    });
  }
};

// --- CRUD ---

const excluir = () => {
  $q.dialog({
    title: "Excluir Cargo",
    message: 'Tem certeza que deseja excluir "' + cargo.value.cargo + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sCargo.excluir(cargo.value.codcargo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Cargo excluído",
      });
      router.push("/cargo");
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir cargo"),
      });
    }
  });
};

const inativar = async () => {
  try {
    const ret = await sCargo.inativar(cargo.value.codcargo);
    cargo.value = ret.data.data;
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

const ativar = async () => {
  try {
    const ret = await sCargo.ativar(cargo.value.codcargo);
    cargo.value = ret.data.data;
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

// --- LIFECYCLE ---

onMounted(async () => {
  try {
    const ret = await sPessoa.pessoasColaboradorCargo(route.params.id);
    pessoasCargo.value = ret.data.data.pessoasCargo;
    cargo.value = ret.data.data.cargoS;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar cargo"),
    });
  }
});
</script>

<template>
  <DialogCargo ref="dialogCargoRef" @submit="onSubmit" />

  <MGLayout back-button>
    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{ name: 'cargosindex' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #tituloPagina>
      <span class="q-pl-sm">{{ cargo.cargo }}</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1000px; margin: auto" class="q-pa-md">
          <!-- CARD DO CARGO -->
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline row items-center">
              <span :class="cargo.inativo ? 'text-strike' : ''">
                {{ cargo.cargo }}
              </span>
              <q-space />
              <span class="text-caption text-grey-5 q-mr-sm">
                {{ formataMoeda(cargo.salario) }}
                <span v-if="cargo.adicional">
                  — {{ cargo.adicional }}% adicional
                </span>
              </span>
              <IconeInfoCriacao
                :usuariocriacao="cargo.usuariocriacao"
                :criacao="cargo.criacao"
                :usuarioalteracao="cargo.usuarioalteracao"
                :alteracao="cargo.alteracao"
              />
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="dialogCargoRef.editar(cargo)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!cargo.inativo"
                flat
                dense
                round
                icon="pause"
                size="sm"
                color="grey-7"
                @click="inativar()"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="cargo.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="sm"
                color="grey-7"
                @click="ativar()"
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
                @click="excluir()"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-item-label
              caption
              class="text-red-14 q-px-md q-pb-sm"
              v-if="cargo.inativo"
            >
              Inativo desde: {{ formataData(cargo.inativo) }}
            </q-item-label>
          </q-card>

          <!-- CARD COLABORADORES ATUAIS -->
          <q-card bordered flat class="q-mt-md q-pb-md">
            <q-card-section class="text-grey-9 text-overline row items-center">
              COLABORADORES ATUAIS ({{ atuais.length }})
            </q-card-section>

            <q-list v-if="atuais.length > 0">
              <template
                v-for="pessoa in atuais"
                :key="pessoa.codcolaboradorcargo"
              >
                <q-separator inset />
                <q-item clickable :to="'/pessoa/' + pessoa.codpessoa">
                  <q-item-section avatar>
                    <q-avatar color="primary" text-color="white" size="35px">
                      {{ pessoa.fantasia?.charAt(0) }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label>
                      {{ pessoa.fantasia }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ pessoa.filial }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-item-label caption>
                      {{ formataDataSemHora(pessoa.inicio) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum colaborador neste cargo
            </div>
          </q-card>

          <!-- CARD COLABORADORES ANTERIORES -->
          <q-card
            v-if="anteriores.length > 0"
            bordered
            flat
            class="q-mt-md q-pb-md"
          >
            <q-card-section class="text-grey-9 text-overline row items-center">
              ANTERIORES ({{ anteriores.length }})
            </q-card-section>

            <q-list>
              <template
                v-for="pessoa in anteriores"
                :key="pessoa.codcolaboradorcargo"
              >
                <q-separator inset />
                <q-item clickable :to="'/pessoa/' + pessoa.codpessoa">
                  <q-item-section avatar>
                    <q-avatar color="grey-4" text-color="white" size="35px">
                      {{ pessoa.fantasia?.charAt(0) }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-grey">
                      {{ pessoa.fantasia }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ pessoa.filial }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-item-label caption>
                      {{ formataDataSemHora(pessoa.inicio) }}
                      a {{ formataDataSemHora(pessoa.fim) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
