<script setup>
import { ref, onMounted } from "vue";
import { db } from "boot/db";
import { pdvStore } from "src/stores/pdv";

const sPdv = pdvStore();

const props = defineProps({
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
});

const opcoes = ref([]);
const filtrado = ref([]);

onMounted(async () => {
  if (sPdv.dispositivos.length == 0) {
    await sPdv.getDispositivos();
  }
  if (props.somenteAtivos) {
    opcoes.value = await sPdv.dispositivos.filter((item) => {
      return item.inativo == null;
    });
  } else {
    opcoes.value = [...sPdv.dispositivos];
  }
  filtrado.value = [...opcoes.value];
});

const sortRegs = (regs) => {
  regs = regs.sort((a, b) => {
    if (!a.apelido) {
      return 1;
    }
    if (!b.apelido) {
      return -1;
    }
    if (a.apelido.toUpperCase() > b.apelido.toUpperCase()) {
      return 1;
    }
    return -1;
  });
  return regs;
};

const pesquisa = (val, update) => {
  if (val === "") {
    update(() => {
      filtrado.value = sortRegs(opcoes.value);
    });
    return;
  }
  update(() => {
    const pesquisa = val.toLowerCase();
    let regs = opcoes.value.filter((item) => {
      if (item.apelido == null) {
        item.apelido = "";
      }
      return (
        item.ip.toLowerCase().indexOf(pesquisa) > -1 ||
        item.uuid.toLowerCase().indexOf(pesquisa) > -1 ||
        item.apelido.toLowerCase().indexOf(pesquisa) > -1
      );
    });
    filtrado.value = sortRegs(regs);
  });
};
</script>
<template>
  <q-select
    :options="filtrado"
    use-input
    @filter="pesquisa"
    emit-value
    map-options
    option-value="codpdv"
    :option-label="
      (item) =>
        item.apelido === null
          ? item.ip + ' (#' + item.codpdv + ')'
          : item.apelido
    "
    v-bind="$attrs"
    options-cover
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="desktop_windows" v-if="scope.opt.desktop" />
          <q-icon name="smartphone" v-else />
        </q-item-section>
        <q-item-section>
          <q-item-label v-if="scope.opt.apelido">
            {{ scope.opt.apelido }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.ip }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.uuid }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          {{ scope.opt.plataforma }}
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
