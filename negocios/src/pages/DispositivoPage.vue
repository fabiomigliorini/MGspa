<script setup>
import { computed, onMounted } from 'vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataCodigo, tempoRelativo } from '@components/formatters'
import { dispositivoStore } from 'stores/dispositivo'
import { statusDispositivo } from 'src/utils/dispositivo'

// Lista dos dispositivos (TASK-46), agrupada por filial; o filtro fica na drawer e cada linha
// leva para a página do dispositivo. Administrador vê todos; Gerente, os da filial dele.
const sDispositivo = dispositivoStore()

const grupos = computed(() => {
  const mapa = new Map()
  for (const d of sDispositivo.dispositivos) {
    const filial = d.filial || 'Sem filial'
    if (!mapa.has(filial)) {
      mapa.set(filial, [])
    }
    mapa.get(filial).push(d)
  }
  return [...mapa.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([filial, itens]) => ({ filial, itens }))
})

onMounted(() => sDispositivo.carregar())
</script>

<template>
  <q-page class="q-pa-md bg-grey-2">
    <div style="max-width: 1086px; margin: auto">
      <MgEmptyState v-if="sDispositivo.erroLista" icon="block">
        {{ sDispositivo.erroLista }}
      </MgEmptyState>

      <MgEmptyState
        v-else-if="!sDispositivo.carregando && !sDispositivo.dispositivos.length"
        icon="devices"
      >
        Nenhum dispositivo encontrado.
      </MgEmptyState>

      <q-card v-for="g in grupos" :key="g.filial" flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-weight-medium">{{ g.filial }}</q-item-label>
            <q-item-label caption>
              {{ g.itens.length }} {{ g.itens.length === 1 ? 'dispositivo' : 'dispositivos' }}
            </q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="d in g.itens" :key="d.codpdv" clickable :to="`/dispositivo/${d.codpdv}`">
            <q-item-section avatar>
              <q-avatar
                :icon="statusDispositivo(d).icone"
                :color="statusDispositivo(d).cor"
                text-color="white"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis" :class="{ 'text-strike text-grey-6': d.inativo }">
                {{ d.apelido || 'Sem apelido' }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataCodigo(d.codpdv) }} · {{ d.setor }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                <q-icon :name="d.desktop ? 'desktop_windows' : 'smartphone'" />
                {{ d.plataforma }} {{ d.navegador }} {{ d.versaonavegador }}
                <template v-if="d.ip"> · {{ d.ip }}</template>
                <template v-if="d.sincronizacaocompleta">
                  · sincronizado {{ tempoRelativo(d.sincronizacaocompleta) }}
                </template>
              </q-item-label>
            </q-item-section>
            <q-item-section side class="gt-xs">
              <q-badge :color="statusDispositivo(d).cor" :label="statusDispositivo(d).label" />
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>
  </q-page>
</template>
