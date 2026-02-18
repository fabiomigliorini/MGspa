<template>
  <MGLayout>
    <template #tituloPagina>Aniversários</template>
    <template #content>
      <div style="max-width: 1280px; margin: auto; min-height: 100vh">
        <div class="row q-col-gutter-md q-pa-md">
          <div class="col-12">
            <div class="row q-col-gutter-md">
              <!-- Coluna Esquerda - Calendário -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline">
                    CALENDÁRIO
                  </q-card-section>

                  <q-separator inset />

                  <q-card-section>
                    <div class="row">
                      <div class="col-auto">
                        <q-date
                          flat
                          bordered
                          v-model="date"
                          :options="events"
                          event-color="orange"
                          minimal
                        />
                      </div>
                      <div class="col q-pl-md">
                        <q-option-group
                          v-model="tipo"
                          :options="tipoOptions"
                          color="primary"
                        />
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Coluna Direita - Aniversariantes do Dia -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline">
                    ANIVERSARIANTES DO DIA
                  </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="aniversariosDoDia"
                    empty-message="Nenhum aniversariante neste dia"
                  />
                </q-card>
              </div>

              <!-- Card Últimos Aniversários -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline">
                    ANTERIORES
                  </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="ultimosAniversarios"
                    empty-message="Nenhum aniversário nos últimos 7 dias"
                  />
                </q-card>
              </div>

              <!-- Card Próximos Aniversários -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline">
                    PRÓXIMOS
                  </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="proximosAniversarios"
                    empty-message="Nenhum aniversário nos próximos 7 dias"
                  />
                </q-card>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<script setup>
// 1. Imports do Vue
import { ref, computed, onMounted, watch } from "vue";

// 2. Imports de stores
import { pessoaStore } from "src/stores/pessoa";

// 3. Imports de utilitários
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

// 4. Imports de componentes
import MGLayout from "layouts/MGLayout.vue";
import AniversarioItem from "components/pessoa/AniversarioItem.vue";

// 5. Instâncias
const sPessoa = pessoaStore();

// 6. Refs e computed
const tipo = ref("todos");
const date = ref(moment().format("YYYY/MM/DD"));
const aniversarios = ref([]);
const events = ref([]);

const tipoOptions = [
  { label: "Todos", value: "todos" },
  { label: "Colaboradores", value: "colaborador" },
  { label: "Clientes", value: "cliente" },
  { label: "Fornecedores", value: "fornecedor" },
];

// Computed para aniversariantes do dia selecionado
const aniversariosDoDia = computed(() => {
  const dia = moment(date.value, "YYYY/MM/DD");
  return aniversarios.value.filter((a) => {
    return a.dia == dia.date() && a.mes == dia.month() + 1;
  });
});

// Computed para últimos 7 dias (excluindo o dia selecionado)
const ultimosAniversarios = computed(() => {
  const dataSelecionada = moment(date.value, "YYYY/MM/DD");
  const dataInicio = moment(dataSelecionada).subtract(7, "days");

  return aniversarios.value
    .filter((a) => {
      const dataAniversario = moment(a.data, "YYYY-MM-DD");
      return (
        dataAniversario.isBefore(dataSelecionada, "day") &&
        dataAniversario.isSameOrAfter(dataInicio, "day")
      );
    })
    .sort((a, b) => moment(b.data, "YYYY-MM-DD").valueOf() - moment(a.data, "YYYY-MM-DD").valueOf());
});

// Computed para próximos 7 dias (excluindo o dia selecionado)
const proximosAniversarios = computed(() => {
  const dataSelecionada = moment(date.value, "YYYY/MM/DD");
  const dataFim = moment(dataSelecionada).add(7, "days");

  return aniversarios.value
    .filter((a) => {
      const dataAniversario = moment(a.data, "YYYY-MM-DD");
      return (
        dataAniversario.isAfter(dataSelecionada, "day") &&
        dataAniversario.isSameOrBefore(dataFim, "day")
      );
    })
    .sort((a, b) => moment(a.data, "YYYY-MM-DD").valueOf() - moment(b.data, "YYYY-MM-DD").valueOf());
});

// 7. Funções
const getAniversarios = async () => {
  const ret = await sPessoa.buscaAniversarios(tipo.value);
  const dados = ret.data;

  const dates = [];
  const arrAniversarios = [];
  const anoAtual = moment().year();

  dados.forEach((el) => {
    const dataOriginal = moment(el.data, "YYYY-MM-DD");

    // Ajusta a data para o ano atual
    const dataAnoAtual = dataOriginal.clone().year(anoAtual);
    el.data = dataAnoAtual.format("YYYY-MM-DD");
    el.dia = dataOriginal.date();
    el.mes = dataOriginal.month() + 1;
    dates.push(dataAnoAtual.format("YYYY/MM/DD"));
    arrAniversarios.push(el);
  });

  aniversarios.value = arrAniversarios;
  events.value = dates;
};

// 8. Watchers
watch(tipo, () => {
  getAniversarios();
});

// 9. Lifecycle
onMounted(() => {
  getAniversarios();
});
</script>
