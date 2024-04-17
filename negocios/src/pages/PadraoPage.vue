<script setup>
import MainLayout from "src/layouts/MainLayout.vue";
import ConfigLeftDrawer from "components/ConfigLeftDrawer.vue";
import { ref, onMounted } from "vue";
import { negocioStore } from "stores/negocio";
import { Dialog, Notify } from "quasar";
import SelectNaturezaOperacao from "components/selects/SelectNaturezaOperacao.vue";
import SelectEstoqueLocal from "components/selects/SelectEstoqueLocal.vue";
import SelectPagarMePos from "components/selects/SelectPagarMePos.vue";
import SelectImpressora from "components/selects/SelectImpressora.vue";
// import SelectPessoa from "components/selects/SelectPessoa.vue";

const sNegocio = negocioStore();

const edicao = ref({});

onMounted(() => {
  inicializaModel();
});

const inicializaModel = async () => {
  edicao.value = { ...sNegocio.padrao };
};

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.salvarPadrao(edicao.value);
    Notify.create({
      type: "positive",
      message: "Configurações Salvas!",
      timeout: 1000, // 1 segundo
      actions: [{ icon: "close", color: "white" }],
    });
  });
};
</script>
<template>
  <main-layout title="Meu Dispositivo" :right-drawer="false">
    <!-- LEFT DRAWER -->
    <template v-slot:left-drawer>
      <config-left-drawer />
    </template>

    <!-- CONTEUDO -->
    <div class="row justify-center">
      <q-card class="q-ma-md col-xs-11 col-sm-5 col-md-4 col-lg-3 col-xl-2">
        <q-form ref="formItem" @submit="salvar()">
          <q-card-section>
            <div class="q-gutter-md">
              <select-estoque-local
                outlined
                v-model="edicao.codestoquelocal"
                label="Local de Estoque"
                @update:model-value="edicao.codpagarmepos = null"
                clearable
              />
              <select-pagar-me-pos
                outlined
                v-model="edicao.codpagarmepos"
                label="POS Stone/PagarMe"
                :codestoquelocal="edicao.codestoquelocal"
                clearable
              />
              <select-natureza-operacao
                outlined
                v-model="edicao.codnaturezaoperacao"
                label="Natureza de Operacao"
                clearable
              />
              <!-- <select-pessoa
                outlined
                v-model="edicao.codpessoa"
                label="Pessoa"
                clearable
              /> -->
              <select-impressora
                outlined
                v-model="edicao.impressora"
                label="Impressora"
                clearable
              />
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn
              flat
              label="Cancelar"
              color="primary"
              tabindex="-1"
              @click="inicializaModel()"
            />
            <q-btn type="submit" flat label="Salvar" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </main-layout>
</template>
