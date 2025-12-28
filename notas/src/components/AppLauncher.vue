<template>
  <q-btn flat round dense icon="apps">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]" max-width="400px">
      <q-card style="min-width: 350px">
        <q-card-section class="q-pa-md">
          <div class="text-h6 q-mb-md">Aplicações</div>

          <!-- EMISSÕES -->
          <div class="text-subtitle2 text-grey-7 q-mb-sm">Emissões</div>
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-3" v-for="item in emissoesItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs" style="line-height: 1.2">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>

          <q-separator class="q-my-md" />

          <!-- CADASTROS -->
          <div class="text-subtitle2 text-grey-7 q-mb-sm">Cadastros</div>
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-3" v-for="item in cadastrosItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs" style="line-height: 1.2">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>

          <q-separator class="q-my-md" />

          <!-- CADASTROS FISCAIS -->
          <div class="text-subtitle2 text-grey-7 q-mb-sm">Cadastros Fiscais</div>
          <div class="row q-col-gutter-sm">
            <div class="col-3" v-for="item in fiscaisItems" :key="item.label">
              <q-btn flat no-caps class="full-width app-launcher-item" :to="item.to" v-close-popup>
                <div class="column items-center">
                  <q-icon :name="item.icon" size="32px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs" style="line-height: 1.2">
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

<script setup>
const emissoesItems = [
  { label: 'Notas Fiscais', icon: 'description', color: 'blue', to: '/notas' },
  { label: 'DFe Recebidos', icon: 'inbox', color: 'green', to: '/dfe-recebidos' },
  { label: 'NFe Terceiros', icon: 'people', color: 'orange', to: '/nfe-terceiros' },
  { label: 'MDFe', icon: 'local_shipping', color: 'red', to: '/mdfe' },
]

const cadastrosItems = [
  { label: 'Motoristas', icon: 'person', color: 'blue', to: '/motoristas' },
  { label: 'Veículos', icon: 'directions_car', color: 'indigo', to: '/veiculos' },
  { label: 'Conjuntos', icon: 'category', color: 'purple', to: '/conjuntos' },
]

const fiscaisItems = [
  { label: 'Tributos', icon: 'account_balance', color: 'green', to: '/tributos' },
  { label: 'Naturezas', icon: 'inventory', color: 'teal', to: '/naturezas' },
  { label: 'Regras Trib.', icon: 'rule', color: 'cyan', to: '/regras-tributarias' },
  { label: 'CFOP', icon: 'code', color: 'blue', to: '/cfop' },
  { label: 'Localidades', icon: 'public', color: 'indigo', to: '/localidades' },
  { label: 'Tributações', icon: 'calculate', color: 'purple', to: '/tributacoes' },
  { label: 'Certidões', icon: 'verified', color: 'pink', to: '/certidoes' },
]
</script>

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
