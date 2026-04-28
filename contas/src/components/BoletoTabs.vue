<template>
  <q-tabs align="left" dense class="bg-primary text-white" indicator-color="white">
    <q-route-tab
      :to="{ name: 'boleto-abertos', query: { tipo: 'vencidos' } }"
      icon="pending_actions"
      label="Abertos"
      no-caps
    />
    <q-route-tab :to="{ name: 'boleto-liquidados' }" icon="task_alt" label="Liquidados" no-caps />
    <q-route-tab :to="{ name: 'boleto-baixados' }" icon="archive" label="Baixados" no-caps />
  </q-tabs>
</template>
