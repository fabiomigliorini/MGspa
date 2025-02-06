<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { Notify } from "quasar";
import { db } from "boot/db";
import { sincronizacaoStore } from "src/stores/sincronizacao";
import SelectFilial from "src/components/selects/SelectFilial.vue";
import { negocioStore } from "src/stores/negocio";
import moment from "moment/min/moment-with-locales";
import { api } from "boot/axios";
import qrcode from "qrcode";
moment.locale("pt-br");

const sSinc = sincronizacaoStore();
const sNegocios = negocioStore();

const openModalCadastro = ref(false);
const openModalEditCadastro = ref(false);
const confirm = ref(false);
const inputApelido = ref();
const isValid = ref(false);
const listaPos = ref([]);
const pdv_uuid = ref("");
const codsauruspdv = ref("");

const apelido = ref("");
const filial = ref("");
const contrato = ref("");
const qrCodeBasae64 = ref("");
const loading = ref(false);

const ruleApelido = [
  (v) => !!v || "Apelido é obrigatório",
  (v) => (v && v.length <= 50) || "Máximo de 50 caracteres",
  (v) => (v && v.length >= 3) || "Mínimo de 3 caracteres",
];

const ruleContrato = [
  (v) => !!v || "Contrato é obrigatório",
  (v) => (v && v.length >= 3) || "Mínimo de 3 caracteres",
];

const ruleFilial = [(v) => !!v || "Filial é obrigatório"];

const cadastrarMaquineta = async () => {
  openModalCadastro.value = true;
};

