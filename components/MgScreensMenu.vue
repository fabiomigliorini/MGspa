<script setup>
import { computed } from 'vue'

const props = defineProps({
  groups: {
    type: Array,
    required: true,
    // [{ label?: string, items: [{ label, icon, color?, to?, href?, target?, hide?: boolean }] }]
  },
})

const visibleGroups = computed(() =>
  props.groups
    .map((g) => ({
      ...g,
      items: (g.items || []).filter((it) => !it.hide),
    }))
    .filter((g) => g.items.length > 0),
)
</script>

<template>
  <q-btn flat round icon="apps" aria-label="Telas">
    <q-menu anchor="bottom left" self="top left" :offset="[0, 8]">
      <q-card flat style="min-width: 360px">
        <template v-for="(group, gi) in visibleGroups" :key="gi">
          <q-separator v-if="gi > 0" />
          <q-card-section class="q-pa-md">
            <div v-if="group.label" class="text-subtitle2 text-grey-7 q-mb-md">
              {{ group.label }}
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-3" v-for="item in group.items" :key="item.label">
                <q-btn
                  flat
                  no-caps
                  class="full-width mg-screens-menu-item"
                  :to="item.to"
                  :href="item.href"
                  :target="item.target"
                  v-close-popup
                >
                  <div class="column items-center">
                    <q-icon :name="item.icon" size="32px" :color="item.color || 'grey-8'" />
                    <div class="text-caption text-center q-mt-xs">{{ item.label }}</div>
                  </div>
                </q-btn>
              </div>
            </div>
          </q-card-section>
        </template>
      </q-card>
    </q-menu>
  </q-btn>
</template>

<style>
.mg-screens-menu-item {
  min-height: 80px;
  padding: 8px;
  border-radius: 8px;
}
.mg-screens-menu-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
