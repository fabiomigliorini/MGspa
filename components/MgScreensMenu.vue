<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useQuasar } from 'quasar'

const props = defineProps({
  groups: {
    type: Array,
    required: true,
    // [{ label?: string, items: [{ label, icon, color?, to?, href?, target?, hide?: boolean }] }]
  },
})

const $q = useQuasar()

const dialogOpen = ref(false)
const search = ref('')
const selectedIndex = ref(0)
const itemRefs = ref([])

const normalize = (s) =>
  (s || '')
    .toString()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()

const visibleGroups = computed(() =>
  props.groups
    .map((g) => ({
      ...g,
      items: (g.items || []).filter((it) => !it.hide),
    }))
    .filter((g) => g.items.length > 0),
)

const isFiltering = computed(() => search.value && search.value.trim().length > 0)

const flatItems = computed(() => {
  const term = normalize(search.value)
  const out = []
  for (const g of visibleGroups.value) {
    for (const it of g.items) {
      if (!term || normalize(it.label).includes(term)) out.push(it)
    }
  }
  return out
})

const colsPerRow = computed(() => {
  if ($q.screen.lt.sm) return 3
  if ($q.screen.lt.md) return 4
  return 6
})

watch([dialogOpen, search], () => {
  selectedIndex.value = 0
})

watch(flatItems, () => {
  if (selectedIndex.value >= flatItems.value.length) selectedIndex.value = 0
})

watch(selectedIndex, async () => {
  await nextTick()
  const el = itemRefs.value[selectedIndex.value]?.$el
  if (el && typeof el.scrollIntoView === 'function') {
    el.scrollIntoView({ block: 'nearest' })
  }
})

function onKey(e) {
  if (!flatItems.value.length) return
  const n = flatItems.value.length
  const cols = colsPerRow.value
  const key = e.key
  if (key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = Math.min(n - 1, selectedIndex.value + cols)
  } else if (key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = Math.max(0, selectedIndex.value - cols)
  } else if (key === 'ArrowRight') {
    e.preventDefault()
    selectedIndex.value = Math.min(n - 1, selectedIndex.value + 1)
  } else if (key === 'ArrowLeft') {
    e.preventDefault()
    selectedIndex.value = Math.max(0, selectedIndex.value - 1)
  } else if (key === 'Enter') {
    e.preventDefault()
    itemRefs.value[selectedIndex.value]?.$el?.click()
  }
}

function globalIndex(item) {
  return flatItems.value.indexOf(item)
}

function setItemRef(el, idx) {
  if (el) itemRefs.value[idx] = el
}
</script>

<template>
  <q-btn flat round icon="apps" aria-label="Telas" @click="dialogOpen = true" />

  <q-dialog v-model="dialogOpen" :maximized="$q.screen.lt.sm">
    <q-card flat :style="$q.screen.lt.sm ? '' : 'width: 800px; max-width: 90vw'">
      <q-card-section class="q-pa-md">
        <q-input
          v-model="search"
          autofocus
          outlined
          rounded
          clearable
          placeholder="Digite para pesquisar"
          @keydown="onKey"
        >
          <template #prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </q-card-section>

      <q-card-section class="q-pt-none" style="max-height: 70vh; overflow-y: auto">
        <template v-if="!isFiltering">
          <template v-for="(group, gi) in visibleGroups" :key="gi">
            <div class="text-subtitle2 text-grey-7 q-mt-md q-mb-sm">{{ group.label }}</div>
            <div class="row q-col-gutter-sm">
              <div
                v-for="item in group.items"
                :key="item.label"
                class="col-4 col-sm-3 col-md-2"
              >
                <q-btn
                  :ref="(el) => setItemRef(el, globalIndex(item))"
                  flat
                  no-caps
                  class="full-width mg-screens-menu-item"
                  :class="{
                    'mg-screens-menu-item--active': globalIndex(item) === selectedIndex,
                  }"
                  :to="item.to"
                  :href="item.href"
                  :target="item.target"
                  v-close-popup
                >
                  <div class="column items-center q-py-sm full-width">
                    <q-icon :name="item.icon" size="48px" :color="item.color" />
                    <div class="text-caption text-center q-mt-xs ellipsis-2-lines">
                      {{ item.label }}
                    </div>
                  </div>
                </q-btn>
              </div>
            </div>
          </template>
        </template>

        <template v-else>
          <div v-if="!flatItems.length" class="text-center text-grey-6 q-py-lg">
            Nenhum resultado
          </div>
          <div v-else class="row q-col-gutter-sm q-mt-sm">
            <div
              v-for="(item, i) in flatItems"
              :key="item.label"
              class="col-4 col-sm-3 col-md-2"
            >
              <q-btn
                :ref="(el) => setItemRef(el, i)"
                flat
                no-caps
                class="full-width mg-screens-menu-item"
                :class="{ 'mg-screens-menu-item--active': i === selectedIndex }"
                :to="item.to"
                :href="item.href"
                :target="item.target"
                v-close-popup
              >
                <div class="column items-center q-py-sm full-width">
                  <q-icon :name="item.icon" size="48px" :color="item.color" />
                  <div class="text-caption text-center q-mt-xs ellipsis-2-lines">
                    {{ item.label }}
                  </div>
                </div>
              </q-btn>
            </div>
          </div>
        </template>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style>
.mg-screens-menu-item {
  min-height: 96px;
  padding: 8px;
  border-radius: 8px;
}
.mg-screens-menu-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
.mg-screens-menu-item--active {
  background-color: rgba(25, 118, 210, 0.12);
  border: 1px solid rgba(25, 118, 210, 0.4);
}
.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.2;
}
</style>
