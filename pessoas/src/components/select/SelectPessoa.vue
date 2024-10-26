<script setup>
import { ref, onMounted, watch } from "vue";
import { api } from "src/boot/axios.js";
import { LoadingBar } from "quasar";
import { formataDocumetos } from "src/stores/formataDocumentos.js";

const fDocs = formataDocumetos();

const props = defineProps({
  modelValue: {
    type: Number,
  },
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
  somenteVendedores: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue"]);

const opcoes = ref([]);

const alterar = (value) => {
  emit("update:modelValue", value);
};

const buscarPeloCod = async (codpessoa) => {
  try {
    const ret = await api.get('v1/select/pessoa', {
      params: {
        codpessoa: codpessoa
      }
    })
    opcoes.value = ret.data
  } catch (error) {
    console.log(error);
    opcoes.value = [];
  }
};

watch(
  () => props.modelValue,
  (newValue) => {
    buscarPeloCod(newValue);
  }
);

onMounted(async () => {
  if (!props.modelValue) {
    return;
  }
  buscarPeloCod(props.modelValue);
});

const pesquisa = (textoPesquisa, update) => {
  update(async () => {
    const texto = textoPesquisa.trim();
    // verifica se tem texto de busca
    if (texto.length < 2) {
      return;
    }

    // sinaliza pro usuario que está pesquisando
    LoadingBar.start();

    const params = {
      pessoa: texto,
    }
    if (props.somenteAtivos) {
      params.somenteAtivos = true;
    }
    if (props.somenteVendedores) {
      params.somenteVendedores = true;
    }

    try {
      const ret = await api.get('v1/select/pessoa', {
        params: params
      });
      opcoes.value = ret.data

    } catch (error) {
      console.log(error);
      opcoes.value = [];
    }

    LoadingBar.stop();
  });
};
</script>
<template>
  <q-select :options="opcoes" :model-value="modelValue" @filter="pesquisa" use-input emit-value map-options
    option-value="codpessoa" option-label="fantasia" v-bind="$attrs" @update:model-value="(value) => alterar(value)">
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="person" v-if="scope.opt.fisica" />
          <q-icon name="warehouse" v-else />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.fantasia }}</q-item-label>
          <q-item-label caption>
            {{ scope.opt.pessoa }}
          </q-item-label>
          <q-item-label caption v-if="scope.opt.cnpj">
            {{ fDocs.formataCnpjCpf(scope.opt.cnpj, scope.opt.fisica) }}
            {{ scope.opt.ie }}
          </q-item-label>
          <q-item-label caption>
            <template v-if="scope.opt.endereco">
              {{ scope.opt.endereco }}, {{ scope.opt.numero }}
            </template>
            <template v-if="scope.opt.complemento">
              - {{ scope.opt.complemento }}
            </template>
            <template v-if="scope.opt.bairro">
              - {{ scope.opt.bairro }} -
            </template>
            {{ scope.opt.cidade }}/{{ scope.opt.uf }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
