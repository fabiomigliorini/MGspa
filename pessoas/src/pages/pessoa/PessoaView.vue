<script setup>
import { onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import CardDetalhesPessoa from "components/pessoa/CardDetalhesPessoa.vue";
import CardCliente from "components/pessoa/CardCliente.vue";
import CardHistoricoCobranca from "components/pessoa/CardHistoricoCobranca.vue";
import CardColaborador from "components/pessoa/CardColaborador.vue";
import CardTelefone from "components/pessoa/CardTelefone.vue";
import CardEmail from "components/pessoa/CardEmail.vue";
import CardEndereco from "components/pessoa/CardEndereco.vue";
import CardPessoaConta from "components/pessoa/CardPessoaConta.vue";
import CardDependentes from "components/pessoa/CardDependentes.vue";
import CardArquivos from "components/pessoa/CardArquivos.vue";
import CardRegistroSpc from "components/pessoa/CardRegistroSpc.vue";
import CardCertidoes from "components/pessoa/CardCertidoes.vue";
import MGLayout from "layouts/MGLayout.vue";
import { formataFromNow } from "src/utils/formatador";

const route = useRoute();
const sPessoa = pessoaStore();
function carregarPessoa(id) {
  sPessoa.get(id);
}

onMounted(() => {
  carregarPessoa(route.params.id);
});

watch(
  () => route.params.id,
  (novoId) => {
    if (novoId) {
      carregarPessoa(novoId);
    }
  }
);
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
        <div v-if="sPessoa.item" style="max-width: 1280px; margin: auto">
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar
                color="grey-8"
                text-color="grey-4"
                size="80px"
                v-if="sPessoa.item.fantasia"
              >
                {{ sPessoa.item.fantasia.slice(0, 1) }}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <div class="text-h4 text-grey-9">
                {{ sPessoa.item.fantasia }}
              </div>
              <div class="text-h5 text-grey-7">
                {{ sPessoa.item.pessoa }}
                <span v-if="sPessoa.item.inativo" class="text-negative">
                  (Inativo {{ formataFromNow(sPessoa.item.inativo) }})
                </span>
              </div>
            </q-item-section>
          </q-item>

          <div class="row q-col-gutter-md q-pa-md">
            <div class="col-xs-12 col-md-8">
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
            <div class="col-xs-12 col-md-4">
              <div class="row q-col-gutter-md">
                <!-- TELEFONE -->
                <div class="col-12">
                  <card-telefone />
                </div>

                <!-- EMAIL -->
                <div class="col-12">
                  <card-email />
                </div>

                <!-- ENDERECO -->
                <div class="col-12">
                  <card-endereco />
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
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>

<style scoped></style>
