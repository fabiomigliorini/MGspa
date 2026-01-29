<script setup>
const emissoesItems = [
  { label: 'Início', icon: 'home', color: 'brown', to: '/' },
  { label: 'Notas', icon: 'description', color: 'blue', to: '/nota' },
]

// const cadastrosItems = []

const fiscaisItems = [
  { label: 'Reforma', icon: 'account_balance', color: 'green', to: '/tributacao' },
  { label: 'Naturezas', icon: 'percent', color: 'green', to: '/natureza-operacao' },
  { label: 'Tributações', icon: 'receipt_long', color: 'teal', to: '/tributacao-cadastro' },
  { label: 'CFOPs', icon: 'compare_arrows', color: 'warning', to: '/cfop' },
  { label: 'Cidades', icon: 'location_city', color: 'purple', to: '/cidade' },
]
</script>

<template>
  <q-btn flat round dense icon="apps">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]" max-width="400px">
      <q-card style="min-width: 350px">
        <q-card-section class="q-pa-md">
          <!-- <div class="text-h6">Aplicações</div>
          <q-separator class="q-my-sm" /> -->

          <!-- EMISSÕES -->
          <div class="text-subtitle2 text-grey-7 q-mb-md">Emissões</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in emissoesItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>

          <!-- <q-separator class="q-mb-sm" /> -->

          <!-- CADASTROS FISCAIS -->
          <div class="text-subtitle2 text-grey-7 q-my-md">Parametrização</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in fiscaisItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>

          <!-- INICIO -->
          <!--
          <q-separator class="q-mb-sm" />

          <div class="text-subtitle2 text-grey-7">Inicio</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in cadastrosItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>
          -->
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
