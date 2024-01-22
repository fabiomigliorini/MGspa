<script setup>
import { negocioStore } from "stores/negocio";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();

const urlTitulo = (codtitulo) => {
  return process.env.MGSIS_URL + "index.php?r=titulo/view&id=" + codtitulo;
};
</script>
<template>
  <div
    class="row q-col-gutter-md q-px-md"
    v-if="sNegocio.negocio.titulos.length > 0"
  >
    <div
      class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
      v-for="titulo in sNegocio.negocio.titulos"
      :key="titulo.codtitulo"
    >
      <q-list bordered class="rounded-borders">
        <q-item
          clickable
          v-ripple
          :href="urlTitulo(titulo.codtitulo)"
          target="_blank"
        >
          <q-item-section avatar>
            <q-avatar icon="receipt" color="primary" text-color="white">
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ titulo.numero }}
            </q-item-label>
            <q-item-label caption class="ellipsis">
              #{{ String(titulo.codtitulo).padStart(8, "0") }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-separator inset />

        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ moment(titulo.vencimento).format("L") }}
            </q-item-label>
            <q-item-label class="ellipsis" caption lines="1">
              {{ moment(titulo.vencimento).fromNow() }}
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-item>
          <q-item-section>
            <q-item-label>
              R$
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(titulo.debito + titulo.credito)
              }}
            </q-item-label>
            <q-item-label caption lines="1">
              <template v-if="Math.abs(titulo.saldo) > 0">
                <template
                  v-if="
                    titulo.saldo != Math.abs(titulo.debito + titulo.credito)
                  "
                >
                  R$
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(titulo.saldo)
                  }}
                </template>
                Em aberto
              </template>
              <template v-else> Agrupado/Liquidado </template>
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ titulo.tipotitulo }}
            </q-item-label>
            <q-item-label class="ellipsis" caption lines="1">
              {{ titulo.fantasia }}
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-item>
          <q-item-section>
            <q-item-label class="ellipsis" v-if="titulo.boleto">
              Boleto {{ titulo.nossonumero }}
            </q-item-label>
            <q-item-label class="ellipsis" v-else> Sem Boleto </q-item-label>
            <q-item-label class="ellipsis" caption lines="1">
              {{ titulo.portador }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>
  </div>
</template>
