<script setup>
const emissoesItems = [{ label: 'Notas Fiscais', icon: 'description', color: 'blue', to: '/nota' }]

const cadastrosItems = [{ label: 'Dashboard Inicial', icon: 'home', color: 'brown', to: '/' }]

const fiscaisItems = [
  { label: 'Reforma', icon: 'account_balance', color: 'green', to: '/tributacao' },
  { label: 'CFOPs', icon: 'swap_horiz', color: 'warning', to: '/cfop' },
  { label: 'Cidades', icon: 'location_city', color: 'purple', to: '/cidade' },
]
</script>

<template>
  <q-btn flat round dense icon="apps">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]" max-width="400px">
      <q-card style="min-width: 350px">
        <q-card-section class="q-pa-md">
          <div class="text-h6">Aplicações</div>
          <q-separator class="q-my-sm" />

          <!-- EMISSÕES -->
          <div class="text-subtitle2 text-grey-7">Emissões</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in emissoesItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>

          <q-separator class="q-mb-sm" />

          <!-- CADASTROS FISCAIS -->
          <div class="text-subtitle2 text-grey-7">Cadastros Fiscais</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in fiscaisItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center" style="line-height: 1.2">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>
          <q-separator class="q-mb-sm" />

          <!-- INICIO -->
          <div class="text-subtitle2 text-grey-7">Inicio</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in cadastrosItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center" style="line-height: 1.2">
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

<style scoped>
.app-launcher-item {
  min-height: 80px;
  padding: 8px;
}

.app-launcher-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 8px;
}
</style>
