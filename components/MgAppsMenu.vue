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

const APPS = [
  {
    id: 'outros',
    label: 'Início',
    icon: 'home',
    color: 'brown',
    href: process.env.SISTEMA_URL || '',
  },
  {
    id: 'negocios',
    label: 'Negócios',
    icon: 'handshake',
    color: 'orange',
    href: process.env.NEGOCIOS_URL || '',
  },
  {
    id: 'notas',
    label: 'Notas',
    icon: 'description',
    color: 'blue',
    href: process.env.NOTAS_URL || '',
  },
  {
    id: 'contas',
    label: 'Contas',
    icon: 'account_balance',
    color: 'indigo',
    href: process.env.CONTAS_URL || '',
  },
  {
    id: 'pessoas',
    label: 'Pessoas',
    icon: 'people',
    color: 'teal',
    href: process.env.PESSOAS_URL || '',
  },
]

const currentApp = (process.env.APP_NAME || '').toLowerCase()

const appsItems = computed(() =>
  APPS.map((app) => ({ ...app, ativo: app.id === currentApp })).filter(
    (app) => app.ativo || app.href,
  ),
)

const dialogOpen = ref(false)
const search = ref('')
const selectedIndex = ref(0)
const itemRefs = ref([])

const normalize = (s) => (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

const visibleGroups = computed(() => {
  const merged = [{ label: 'Apps', items: appsItems.value }, ...props.groups]
  return merged
    .map((g) => ({
      ...g,
      items: (g.items || []).filter((it) => !it.hide),
    }))
    .filter((g) => g.items.length > 0)
})

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

const isMobile = computed(() => $q.screen.lt.sm)

const colsPerRow = computed(() => {
  if (isMobile.value) return 3
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

function flatToPosition(flatIdx) {
  let acc = 0
  for (let gi = 0; gi < visibleGroups.value.length; gi++) {
    const len = visibleGroups.value[gi].items.length
    if (flatIdx < acc + len) return { groupIdx: gi, localIdx: flatIdx - acc }
    acc += len
  }
  return null
}

function positionToFlat(groupIdx, localIdx) {
  let acc = 0
  for (let gi = 0; gi < groupIdx; gi++) acc += visibleGroups.value[gi].items.length
  return acc + localIdx
}

function moveVertical(dir) {
  const cols = colsPerRow.value
  const n = flatItems.value.length

  if (isFiltering.value) {
    if (dir > 0) selectedIndex.value = Math.min(n - 1, selectedIndex.value + cols)
    else selectedIndex.value = Math.max(0, selectedIndex.value - cols)
    return
  }

  const pos = flatToPosition(selectedIndex.value)
  if (!pos) return
  const groups = visibleGroups.value
  const group = groups[pos.groupIdx]
  const col = pos.localIdx % cols
  const row = Math.floor(pos.localIdx / cols)

  if (dir > 0) {
    const nextLocalIdx = (row + 1) * cols + col
    if (nextLocalIdx < group.items.length) {
      selectedIndex.value = positionToFlat(pos.groupIdx, nextLocalIdx)
    } else if (pos.groupIdx + 1 < groups.length) {
      const next = groups[pos.groupIdx + 1]
      selectedIndex.value = positionToFlat(pos.groupIdx + 1, Math.min(col, next.items.length - 1))
    }
  } else {
    if (row > 0) {
      selectedIndex.value = positionToFlat(pos.groupIdx, (row - 1) * cols + col)
    } else if (pos.groupIdx > 0) {
      const prev = groups[pos.groupIdx - 1]
      const prevLen = prev.items.length
      const lastRow = Math.floor((prevLen - 1) / cols)
      selectedIndex.value = positionToFlat(
        pos.groupIdx - 1,
        Math.min(lastRow * cols + col, prevLen - 1),
      )
    }
  }
}

function onKey(e) {
  if (!flatItems.value.length) return
  const n = flatItems.value.length
  const key = e.key
  if (key === 'ArrowDown') {
    e.preventDefault()
    moveVertical(1)
  } else if (key === 'ArrowUp') {
    e.preventDefault()
    moveVertical(-1)
  } else if (key === 'ArrowRight') {
    e.preventDefault()
    selectedIndex.value = Math.min(n - 1, selectedIndex.value + 1)
  } else if (key === 'ArrowLeft') {
    e.preventDefault()
    selectedIndex.value = Math.max(0, selectedIndex.value - 1)
  } else if (key === 'Enter') {
    e.preventDefault()
    itemRefs.value[selectedIndex.value]?.$el?.click()
  } else if (key === 'Escape') {
    e.preventDefault()
    dialogOpen.value = false
  }
}

function globalIndex(item) {
  return flatItems.value.indexOf(item)
}

function setItemRef(el, idx) {
  if (el) itemRefs.value[idx] = el
}

function abrirDialog() {
  search.value = ''
  dialogOpen.value = true
}
</script>

<template>
  <q-btn flat round icon="apps" aria-label="Telas" @click="abrirDialog()" />

  <q-dialog
    v-model="dialogOpen"
    :maximized="isMobile"
    content-class="mg-apps-menu-content"
  >
    <q-card
      flat
      class="mg-apps-menu-card"
      :style="isMobile ? '' : 'width: 800px; max-width: 90vw'"
    >
      <q-card-section class="q-pa-md row items-center no-wrap q-gutter-sm">
        <q-input
          v-model="search"
          autofocus
          outlined
          rounded
          clearable
          placeholder="Digite para pesquisar"
          class="col"
          @keydown="onKey"
        >
          <template #prepend>
            <q-icon name="search" />
          </template>
        </q-input>
        <q-btn flat round icon="close" aria-label="Fechar" v-close-popup />
      </q-card-section>

      <q-separator inset />

      <q-card-section class="q-pt-none" style="max-height: 70vh; overflow-y: auto">
        <template v-if="!isFiltering">
          <template v-for="(group, gi) in visibleGroups" :key="gi">
            <div class="text-subtitle2 text-grey-7 q-mt-md q-mb-sm">{{ group.label }}</div>
            <div class="row q-col-gutter-sm">
              <div v-for="item in group.items" :key="item.label" class="col-4 col-sm-3 col-md-2">
                <q-btn
                  :ref="(el) => setItemRef(el, globalIndex(item))"
                  flat
                  no-caps
                  class="full-width mg-screens-menu-item"
                  :class="{
                    'mg-screens-menu-item--active':
                      item.ativo || globalIndex(item) === selectedIndex,
                  }"
                  :to="item.to"
                  :href="item.href"
                  :target="item.target"
                  :disable="item.ativo"
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
            <div v-for="(item, i) in flatItems" :key="item.label" class="col-4 col-sm-3 col-md-2">
              <q-btn
                :ref="(el) => setItemRef(el, i)"
                flat
                no-caps
                class="full-width mg-screens-menu-item"
                :class="{
                  'mg-screens-menu-item--active': item.ativo || i === selectedIndex,
                }"
                :to="item.to"
                :href="item.href"
                :target="item.target"
                :disable="item.ativo"
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
  border: 1px solid rgba(255, 255, 255, 0);
}
.mg-screens-menu-item:hover {
  background-color: rgba(25, 118, 210, 0.12);
  border: 1px solid rgba(25, 118, 210, 0.4);
}
.mg-screens-menu-item--active {
  background-color: rgba(25, 118, 210, 0.12);
  border: 1px solid rgba(25, 118, 210, 0.4);
}
.mg-apps-menu-card {
  background-color: rgba(255, 255, 255, 1) !important;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
}
@media (max-width: 599.98px) {
  .mg-apps-menu-content,
  .mg-apps-menu-content.q-dialog__inner,
  .mg-apps-menu-content.q-dialog__inner--minimized,
  .mg-apps-menu-content.q-dialog__inner--standard {
    padding: 0 !important;
    align-items: stretch !important;
    justify-content: stretch !important;
  }
  .mg-apps-menu-card,
  .q-dialog__inner > .mg-apps-menu-card,
  .mg-apps-menu-content > .mg-apps-menu-card {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    min-width: 100vw !important;
    min-height: 100vh !important;
    border-radius: 0 !important;
    margin: 0 !important;
  }
}
.mg-screens-menu-item.q-btn--disable {
  opacity: 1 !important;
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
