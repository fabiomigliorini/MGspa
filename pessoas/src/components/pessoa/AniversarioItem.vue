<script setup>
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

// Props
const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  emptyMessage: {
    type: String,
    default: "Nenhum aniversário encontrado",
  },
});

// Computed
const idadeClass = "text-body1 text-grey-7";

const listStyle = "max-height: 400px; overflow-y: auto";

// Functions
const formataDataDiaMes = (data) => {
  return moment(data, "YYYY/MM/DD").format("ddd, D/MMM");
};

const formataTipo = (item) => {
  if (item.fisica === false) {
    return "Fundação da Empresa";
  }
  if (item.tipo === "Empresa") {
    return "Tempo de Colaboração";
  }
  return item.tipo;
};

const getCategoria = (item) => {
  if (item.colaborador) {
    return "Colaborador";
  }
  if (item.cliente) {
    return "Cliente";
  }
  if (item.fornecedor) {
    return "Fornecedor";
  }
  return null;
};

const formataCaption = (item) => {
  const data = formataDataDiaMes(item.data);
  const categoria = getCategoria(item);
  const tipo = formataTipo(item);

  return categoria ? `${data} • ${categoria} • ${tipo}` : `${data} • ${tipo}`;
};

const getIcone = (item) => {
  // Pessoa jurídica (empresa/organização)
  if (item.fisica === false) {
    return "business";
  }
  // Aniversário de contratação (tempo de empresa)
  if (item.tipo === "Empresa") {
    return "workspace_premium";
  }
  // Aniversário de nascimento (idade)
  return "cake";
};
</script>
<template>
  <q-list v-if="items.length > 0" :style="listStyle">
    <template v-for="(item, index) in items" :key="item.codpessoa">
      <q-separator inset v-if="index > 0" />
      <q-item>
        <q-item-section avatar>
          <q-btn round flat :icon="getIcone(item)" color="primary" />
        </q-item-section>

        <q-item-section>
          <q-item-label>
            <q-btn
              :to="'/pessoa/' + item.codpessoa"
              :label="item.fantasia || item.pessoa"
              flat
              dense
              color="primary"
              class="q-pa-none"
              no-caps
            />
          </q-item-label>
          <q-item-label caption>
            {{ formataCaption(item) }}
          </q-item-label>
        </q-item-section>

        <q-item-section side>
          <div class="text-center">
            <div :class="idadeClass">
              {{ item.idade }}
            </div>
            <div class="text-caption text-grey-6">
              {{ item.idade === 1 ? "Ano" : "Anos" }}
            </div>
          </div>
        </q-item-section>
      </q-item>
    </template>
  </q-list>

  <div v-else class="q-pa-md text-center text-grey">
    {{ emptyMessage }}
  </div>
</template>
