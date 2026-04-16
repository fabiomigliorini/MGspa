<script setup>
defineProps({
  activeCount: { type: Number, default: 0 },
  title: { type: String, default: 'Filtros' },
  headerClass: { type: String, default: 'bg-primary text-white' },
})

defineEmits(['clear'])
</script>

<template>
  <div class="column full-height bg-white">
    <div :class="['q-pa-md', headerClass]">
      <div class="text-h6">
        <q-icon name="filter_list" class="q-mr-sm" />
        {{ title }}
      </div>
      <div class="row items-center justify-between" style="min-height: 32px">
        <div class="text-caption">
          {{ activeCount }}
          {{ activeCount === 1 ? 'filtro ativo' : 'filtros ativos' }}
        </div>
        <q-btn
          v-if="activeCount > 0"
          flat
          dense
          round
          icon="close"
          color="white"
          size="sm"
          @click="$emit('clear')"
        >
          <q-tooltip>Limpar Filtros</q-tooltip>
        </q-btn>
      </div>
    </div>

    <q-separator />

    <div class="q-pa-md">
      <slot />
    </div>
  </div>
</template>
