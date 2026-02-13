<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";

const CardDetalhesPessoa = defineAsyncComponent(() =>
  import("components/pessoa/CardDetalhesPessoa.vue")
);
const CardCliente = defineAsyncComponent(() =>
  import("components/pessoa/CardCliente.vue")
);
const CardHistoricoCobranca = defineAsyncComponent(() =>
  import("components/pessoa/CardHistoricoCobranca.vue")
);
const CardColaborador = defineAsyncComponent(() =>
  import("components/pessoa/CardColaborador.vue")
);
const ItemTelefone = defineAsyncComponent(() =>
  import("components/pessoa/ItemTelefone.vue")
);
const ItemEmail = defineAsyncComponent(() =>
  import("components/pessoa/ItemEmail.vue")
);
const ItemEndereco = defineAsyncComponent(() =>
  import("components/pessoa/ItemEndereco.vue")
);
const CardPessoaConta = defineAsyncComponent(() =>
  import("components/pessoa/CardPessoaConta.vue")
);
const CardDependentes = defineAsyncComponent(() =>
  import("components/pessoa/CardDependentes.vue")
);
const CardArquivos = defineAsyncComponent(() =>
  import("components/pessoa/CardArquivos.vue")
);
const CardRegistroSpc = defineAsyncComponent(() =>
  import("components/pessoa/CardRegistroSpc.vue")
);
const CardCertidoes = defineAsyncComponent(() =>
  import("components/pessoa/CardCertidoes.vue")
);
const MGLayout = defineAsyncComponent(() => import("layouts/MGLayout.vue"));

const route = useRoute();
const sPessoa = pessoaStore();
const totalNegocioPessoa = ref([]);

onMounted(async () => {
  sPessoa.get(route.params.id);
  const ret = await sPessoa.totaisNegocios(1, {
    codpessoa: route.params.id,
  });
  totalNegocioPessoa.value = ret.data;
});
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Pessoa - Detalhes</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{ name: 'pessoa' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div
          class="row q-pa-md q-col-gutter-md"
          v-if="sPessoa.item"
          style="max-width: 1366px; margin: auto"
        >
          <div class="col-md-8 col-12">
            <div class="column q-gutter-y-md">
              <card-detalhes-pessoa />
              <card-cliente />
              <card-historico-cobranca />
              <card-colaborador />
            </div>
          </div>
          <div class="col-md-4 col-12">
            <div class="column q-gutter-y-md">
              <item-telefone />
              <item-email />
              <item-endereco />
              <card-pessoa-conta />
              <card-dependentes />
              <card-arquivos />
              <card-registro-spc />
              <card-certidoes />
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>

<style scoped></style>
