<script setup>
import { useAuth } from 'src/composables/useAuth'

const { usuario } = useAuth()
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="row items-center no-wrap q-gutter-md">
          <q-avatar size="64px" color="brown-1" text-color="brown-7" icon="inventory_2" />
          <div>
            <div class="text-h5">MG Estoque</div>
            <div class="text-subtitle2 text-grey-7">
              Cadastro de Produtos e Controle de Estoque
            </div>
            <div v-if="usuario?.usuario" class="text-caption text-grey-6 q-mt-xs">
              Olá, {{ usuario.usuario }}
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-section class="text-body2 text-grey-7">
          Aplicação em desenvolvimento. As telas de cadastro de produtos e controle de estoque
          serão adicionadas aqui, no padrão do app Contas.
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
