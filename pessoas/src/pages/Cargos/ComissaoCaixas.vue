<script setup>
import { ref, onMounted, watch } from "vue";
import { debounce } from "quasar";
import { guardaToken } from "src/stores";
import MGLayout from "src/layouts/MGLayout.vue";
import moment from "moment";
import { api } from "src/boot/axios";

const user = guardaToken();
const caixas = ref([]);

const filtro = ref({
  inicio: moment().add(-10, "days").startOf("month").format("DD/MM/YYYY HH:mm"),
  fim: moment().add(-10, "days").endOf("month").format("DD/MM/YYYY HH:mm"),
});

const buscar = debounce(async () => {
  try {
    const params = {
      inicio: moment(filtro.value.inicio, "DD/MM/YYYY HH:mm").format(
        "YYYY-MM-DD HH:mm"
      ),
      fim: moment(filtro.value.fim, "DD/MM/YYYY HH:mm").format(
        "YYYY-MM-DD HH:mm"
      ),
    };
    const ret = await api.get("v1/comissao-caixas", { params: params });
    caixas.value = ret.data;
  } catch (error) {
    console.log(error);
  }
}, 500);

watch(
  () => filtro,
  () => buscar(),
  { deep: true }
);

const columns = ref([
  {
    name: "filial",
    label: "Filial",
    field: "filial",
    align: "left",
    sortable: true,
  },
  {
    name: "pessoa",
    label: "Colaborador",
    field: "pessoa",
    align: "left",
    sortable: true,
  },
  {
    name: "cargo",
    label: "Cargo",
    field: "cargo",
    align: "left",
    sortable: true,
  },
  {
    name: "negocios",
    label: "Vendas",
    field: "negocios",
    format: (val) => {
      return new Intl.NumberFormat("pt-BR", {
        style: "decimal",
      }).format(val);
    },
    align: "right",
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: "valor",
    label: "Valor",
    field: "valor",
    format: (val) => {
      return new Intl.NumberFormat("pt-BR", {
        minimumFractionDigits: 2,
        style: "decimal",
      }).format(val);
    },
    align: "right",
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: "comissaocaixa",
    label: "% Comissão",
    field: "comissaocaixa",
    format: (val) => {
      return new Intl.NumberFormat("pt-BR", {
        style: "percent",
        minimumFractionDigits: 2,
      }).format(val / 100);
    },
    align: "right",
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
  {
    name: "comissao",
    label: "Comissão",
    field: "comissao",
    format: (val) => {
      return new Intl.NumberFormat("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        style: "decimal",
      }).format(val);
    },
    align: "right",
    sortable: true,
    sort: (a, b) => parseFloat(a) - parseFloat(b),
  },
]);

const pagination = ref({
  rowsPerPage: 0,
});

onMounted(() => {
  buscar();
});

const filter = ref("");
</script>
<template>
  <MGLayout drawer v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <template #tituloPagina> Comissão Caixas </template>
    <template #content>
      <div class="q-pa-md">
        <q-table
          flat
          bordered
          title="Caixas"
          :rows="caixas"
          row-key="codpessoa"
          :columns="columns"
          virtual-scroll
          v-model:pagination="pagination"
          :rows-per-page-options="[0]"
          :filter="filter"
        >
          <template v-slot:top-right>
            <q-input
              outlined
              dense
              debounce="300"
              v-model="filter"
              placeholder="Search"
            >
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </template>
        </q-table>
      </div>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Período
              <q-btn icon="replay" @click="buscar()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <div class="q-pa-md q-gutter-md">
          <!-- DATA inicio -->
          <q-input outlined v-model="filtro.inicio" input-class="text-center">
            <template v-slot:prepend>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-date v-model="filtro.inicio" mask="DD/MM/YYYY HH:mm">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Close" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>

            <template v-slot:append>
              <q-icon name="access_time" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-time
                    v-model="filtro.inicio"
                    mask="DD/MM/YYYY HH:mm"
                    format24h
                  >
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Close" color="primary" flat />
                    </div>
                  </q-time>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>

          <!-- DATA fim -->
          <q-input outlined v-model="filtro.fim" input-class="text-center">
            <template v-slot:prepend>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-date v-model="filtro.fim" mask="DD/MM/YYYY HH:mm">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Close" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>

            <template v-slot:append>
              <q-icon name="access_time" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-time
                    v-model="filtro.fim"
                    mask="DD/MM/YYYY HH:mm"
                    format24h
                  >
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Close" color="primary" flat />
                    </div>
                  </q-time>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
      </div>
    </template>
  </MGLayout>
</template>
