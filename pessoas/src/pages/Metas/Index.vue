<script setup>

import { onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router'
import MgLayout from 'src/layouts/MGLayout.vue';
import metasStore from 'src/stores/metas';
import moment from "moment";
import GraficoMetasFilial from 'src/components/metas/GraficoMetasFilial.vue';

const route = useRoute();
const router = useRouter();
const sMetas = metasStore();

const corProgresso = (progresso) => {
  if (!progresso) {
    return 'grey';
  }
  if (progresso >= 0.99) {
    return 'primary';
  }
  return 'negative'
}

onMounted(() => {
  sMetas.getListagem().then(() => {
    if (route.params.codmeta) {
      return;
    }
    if (sMetas.listagem.length == 0) {
      return;
    }
    router.push("/metas/" + sMetas.listagem[0].codmeta);
  });
});

watch(
  () => route.params.codmeta,
  (newValue) => {
    sMetas.get(newValue);
  }
)


</script>
<template>
  <mg-layout drawer>

    <template #tituloPagina>
      Metas
    </template>

    <template #drawer>
      <q-list>
        <template v-for="meta in sMetas.listagem" :key="meta.codmeta">

          <q-item clickable v-ripple :to="'/metas/' + meta.codmeta">
            <q-item-section top avatar>
              <q-avatar color="primary" text-color="white">
                {{ moment(meta.periodofinal).format('M') }}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ moment(meta.periodofinal).format('MMMM/YYYY') }}</q-item-label>
              <q-item-label caption>
                {{ moment(meta.periodoinicial).format('D') }} a
                {{ moment(meta.periodofinal).format('D/MMM/YYYY') }}
              </q-item-label>
            </q-item-section>

          </q-item>
          <q-separator inset />

        </template>

      </q-list>
    </template>

    <template #content>
      <div class="q-px-md q-mb-xl">
        <h3>
          Metas de
          {{ moment(sMetas.meta.periodoinicial).format('DD/MMM') }} à
          {{ moment(sMetas.meta.periodofinal).format('D/MMM/YYYY') }}
        </h3>

        <div class="row q-col-gutter-md">
          <template v-for="filial in sMetas.meta.filiais" :key="filial.codfilial">
            <div class="col-md-4">
              <q-card flat bordered>

                <q-card-section>

                  <!-- FILIAL -->
                  <div class="row no-wrap items-center text-h6 ellipsis">
                    {{ filial.filial }}
                  </div>

                  <!-- ESTRELAS -->
                  <div class="row no-wrap items-center">
                    <q-rating size="18px" v-model="filial.estrelas" :max="5" :color="corProgresso(filial.progresso)"
                      readonly />
                    <span class="text-caption text-grey q-ml-sm" v-if="filial.valormetafilial">
                      {{
                        new Intl.NumberFormat(
                          'pt-BR',
                          {
                            style: "decimal",
                            maximumFractionDigits: 1
                          }
                        ).format(filial.estrelas)
                      }}
                      ({{
                        new Intl.NumberFormat(
                          'pt-BR',
                          {
                            style: "decimal",
                            maximumFractionDigits: 1
                          }
                        ).format(filial.progresso * 100)
                      }}% da meta)
                    </span>
                  </div>

                </q-card-section>

                <q-card-section class="text-center bg-grey-2 q-pa-none">

                  <!-- GRAFICO -->
                  <grafico-metas-filial :filial="filial" />

                  <!-- PROGRESSO -->
                  <q-linear-progress :value="filial.progresso" class="" size="8px" stripe
                    :color="corProgresso(filial.progresso)">
                  </q-linear-progress>

                </q-card-section>

                <q-list>

                  <!-- META -->
                  <q-item v-if="filial.valormetafilial">
                    <q-item-section avatar>
                      <q-icon color="grey-6" name="local_bar" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Meta</q-item-label>
                      <q-item-label caption v-if="filial.valorvenda >= filial.valormetafilial">
                        Meta Batida
                      </q-item-label>
                      <q-item-label caption v-else>
                        Faltando
                        {{
                          new Intl.NumberFormat('pt-BR',
                            {
                              style: "decimal",
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2
                            }).format(
                              parseFloat(filial.valormetafilial - filial.valorvenda)
                            )
                        }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      {{
                        new Intl.NumberFormat('pt-BR',
                          {
                            style: "decimal",
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                          }).format(
                            parseFloat(filial.valormetafilial)
                          )
                      }}
                    </q-item-section>
                  </q-item>

                  <!-- VENDAS -->
                  <q-item>
                    <q-item-section avatar>
                      <q-icon color="grey-6" name="local_bar" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Vendas da Filial</q-item-label>
                      <q-item-label caption>
                        Sem o desconto
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      {{
                        new Intl.NumberFormat('pt-BR',
                          {
                            style: "decimal",
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                          }).format(
                            parseFloat(filial.valorvenda)
                          )
                      }}
                    </q-item-section>
                  </q-item>

                  <!-- COMISSAO -->
                  <q-item>
                    <q-item-section avatar>
                      <q-icon color="grey-6" name="local_bar" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Comissão Filial</q-item-label>
                      <q-item-label caption>
                        {{
                          new Intl.NumberFormat('pt-BR',
                            {
                              style: "decimal",
                              minimumFractionDigits: 1,
                              maximumFractionDigits: 1
                            }).format(
                              parseFloat(sMetas.meta.percentualcomissaosubgerentemeta)
                            )
                        }}% das vendas
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      {{
                        new Intl.NumberFormat('pt-BR',
                          {
                            style: "decimal",
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                          }).format(
                            parseFloat(filial.valorcomissao)
                          )
                      }}
                    </q-item-section>
                  </q-item>

                </q-list>

              </q-card>

            </div>

          </template>

        </div>

      </div>
      <!-- <pre>{{ sMetas.meta }}</pre> -->
    </template>

    <template #botaoVoltar>

    </template>

  </mg-layout>
</template>
