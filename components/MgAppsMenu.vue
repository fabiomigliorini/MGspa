<script setup>
import { computed } from 'vue'

// Acessos LITERAIS de process.env são necessários pra Vite/Webpack DefinePlugin
// substituir estaticamente em build time. process.env[key] dinâmico retorna undefined.
const APPS = [
  {
    id: 'pessoas',
    label: 'Pessoas',
    icon: 'people',
    color: 'teal',
    href: process.env.PESSOAS_URL || '',
  },
  {
    id: 'notas',
    label: 'Notas',
    icon: 'description',
    color: 'blue',
    href: process.env.NOTAS_URL || '',
  },
  {
    id: 'contas',
    label: 'Contas',
    icon: 'account_balance',
    color: 'indigo',
    href: process.env.CONTAS_URL || '',
  },
  {
    id: 'negocios',
    label: 'Negócios',
    icon: 'handshake',
    color: 'orange',
    href: process.env.NEGOCIOS_URL || '',
  },
  {
    id: 'mgsis',
    label: 'MGsis',
    icon: 'apps',
    color: 'brown',
    href: process.env.MGSIS_URL || '',
  },
]

const currentApp = (process.env.APP_NAME || '').toLowerCase()

const items = computed(() =>
  APPS.map((app) => ({ ...app, ativo: app.id === currentApp })).filter(
    (app) => app.ativo || app.href,
  ),
)
</script>

<template>
  <q-btn flat round icon="workspaces" aria-label="Aplicações">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]">
      <q-card flat style="min-width: 360px">
        <q-card-section class="q-pa-md">
          <div class="text-subtitle2 text-grey-7 q-mb-md">Aplicações</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="app in items" :key="app.id">
              <q-btn
                flat
                no-caps
                class="full-width mg-apps-menu-item"
                :class="{ 'mg-apps-menu-item--active': app.ativo }"
                :href="app.ativo ? undefined : app.href"
                :disable="app.ativo"
                v-close-popup
              >
                <div class="column items-center">
                  <q-icon :name="app.icon" size="32px" :color="app.color" />
                  <div class="text-caption text-center q-mt-xs">{{ app.label }}</div>
                </div>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-menu>
  </q-btn>
</template>

<style>
.mg-apps-menu-item {
  min-height: 80px;
  padding: 8px;
  border-radius: 8px;
}
.mg-apps-menu-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
.mg-apps-menu-item--active.q-btn--disable {
  opacity: 1 !important;
  background-color: rgba(25, 118, 210, 0.12);
  border: 1px solid rgba(25, 118, 210, 0.4);
}
</style>
