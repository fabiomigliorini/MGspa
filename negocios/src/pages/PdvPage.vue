<script setup>
import { onMounted, ref } from "vue";
import { pdvStore } from "src/stores/pdv";
import moment from "moment/min/moment-with-locales";
import SelectFilial from "components/selects/SelectFilial.vue";
import { Notify } from "quasar";

moment.locale("pt-br");

const sPdv = pdvStore();
const model = ref({});
const dialogEditarPdv = ref(false);

const openStreetMapUrl = (pdv) => {
  if (!pdv.longitude) {
    return "";
  }
  return `http://www.openstreetmap.org/export/embed.html?bbox=${pdv.longitude},${pdv.latitude},${pdv.longitude},${pdv.latitude}&layers=ND`;
};

const googleMapsUrl = (pdv) => {
  return `http://maps.google.com/maps?q=${pdv.latitude},${pdv.longitude}`;
};

const editar = (pdv) => {
  dialogEditarPdv.value = true;
  model.value = { ...pdv };
};

const salvarPdv = async () => {
  try {
    const ret = await sPdv.updateConfigPdv(model.value);
    dialogEditarPdv.value = false;
  } catch (error) {
    Notify.create({
      type: "negative",
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
  }
};

onMounted(() => {
  sPdv.getDispositivos();
});
</script>
<template>
  <q-page>
    <div class="row q-pa-md q-col-gutter-md q-pb-xl">
      <template v-for="pdv in sPdv.dispositivos" :key="pdv.codpdv">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
          <q-card class="my-card" flat bordered>
            <iframe
              width="100%"
              height="200"
              frameborder="0"
              scrolling="no"
              marginheight="0"
              marginwidth="0"
              :src="openStreetMapUrl(pdv)"
            >
            </iframe>

            <q-card-section>
              <q-btn
                fab
                color="primary"
                icon="place"
                class="absolute"
                style="top: 0; right: 12px; transform: translateY(-50%)"
                :href="googleMapsUrl(pdv)"
                target="_blank"
                :disable="!pdv.latitude"
              />

              <div class="row no-wrap items-center">
                <div class="col text-h6 ellipsis">
                  {{ pdv.ip }}
                </div>
              </div>
              <div class="text-subtitle1">
                {{ pdv.apelido }}
                <q-btn
                  flat
                  dense
                  size="sm"
                  round
                  icon="edit"
                  @click="editar(pdv)"
                >
                  <q-tooltip class="bg-accent">Editar</q-tooltip>
                </q-btn>
              </div>

              <div class="text-caption text-grey" v-if="pdv.observacoes">
                <q-icon name="comment" />
                {{ pdv.observacoes }}
              </div>
              <div class="text-caption text-grey">
                <q-icon name="home" />
                #{{ String(pdv.codpdv).padStart(8, "0") }}
                <template v-if="pdv.codfilial"> | {{ pdv.filial }} </template>
              </div>
              <div class="text-caption text-grey">
                <q-icon name="fingerprint" />
                {{ pdv.uuid }}
              </div>
              <div class="text-caption text-grey">
                <q-icon name="desktop_windows" v-if="pdv.desktop" />
                <q-icon name="smartphone" v-else />
                {{ pdv.plataforma }}
                {{ pdv.navegador }}
                {{ pdv.versaonavegador }}
                {{ moment(pdv.criacao).fromNow() }}
              </div>
              <div class="text-caption text-grey ellipsis" v-if="pdv.latitude">
                <q-icon name="place" />
                {{ pdv.latitude }}
                {{ pdv.longitude }}
                {{ pdv.precisao }}
              </div>
            </q-card-section>
            <q-card-section>
              <!-- INATIVAR -->
              <q-banner
                rounded
                dense
                class="bg-green text-white"
                inline-actions
                v-if="pdv.autorizado"
              >
                Autorizado
                <template v-slot:action>
                  <q-btn
                    flat
                    dense
                    round
                    icon="pause"
                    size="sm"
                    @click="sPdv.inativar(pdv)"
                  >
                    <q-tooltip class="bg-accent">Inativar</q-tooltip>
                  </q-btn>
                </template>
              </q-banner>

              <!-- ATIVAR -->
              <q-banner
                rounded
                dense
                class="bg-red text-white"
                inline-actions
                v-else-if="pdv.inativo"
              >
                Inativo
                <template v-slot:action>
                  <q-btn
                    flat
                    dense
                    icon="play_arrow"
                    size="sm"
                    @click="sPdv.reativar(pdv)"
                  >
                    <q-tooltip class="bg-accent">Reativar</q-tooltip>
                  </q-btn>
                </template>
              </q-banner>

              <!-- AUTORIZAR -->
              <q-banner
                rounded
                dense
                class="bg-orange text-white"
                inline-actions
                v-else
              >
                Não Autorizado
                <template v-slot:action>
                  <q-btn
                    flat
                    dense
                    round
                    icon="check_circle"
                    size="sm"
                    v-if="!pdv.autorizado"
                    @click="sPdv.autorizar(pdv)"
                  >
                    <q-tooltip class="bg-accent">Autorizar</q-tooltip>
                  </q-btn>
                </template>
              </q-banner>
            </q-card-section>

            <q-separator />

            <!-- <q-card-actions> </q-card-actions> -->
          </q-card>
        </div>
      </template>
    </div>

    <q-dialog v-model="dialogEditarPdv">
      <q-card style="min-width: 350px">
        <q-form @submit="salvarPdv">
          <q-card-section>
            <div class="q-col-gutter-md">
              <q-input
                outlined
                v-model="model.apelido"
                autofocus
                label="Apelido"
              />

              <select-filial v-model="model.codfilial"></select-filial>

              <q-input
                outlined
                autogrow
                v-model="model.observacoes"
                label="Observações"
                type="textarea"
                class="q-mb-md"
                autofocus
              />
            </div>
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
