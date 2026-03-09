<script setup>
import { onMounted, ref, watch } from "vue";
import { pdvStore } from "src/stores/pdv";
import moment from "moment/min/moment-with-locales";
import DialogEditarPdv from "components/pdv/DialogEditarPdv.vue";
import SelectFilial from "components/selects/SelectFilial.vue";
import SelectSetor from "components/selects/SelectSetor.vue";
import { Notify } from "quasar";

moment.locale("pt-br");

const sPdv = pdvStore();
const model = ref({});
const dialogEditarPdv = ref(false);
const filtro = ref({
  apelido: null,
  codfilial: null,
  status: null,
  ip: null,
  uuid: null,
  codsetor: null,
});

const buscar = () => {
  sPdv.getDispositivos(filtro.value);
};

const googleMapsUrl = (pdv) => {
  return `http://maps.google.com/maps?q=${pdv.latitude},${pdv.longitude}`;
};

const editar = (pdv) => {
  model.value = { ...pdv };
  dialogEditarPdv.value = true;
};

const salvarPdv = async (formData) => {
  try {
    await sPdv.updateConfigPdv(formData);
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000,
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

const statusColor = (pdv) => {
  if (pdv.autorizado) return "green";
  if (pdv.inativo) return "red";
  return "orange";
};

const statusIcon = (pdv) => {
  if (pdv.autorizado) return "check_circle";
  if (pdv.inativo) return "cancel";
  return "warning";
};

const statusLabel = (pdv) => {
  if (pdv.autorizado) return "Autorizado";
  if (pdv.inativo) return "Inativo";
  return "Não Autorizado";
};

const statusClass = (pdv) => {
  if (pdv.autorizado) return "bg-green-1";
  if (pdv.inativo) return "bg-red-1";
  return "bg-orange-1";
};

watch(
  filtro,
  () => {
    buscar();
  },
  { deep: true }
);

onMounted(() => {
  buscar();
});
</script>
<template>
  <q-page>
    <q-card flat class="q-pa-md">
      <div class="text-caption text-grey-7 q-mb-sm">
        Filtre seu dispositivo:
      </div>
      <div class="row q-col-gutter-md">
        <div class="col-xs-12 col-sm-4">
          <q-input
            outlined
            v-model="filtro.apelido"
            label="Apelido"
            clearable
          />
        </div>
        <div class="col-xs-12 col-sm-4">
          <select-filial v-model="filtro.codfilial" clearable />
        </div>
        <div class="col-xs-12 col-sm-4">
          <q-select
            outlined
            v-model="filtro.status"
            label="Status"
            :options="[
              { label: 'Autorizado', value: 'autorizado' },
              { label: 'Inativo', value: 'inativo' },
              { label: 'Não Autorizado', value: 'nao_autorizado' },
            ]"
            emit-value
            map-options
            clearable
          />
        </div>
        <div class="col-xs-12 col-sm-4">
          <q-input outlined v-model="filtro.ip" label="IP" clearable />
        </div>
        <div class="col-xs-12 col-sm-4">
          <q-input outlined v-model="filtro.uuid" label="UUID" clearable />
        </div>
        <div class="col-xs-12 col-sm-4">
          <select-setor
            v-model="filtro.codsetor"
            outlined
            label="Setor"
            clearable
          />
        </div>
      </div>
    </q-card>

    <div
      v-if="sPdv.dispositivos.length === 0"
      class="absolute-center text-grey text-center"
    >
      <q-icon name="do_not_disturb" size="300px" />
      <h3>Nenhum dispositivo localizado!</h3>
    </div>

    <q-list v-else separator>
      <template v-for="pdv in sPdv.dispositivos" :key="pdv.codpdv">
        <q-item class="row" :class="statusClass(pdv)">
          <!-- ICONE STATUS -->
          <q-item-section avatar>
            <q-avatar
              :icon="statusIcon(pdv)"
              :color="statusColor(pdv)"
              text-color="white"
            />
          </q-item-section>

          <!-- APELIDO / COD / FILIAL / IP-->
          <q-item-section>
            <q-item-label>
              {{ pdv.apelido }}
            </q-item-label>
            <q-item-label caption class="ellipsis">
              #{{ String(pdv.codpdv).padStart(8, "0") }} | {{ pdv.filial }}
            </q-item-label>
            <q-item-label caption class="ellipsis" v-if="pdv.observacoes">
              IP: {{ pdv.ip }}
            </q-item-label>
          </q-item-section>

          <!-- Observações / PLATAFORMA / DATA -->
          <q-item-section class="gt-xs">
            <q-item-label>{{ pdv.setor }}</q-item-label>
            <q-item-label caption>
              <q-icon :name="pdv.desktop ? 'desktop_windows' : 'smartphone'" />
              {{ pdv.plataforma }} {{ pdv.navegador }} {{ pdv.versaonavegador }}
            </q-item-label>
            <q-item-label caption>
              {{ moment(pdv.criacao).format("DD/MM/YY HH:mm:ss") }}
            </q-item-label>
          </q-item-section>

          <!-- UUID / LOCALIZAÇÃO -->
          <q-item-section class="gt-sm ellipsis">
            <q-item-label>UUID / Localização</q-item-label>
            <q-item-label caption>{{ pdv.uuid }}</q-item-label>
            <q-item-label caption v-if="pdv.latitude">
              <q-btn
                v-if="pdv.latitude"
                round
                flat
                class="text-primary"
                icon="place"
                size="xs"
                :href="googleMapsUrl(pdv)"
                target="_blank"
              >
                <q-tooltip>Mapa</q-tooltip>
              </q-btn>
              {{ pdv.latitude }}, {{ pdv.longitude }}
            </q-item-label>
          </q-item-section>

          <!--/ STATUS  -->
          <q-item-section side class="col-xs-4 col-sm-3 col-md-2">
            <q-item-label class="text-black">Status</q-item-label>
            <q-item-label>
              <span :class="'text-' + statusColor(pdv)">
                {{ statusLabel(pdv) }}
              </span>
            </q-item-label>
            <q-item-label>
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="xs"
                @click="editar(pdv)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="pdv.autorizado"
                flat
                dense
                round
                icon="pause"
                size="xs"
                color="red"
                @click="sPdv.inativar(pdv)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>
              <q-btn
                v-else-if="pdv.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="xs"
                color="green"
                @click="sPdv.reativar(pdv)"
              >
                <q-tooltip>Reativar</q-tooltip>
              </q-btn>
              <q-btn
                v-else
                flat
                dense
                round
                icon="check_circle"
                size="xs"
                color="green"
                @click="sPdv.autorizar(pdv)"
              >
                <q-tooltip>Autorizar</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>

    <dialog-editar-pdv
      v-model="dialogEditarPdv"
      :pdv="model"
      titulo="Editar Dispositivo"
      @salvar="salvarPdv"
    />
  </q-page>
</template>
