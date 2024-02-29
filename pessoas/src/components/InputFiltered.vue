<script setup>
import { ref, computed, nextTick } from "vue";
import { primeiraLetraMaiuscula } from "src/utils/formatador";

// variavaies de controle
const refInput = ref(); // ref para poder setar o foco
const filtrar = ref(true); // toogle se filtra ou nao o texto
const modelAnterior = ref(null); // cache do ultimo filtro pra nao ficar aplicando filtro quando estiver navegando com as setas

// propriedades
const props = defineProps({
  modelValue: {
    type: [String],
    default: "",
  },
  filtro: {
    //tipo do filtro pra alicar
    type: [String],
    default: "primeiraLetraMaiuscula",
  },
});

// para mandar pro componente pai as alteracoes
const emit = defineEmits(["update:modelValue"]);

// controla as alteracoes pra enviar ao compoenente pai, e recebe as alteracoes do pai
const model = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    return emit("update:modelValue", value);
  },
});

// aplica o filtro selecionado
const aplicarFiltro = (event) => {
  // se o texto for igual ao da ultima execucao evita aplicar
  // novamente pra que não sobrecarregue quando o usuario
  // estiver apenas navegando com as setas por exemplo
  if (model.value == modelAnterior.value || !filtrar.value) {
    return;
  }
  // salva localizacao do cursor antes de alterar
  const posStart = refInput.value.getNativeElement().selectionStart;
  const posEnd = refInput.value.getNativeElement().selectionEnd;
  switch (props.filtro) {
    case "primeiraLetraMaiuscula":
      model.value = primeiraLetraMaiuscula(model.value);
      nextTick(() => {
        // volta localizacao do cursor
        refInput.value.getNativeElement().selectionStart = posStart;
        refInput.value.getNativeElement().selectionEnd = posEnd;
      });
      break;
    default:
      break;
  }
  // salva cache da ultima alteracao
  modelAnterior.value = model.value;
};

// liga/desliga o filtro
const toggleFiltrar = () => {
  filtrar.value = !filtrar.value;
  refInput.value.focus();
  modelAnterior.value = model.value;
};
</script>

<template>
  <q-input
    ref="refInput"
    v-model="model"
    @keyup="aplicarFiltro"
    @focus="modelAnterior = model"
  >
    <template v-slot:append>
      <q-icon
        :name="filtrar ? 'published_with_changes' : 'unpublished'"
        class="cursor-pointer"
        @click="toggleFiltrar()"
      />
    </template>
  </q-input>
</template>
