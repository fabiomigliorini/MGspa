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
          v-if="sPessoa.item"
          class="row q-col-gutter-md"
          style="max-width: 1280px; margin: auto"
        >
          <div class="col-md-8 col-sm-12">
            <div class="row q-col-gutter-md">
              <!-- CARD PESSOA -->
              <div class="col-12">
                <card-detalhes-pessoa />
              </div>

              <!-- CLIENTE -->
              <div class="col-12">
                <card-cliente />
              </div>

              <!-- COBRANCA -->
              <div class="col-12">
                <card-historico-cobranca />
              </div>

              <!-- COLABORADOR -->
              <div class="col-12">
                <card-colaborador />
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="row q-col-gutter-md">
              <!-- TELEFONE -->
              <div class="col-12">
                <item-telefone />
              </div>

              <!-- EMAIL -->
              <div class="col-12">
                <item-email />
              </div>

              <!-- ENDERECO -->
              <div class="col-12">
                <item-endereco />
              </div>

              <!-- CONTA -->
              <div class="col-12">
                <card-pessoa-conta />
              </div>

              <!-- DEPENDENTES -->
              <div class="col-12">
                <card-dependentes />
              </div>

              <!-- ARQUIVOS -->
              <div class="col-12">
                <card-arquivos />
              </div>

              <!-- SPC -->
              <div class="col-12">
                <card-registro-spc />
              </div>

              <!-- CERT -->
              <div class="col-12">
                <card-certidoes />
              </div>
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>

<style scoped></style>
