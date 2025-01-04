<script setup>
import { ref, onMounted } from "vue";
import { Notify } from "quasar";
import { api } from "src/boot/axios";
import { negocioStore } from "stores/negocio";
import SelectImpressora from "components/selects/SelectImpressora.vue";
import SelectPessoa from "src/components/selects/SelectPessoa.vue";

const sNegocio = negocioStore();

const model = ref({
  // impressora: "bematech-cxabot01",
  impressora: sNegocio.padrao.impressora,
  // codpessoavendedor: 30010669,
  codpessoavendedor: null,
  copias: 20
})

const imprimir = async () => {


  try {
    await api.post(
      '/api/v1/pessoa/' + model.value.codpessoavendedor + '/comanda-vendedor/imprimir',
      {
        impressora: model.value.impressora,
        copias: model.value.copias
      }
    );
    Notify.create({
      type: "positive",
      message: "Comandas impressas!",
    });
  } catch (error) {
    console.log(error);
    var message = error?.response?.data?.message;
    if (!message) {
      message = error?.message;
    }
    Notify.create({
      type: "negative",
      message: message,
    });
    return false;
  }

}

onMounted(() => {
  model.value.impressora = sNegocio.padrao.impressora
});

</script>
<template>
  <q-page>
    <div class="row justify-center">
      <q-card class="q-ma-md col-xs-11 col-sm-5 col-md-4 col-lg-3 col-xl-2">
        <q-form ref="formItem" @submit="imprimir()">

          <q-card-section>

            <div class="q-gutter-md">
              <select-impressora outlined v-model="model.impressora" label="Impressora" clearable
                :rules="[val => !!val || 'Selecione uma impressora!']" />
              <select-pessoa outlined v-model="model.codpessoavendedor" label="Vendedor" clearable somente-vendedores
                somente-ativos :rules="[val => !!val || 'Selecione um vendedor!']" />
              <q-input outlined label="Cópias" v-model="model.copias" type="number" step="1" min="1"
                input-class="text-right" :rules="[val => val >= 1 || 'Informe a quantidade de cópias!']" />
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn type="submit" flat label="Imprimir" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
