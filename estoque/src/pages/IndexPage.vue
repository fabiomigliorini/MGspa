<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from 'src/composables/useAuth'
import { menuGroups } from 'src/constants/menu'

const { usuario } = useAuth()
const router = useRouter()
</script>

<template>
  <q-page class="q-pa-md">
    <div class="dashboard-container">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
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
      </q-card>

      <!-- Grupos de telas -->
      <div v-for="group in menuGroups" :key="group.label" class="q-mb-lg">
        <div class="text-overline text-grey-7 q-mb-sm">{{ group.label }}</div>
        <div class="row q-col-gutter-md">
          <div
            v-for="item in group.items"
            :key="item.label"
            class="col-12 col-sm-6 col-md-4 col-lg-3"
          >
            <q-card
              v-ripple
              bordered
              flat
              class="cursor-pointer full-height column"
              @click="router.push(item.to)"
            >
              <q-card-section class="row items-center no-wrap q-gutter-md">
                <q-avatar
                  rounded
                  size="48px"
                  :color="`${item.color}`"
                  text-color="white"
                  :icon="item.icon"
                />
                <div class="col">
                  <div class="text-subtitle1 text-weight-medium">{{ item.label }}</div>
                  <div class="text-caption text-grey-6">{{ item.caption }}</div>
                </div>
                <q-icon name="chevron_right" color="grey-5" size="sm" />
              </q-card-section>
            </q-card>
          </div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<style scoped>
.dashboard-container {
  max-width: 1086px;
  margin: 0 auto;
}
</style>
