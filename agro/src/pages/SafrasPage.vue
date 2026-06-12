<script setup>
import { onMounted } from 'vue'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'
import MgIconeCultura from 'components/MgIconeCultura.vue'
import MgRadioCultura from 'components/MgRadioCultura.vue'

const cad = useCadastro('safra', 'codsafra', 'Safra')

// Maior ano de plantio já cadastrado pra cultura (a lista vem ordenada por
// -anoplantio, então o último está na primeira página).
function ultimoAnoPlantio(codcultura) {
  const anos = cad.items
    .filter((s) => s.codcultura === codcultura && s.anoplantio)
    .map((s) => s.anoplantio)
  return anos.length ? Math.max(...anos) : null
}

// Ao escolher a cultura numa safra nova, sugere os anos: plantio = último ano
// cadastrado + 1 (ou ano atual se for a primeira safra da cultura); colheita =
// plantio + (cicloanos - 1) — milho (ciclo 1) colhe no mesmo ano, soja (ciclo
// 2) colhe no ano seguinte.
function onCultura(cultura) {
  if (!cad.isNovo || !cultura) return
  const ultimo = ultimoAnoPlantio(cultura.codcultura)
  const plantio = ultimo ? ultimo + 1 : new Date().getFullYear()
  cad.form.anoplantio = plantio
  cad.form.anocolheita = plantio + (Number(cultura.cicloanos || 1) - 1)
}

function safraAnos(s) {
  if (!s.anoplantio) return 'Sem ano'
  return s.anocolheita && s.anocolheita !== s.anoplantio
    ? `${s.anoplantio}/${s.anocolheita}`
    : `${s.anoplantio}`
}

onMounted(() => cad.carregar())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Safras</div>
            <div class="text-caption text-grey-7">Abra uma safra para ver plantio e produtividade</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo()">
            <q-tooltip>Nova safra</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="s in cad.items" :key="s.codsafra" class="col-12 col-sm-6">
          <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': s.inativo }">
            <q-item clickable v-ripple :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }">
              <q-item-section avatar>
                <MgIconeCultura :codcultura="s.codcultura" fallback-color="light-green-8" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ s.safra }}</q-item-label>
                <q-item-label caption>
                  {{ s.cultura?.cultura || '—' }} · {{ safraAnos(s) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>

      <q-banner v-else rounded class="bg-grey-2 text-grey-7">
        Nenhuma safra. Crie a primeira com o botão <q-icon name="add" />.
      </q-banner>

      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 440px; max-width: 90vw">
          <q-form @submit="cad.salvar()">
            <q-card-section>
              <div class="text-h6">{{ cad.isNovo ? 'Nova Safra' : 'Editar Safra' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <MgRadioCultura v-model="cad.form.codcultura" @change="onCultura" />
              <q-input
                v-model="cad.form.safra"
                label="Safra"
                hint="Ex.: Milho 2ª Safra 2026"
                outlined
                autofocus
              />
              <div class="row q-col-gutter-md">
                <MgInputValor
                  v-model="cad.form.anoplantio"
                  :decimals="0"
                  :min="2000"
                  :max="2100"
                  label="Ano de plantio"
                  class="col-6"
                />
                <MgInputValor
                  v-model="cad.form.anocolheita"
                  :decimals="0"
                  :min="2000"
                  :max="2100"
                  label="Ano de colheita"
                  class="col-6"
                />
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
