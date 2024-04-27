<script setup>
import { onMounted, onUnmounted } from "vue";
import { liquidacaoStore } from "stores/liquidacao";
import SelectPessoa from "components/selects/SelectPessoa.vue";
import SelectPdv from "components/selects/SelectPdv.vue";
import SelectUsuario from "components/selects/SelectUsuario.vue";
import SelectPortador from "components/selects/SelectPortador.vue";
const sLiquidacao = liquidacaoStore();

onMounted(() => {
  sLiquidacao.inicializaFiltro();
});
</script>
<template>
  <q-list>
    <q-item-label header>Filtro </q-item-label>

    <!-- PDV -->
    <q-item>
      <q-item-section>
        <select-pdv
          outlined
          v-model="sLiquidacao.filtro.codpdv"
          label="PDV"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- USUARIO -->
    <q-item>
      <q-item-section>
        <select-usuario
          outlined
          v-model="sLiquidacao.filtro.codusuariocriacao"
          :somente-ativos="false"
          label="Usuário"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- PORTADOR -->
    <q-item>
      <q-item-section>
        <select-portador
          outlined
          v-model="sLiquidacao.filtro.codportador"
          :somente-ativos="false"
          label="Portador"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- CODLIQUIDACAO -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          type="number"
          step="1"
          min="1"
          input-class="text-right"
          v-model="sLiquidacao.filtro.codliquidacao"
          label="# Liquidação"
        />
      </q-item-section>
    </q-item>

    <!-- LANCAMENTO_DE -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sLiquidacao.filtro.transacao_de"
          input-class="text-center"
          label="De"
          mask="##/##/#### ##:##"
        >
          <template v-slot:prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-date
                  v-model="sLiquidacao.filtro.transacao_de"
                  mask="DD/MM/YYYY HH:mm"
                >
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
          <template v-slot:append>
            <q-icon name="access_time" class="cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-time
                  v-model="sLiquidacao.filtro.transacao_de"
                  mask="DD/MM/YYYY HH:mm"
                  format24h
                >
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-time>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </q-item-section>
    </q-item>

    <!-- LANCAMENTO_ATE -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sLiquidacao.filtro.transacao_ate"
          input-class="text-center"
          label="Até"
          mask="##/##/#### ##:##"
        >
          <template v-slot:prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-date
                  v-model="sLiquidacao.filtro.transacao_ate"
                  mask="DD/MM/YYYY HH:mm"
                >
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
          <template v-slot:append>
            <q-icon name="access_time" class="cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-time
                  v-model="sLiquidacao.filtro.transacao_ate"
                  mask="DD/MM/YYYY HH:mm"
                  format24h
                >
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Close" color="primary" flat />
                  </div>
                </q-time>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </q-item-section>
    </q-item>

    <!-- PESQUISAR EM -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sLiquidacao.filtro.pesquisar"
          label="Pesquisar por"
          clearable
          :options="[
            { value: 'LIQ', label: 'Total Liquidação' },
            { value: 'MOV', label: 'Títulos' },
          ]"
          map-options
          emit-value
        />
      </q-item-section>
    </q-item>

    <!-- PESSOA -->
    <q-item>
      <q-item-section>
        <select-pessoa
          outlined
          v-model="sLiquidacao.filtro.codpessoa"
          label="Pessoa"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- TIPO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sLiquidacao.filtro.tipo"
          label="Tipo"
          clearable
          :options="[
            { value: 'DB', label: 'Débito' },
            { value: 'CR', label: 'Crédito' },
          ]"
          map-options
        />
      </q-item-section>
    </q-item>

    <!-- VALOR -->
    <q-item>
      <q-item-section>
        <div class="row q-col-gutter-sm">
          <q-input
            outlined
            type="number"
            step="0.01"
            min="0.01"
            input-class="text-right"
            v-model="sLiquidacao.filtro.valor_de"
            :max="sLiquidacao.filtro.valor_ate"
            label="Valor de"
            class="col-6"
            prefix="R$"
          />
          <q-input
            outlined
            type="number"
            step="0.01"
            :min="
              sLiquidacao.filtro.valor_de > 0
                ? sLiquidacao.filtro.valor_de
                : 0.01
            "
            input-class="text-right"
            v-model="sLiquidacao.filtro.valor_ate"
            label="até"
            class="col-6"
            prefix="R$"
          />
        </div>
      </q-item-section>
    </q-item>

    <!-- INTEGRACAO -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sLiquidacao.filtro.integracao"
          multiple
          :options="sLiquidacao.opcoes.integracao"
          label="Integração Pagamento"
          clearable
        />
      </q-item-section>
    </q-item>

    <!-- FIM -->
  </q-list>
</template>