const gerarQrCode = async () => {
  loading.value = true;
  isValid.value = true;
  await api
    .post("api/v1/pdv/saurus/registrar-pos", {
      apelido: apelido.value,
      codfilial: filial.value,
      contrato: contrato.value,
      pdv_uuid: pdv_uuid.value || null,
    })
    .then(async ({ data }) => {
      pdv_uuid.value = data.pdvsaurus.id;

      qrCodeBasae64.value = await qrcode.toDataURL(
        data.pdvsaurus.chavepublica,
        {
          errorCorrectionLevel: "H",
          width: 200,
        }
      );

      Notify.create({
        type: "positive",
        message: "QrCode gerado com sucesso!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
      loading.value = false;
    })
    .catch((error) => {
      Notify.create({
        type: "negative",
        message: "Falha ao gerar QrCode, tente novamente!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
      loading.value = false;
      isValid.value = false;
    });
};

const checkLeitura = async () => {
  loading.value = true;
  await api
    .post("api/v1/pdv/saurus/verificar-leitura", {
      pdv_uuid: pdv_uuid.value,
    })
    .then(async ({ data }) => {
      if (data.success) {
        Notify.create({
          type: "positive",
          message: "Leitura realizada com sucesso!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });

        await sSinc.silentSincronizarEstoqueLocal();
        await getPinpads();

        loading.value = false;
        openModalCadastro.value = false;
      } else {
        this.checkLeitura();
      }
    })
    .catch((error) => {
      Notify.create({
        type: "negative",
        message: "Falha ao verificar leitura, tente novamente!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
      loading.value = false;
    });
};

const checkValidate = computed(() => {
  if (
    apelido.value.length < 3 ||
    apelido.value.length > 50 ||
    !filial.value ||
    !contrato.value
  ) {
    return true;
  }
  return false;
});

const getPinpads = async () => {
  await api.get("api/v1/pdv/saurus/pdvs").then(({ data }) => {
    listaPos.value = data;
  });
};

const editarMaquineta = (pdv) => {
  openModalEditCadastro.value = true;
  codsauruspdv.value = pdv.codsauruspdv;
  apelido.value = pdv.apelido;
  filial.value = pdv.codfilial;
  pdv_uuid.value = pdv.id;
  contrato.value = pdv.contratoid;
};

const salvarMaquineta = async () => {
  loading.value = true;
  await api
    .post("api/v1/pdv/saurus/pdv/" + codsauruspdv.value, {
      apelido: apelido.value,
      codfilial: filial.value,
    })
    .then(async ({ data }) => {
      Notify.create({
        type: "positive",
        message: "Maquineta salva com sucesso!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });

      loading.value = false;
      openModalEditCadastro.value = false;
      getPinpads();
    })
    .catch((error) => {
      Notify.create({
        type: "negative",
        message: "Falha ao salvar maquineta, tente novamente!",
        timeout: 3000, // 3 segundos
        actions: [{ icon: "close", color: "white" }],
      });
      loading.value = false;
    });
};

const inativarMaquineta = async (pdv) => {
  confirm.value = await Notify.create({
    message: "Deseja realmente inativar a maquineta?",
    position: "center",
    timeout: 0,
    actions: [
      {
        label: "Sim",
        color: "white",
        handler: async () => {
          await api
            .get("api/v1/pdv/saurus/pdv/" + pdv.codsauruspdv + "/inativar")
            .then(async ({ data }) => {
              Notify.create({
                type: "positive",
                message: "Maquineta inativada com sucesso!",
                timeout: 3000, // 3 segundos
                actions: [{ icon: "close", color: "white" }],
              });

              getPinpads();
            })
            .catch((error) => {
              Notify.create({
                type: "negative",
                message: "Falha ao inativar maquineta, tente novamente!",
                timeout: 3000, // 3 segundos
                actions: [{ icon: "close", color: "white" }],
              });
            });
        },
      },
      {
        label: "Não",
        color: "white",
        handler: () => {
          confirm.value = false;
        },
      },
    ],
  });
};

const ativarMaquineta = async (pdv) => {
  confirm.value = await Notify.create({
    message: "Deseja realmente ativar a maquineta?",
    position: "center",
    timeout: 0,
    actions: [
      {
        label: "Sim",
        color: "white",
        handler: async () => {
          await api
            .get("api/v1/pdv/saurus/pdv/" + pdv.codsauruspdv + "/ativar")
            .then(async ({ data }) => {
              Notify.create({
                type: "positive",
                message: "Maquineta ativada com sucesso!",
                timeout: 3000, // 3 segundos
                actions: [{ icon: "close", color: "white" }],
              });

              getPinpads();
            })
            .catch((error) => {
              Notify.create({
                type: "negative",
                message: "Falha ao ativar maquineta, tente novamente!",
                timeout: 3000, // 3 segundos
                actions: [{ icon: "close", color: "white" }],
              });
            });
        },
      },
      {
        label: "Não",
        color: "white",
        handler: () => {
          confirm.value = false;
        },
      },
    ],
  });
};

const definirPinPad = async (pdv) => {
  codsauruspdv.value = pdv.codsauruspdv;
  apelido.value = pdv.apelido;
  filial.value = pdv.codfilial;
  pdv_uuid.value = pdv.id;
  contrato.value = pdv.contratoid;
  openModalCadastro.value = true;
};

watch(openModalCadastro, (value) => {
  if (!value) {
    qrCodeBasae64.value = "";
    apelido.value = "";
    filial.value = "";
    contrato.value = "";
    pdv_uuid.value = "";
    isValid.value = false;
  }
});

watch(openModalEditCadastro, (value) => {
  if (!value) {
    codsauruspdv.value = "";
    apelido.value = "";
    filial.value = "";
    contrato.value = "";
    pdv_uuid.value = "";
    isValid.value = false;
  }
});

onMounted(() => {
  getPinpads();
});
</script>
<template>
  <q-page>
    <div class="row justify-center q-mt-md">
      <q-btn
        color="primary"
        label="Cadastrar Maquineta"
        @click="cadastrarMaquineta()"
      />

      <q-dialog v-model="openModalCadastro" persistent>
        <q-card>
          <q-card-section
            class="row items-center justify-center"
            style="min-width: 400px"
          >
            <select-filial
              outlined
              v-model="filial"
              label="Filial"
              clearable
              :disable="isValid"
              class="col-12"
              :rules="ruleFilial"
            />

            <!-- //input apelido -->
            <q-input
              v-model="apelido"
              ref="inputApelido"
              outlined
              label="Apelido"
              class="col-12"
              maxlength="50"
              :rules="ruleApelido"
              :disable="isValid"
            />

            <!-- input contrato -->
            <q-input
              v-model="contrato"
              ref="inputContrato"
              outlined
              label="Contrato"
              class="col-12"
              maxlength="50"
              :rules="ruleContrato"
              :disable="isValid"
            />

            <!-- show qrcode -->
            <div
              v-if="qrCodeBasae64"
              class="row items-center justify-center"
              style="
                margin-top: 20px;
                width: 215px;
                height: 215px;
                border: 3px solid #111;
                border-radius: 10px;
              "
            >
              <img
                v-if="qrCodeBasae64"
                :src="qrCodeBasae64"
                style="width: 200px; height: 200px"
              />
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn outline label="Cancelar" color="red" v-close-popup />
            <q-btn
              v-if="!qrCodeBasae64"
              label="Gerar QR Code"
              color="primary"
              @click="gerarQrCode()"
              :loading="loading"
              :disabled="checkValidate"
            />
            <q-btn
              v-else
              label="Verificar Leitura"
              color="primary"
              @click="checkLeitura()"
              :loading="loading"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <q-dialog v-model="openModalEditCadastro" persistent>
        <q-card>
          <q-card-section
            class="row items-center justify-center"
            style="min-width: 400px"
          >
            <select-filial
              outlined
              v-model="filial"
              label="Filial"
              clearable
              :disable="isValid"
              class="col-12"
              :rules="ruleFilial"
            />

            <!-- //input apelido -->
            <q-input
              v-model="apelido"
              ref="inputApelido"
              outlined
              label="Apelido"
              class="col-12"
              maxlength="50"
              :rules="ruleApelido"
              :disable="isValid"
            />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn outline label="Cancelar" color="red" v-close-popup />
            <q-btn
              label="Salvar"
              color="primary"
              @click="salvarMaquineta()"
              :loading="loading"
              :disabled="checkValidate"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>

    <div class="row q-pa-md q-col-gutter-md q-pb-xl">
      <template v-for="pdv in listaPos" :key="pdv.id">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-3">
          <q-card
            class="my-card"
            :class="pdv.inativo ? 'bg-red-1' : ''"
            flat
            bordered
          >
            <q-card-section>
              <div
                class="absolute"
                style="top: 0; right: 12px; transform: translateY(15%)"
              >
                <q-icon
                  name="check_circle"
                  color="green"
                  size="30px"
                  v-if="!pdv.inativo"
                />
                <q-icon name="cancel" color="red" size="30px" v-else />
              </div>
              <div class="row no-wrap items-center">
                <div class="text-h6 q-mr-sm">
                  <q-icon
                    name="fax"
                    color="purple"
                    size="32px"
                    style="rotate: 90deg"
                  />
                </div>
                <div>
                  <div class="row no-wrap items-center q-mb-sm">
                    <div class="col text-h6 ellipsis">
                      {{ pdv.apelido }}
                    </div>
                  </div>
                  <q-separator />
                  <div
                    class="col text-h6 text-black q-mt-sm"
                    v-if="pdv.saurus_pin_pad_s.length > 0"
                  >
                    PinPad
                  </div>
                  <div
                    class="col text-caption text-black q-mt-sm"
                    v-if="pdv.saurus_pin_pad_s.length > 0"
                  >
                    {{ pdv.saurus_pin_pad_s[0].serial }}
                  </div>
                  <div class="col text-caption text-black q-mt-sm">
                    Numero : {{ pdv.numero }}
                  </div>
                </div>
              </div>
            </q-card-section>
            <!-- editar , inativar , definir pinpad -->
            <q-card-actions align="center">
              <q-btn
                color="primary"
                label="Editar"
                class="q-mt-sm"
                @click="editarMaquineta(pdv)"
              />
              <q-btn
                v-if="!pdv.inativo"
                color="negative"
                label="Inativar"
                class="q-mt-sm"
                @click="inativarMaquineta(pdv)"
              />
              <q-btn
                v-else
                color="positive"
                label="Ativar"
                class="q-mt-sm"
                @click="ativarMaquineta(pdv)"
              />
              <q-btn
                color="cyan-10"
                label="Definir PinPad"
                class="q-mt-sm"
                @click="definirPinPad(pdv)"
              />
            </q-card-actions>
          </q-card>
        </div>
      </template>
    </div>
  </q-page>
</template>
<style scoped>
.q-btn--fab {
  max-width: 40px;
  max-height: 40px;

  min-width: 32px;
  min-height: 32px;
}
</style>
