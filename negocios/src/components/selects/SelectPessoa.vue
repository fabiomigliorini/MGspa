<script setup>
import { ref, onMounted, watch } from "vue";
import { db } from "boot/db";
import { LoadingBar } from "quasar";
import { formataCnpjCpf } from "../../utils/formatador.js";

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
  if (codpessoa) {
    opcoes.value = await db.pessoa.where({ codpessoa: codpessoa }).toArray();
    return;
  }
  opcoes.value = [];
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
    // verifica se tem texto de busca
    const texto = textoPesquisa.trim();
    if (texto.length < 2) {
      return;
    }

    // monta array de palavras pra buscas
    LoadingBar.start();
    const palavras = texto.split(" ");

    // Busca Pessoas baseados na primeira palavra de pesquisa
    var colPessoas = await db.pessoa
      .where("buscaArr")
      .startsWithIgnoreCase(palavras[0]);

    if (props.somenteAtivos) {
      colPessoas.and((p) => p.inativo == null);
    }

    if (props.somenteVendedores) {
      colPessoas.and((p) => p.vendedor == true);
    }

    // se estiver buscando por mais de uma palavra
    if (palavras.length > 1) {
      // monta expressoes regulares
      var regexes = [];
      for (let i = 1; i < palavras.length; i++) {
        regexes.push(new RegExp(".*" + palavras[i] + ".*", "i"));
      }

      // percorre todos registros filtrando pelas expressoes regulares
      const iMax = regexes.length;
      colPessoas = await colPessoas.and(function (pessoa) {
        for (let i = 0; i < iMax; i++) {
          if (!regexes[i].test(pessoa.busca)) {
            return false;
          }
        }
        return true;
      });
    }
    var arrPessoas = await colPessoas.toArray();
    arrPessoas = arrPessoas.sort((a, b) => {
      if (a.fantasia > b.fantasia) {
        return 1;
      } else {
        return -1;
      }
    });
    // esconde barra
    LoadingBar.stop();
    opcoes.value = arrPessoas;
  });
};
</script>
<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @filter="pesquisa"
    use-input
    emit-value
    map-options
    option-value="codpessoa"
    option-label="fantasia"
    v-bind="$attrs"
    @update:model-value="(value) => alterar(value)"
  >
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
            {{ formataCnpjCpf(scope.opt.cnpj, scope.opt.fisica) }}
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
