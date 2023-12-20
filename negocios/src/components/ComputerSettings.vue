<script setup>
import { ref, onMounted } from "vue";
import { negocioStore } from "stores/negocio";
import { Dialog } from "quasar";
import SelectNaturezaOperacao from "components/selects/SelectNaturezaOperacao.vue";
import SelectEstoqueLocal from "components/selects/SelectEstoqueLocal.vue";
import SelectImpressora from "components/selects/SelectImpressora.vue";
import SelectPessoa from "components/selects/SelectPessoa.vue";

const sNegocio = negocioStore();
const settingsDialog = ref(false);

const edicao = ref({});

onMounted(async () => {});

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.padrao = { ...edicao.value };
    toggleSettingsDialog();
  });
};

const toggleSettingsDialog = () => {
  settingsDialog.value = !settingsDialog.value;
  if (settingsDialog.value) {
    edicao.value = { ...sNegocio.padrao };
  }
};
</script>
<template>
  <q-btn dense flat round icon="settings" @click="toggleSettingsDialog" />
  <q-dialog v-model="settingsDialog">
    <q-card style="width: 350px; max-width: 80vw">
      <q-form ref="formItem" @submit="salvar()">
        <q-card-section>
          <div class="q-gutter-md">
            <select-estoque-local
              outlined
              v-model="edicao.codestoquelocal"
              label="Local de Estoque"
            />
            <select-natureza-operacao
              outlined
              v-model="edicao.codnaturezaoperacao"
              label="Natureza de Operacao"
            />
            <select-pessoa
              outlined
              v-model="edicao.codpessoa"
              label="Pessoa"
              disable
            />
            <select-impressora
              outlined
              v-model="edicao.impressora"
              label="Impressora"
            />
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            tabindex="-1"
            @click="toggleSettingsDialog()"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
