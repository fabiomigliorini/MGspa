<script setup>
import { useAuth } from 'src/composables/useAuth'

const { usuario } = useAuth()
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="row items-center no-wrap q-gutter-md">
          <q-avatar size="64px" color="green-1" text-color="green-8" icon="agriculture" />
          <div>
            <div class="text-h5">MG Agro</div>
            <div class="text-subtitle2 text-grey-7">Controle de Safra da Fazenda</div>
            <div v-if="usuario?.usuario" class="text-caption text-grey-6 q-mt-xs">
              Olá, {{ usuario.usuario }}
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-section class="text-body2 text-grey-7">
          Aplicação em desenvolvimento. As telas de controle de safra serão adicionadas aqui, no
          padrão dos apps Contas e Estoque.
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
