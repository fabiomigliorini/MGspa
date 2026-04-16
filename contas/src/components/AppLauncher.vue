<script setup>
const appsItems = [
  { label: 'Notas', icon: 'description', color: 'blue', href: process.env.NOTAS_URL },
  { label: 'Pessoas', icon: 'people', color: 'teal', href: process.env.PESSOAS_URL },
  { label: 'Negócios', icon: 'handshake', color: 'orange', href: process.env.NEGOCIOS_URL },
  { label: 'MGsis', icon: 'apps', color: 'brown', href: process.env.MGSIS_URL },
]

const cadastrosItems = [
  { label: 'Bancos', icon: 'account_balance', color: 'red-8', to: { name: 'banco' } },
  {
    label: 'Contas Contábeis',
    icon: 'account_tree',
    color: 'indigo-8',
    to: { name: 'conta-contabil' },
  },
  {
    label: 'Tipos de Título',
    icon: 'receipt_long',
    color: 'teal-8',
    to: { name: 'tipo-titulo' },
  },
  {
    label: 'Tipos de Movimento',
    icon: 'sync_alt',
    color: 'deep-purple-8',
    to: { name: 'tipo-movimento-titulo' },
  },
  {
    label: 'Portadores',
    icon: 'credit_card',
    color: 'cyan-8',
    to: { name: 'portador' },
  },
  {
    label: 'Formas de Pagamento',
    icon: 'payments',
    color: 'green-8',
    to: { name: 'forma-pagamento' },
  },
]
</script>

<template>
  <q-btn flat round dense icon="apps">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]" max-width="400px">
      <q-card style="min-width: 350px">
        <q-card-section class="q-pa-md">
          <div class="text-subtitle2 text-grey-7 q-mb-md">Aplicações</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in appsItems" :key="item.label">
              <q-btn
                flat
                no-caps
                class="full-width app-launcher-item"
                :href="item.href"
                target="_blank"
                v-close-popup
              >
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-md">
          <div class="text-subtitle2 text-grey-7 q-mb-md">Cadastros</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in cadastrosItems" :key="item.label">
              <q-btn
                flat
                no-caps
                class="full-width app-launcher-item"
                :to="item.to"
                v-close-popup
              >
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-menu>
  </q-btn>
</template>
