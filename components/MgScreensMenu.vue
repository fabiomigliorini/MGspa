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
    <q-menu anchor="bottom left" self="top left" :offset="[0, 4]" class="bg-grey-4">
      <q-list dense style="width: 200px">
        <template v-for="(group, gi) in visibleGroups" :key="gi">
          <!-- <q-separator v-if="gi > 0" /> -->
          <q-item-label header class="text-weight-bold">
            {{ group.label }}
          </q-item-label>
          <template v-for="item in group.items" :key="item.label">
            <q-item class="q-mb-sm" clickable v-ripple :to="item.to">
              <q-item-section side>
                <q-icon :name="item.icon" size="24px" :color="item.color" />
                <!-- <q-avatar rounded :icon="item.icon" size="36px" :text-color="item.color" /> -->
              </q-item-section>

              <q-item-section>
                <q-item-label class="text-weight-bold ellipsis text-grey-8">
                  {{ item.label }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </template>
      </q-list>
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
