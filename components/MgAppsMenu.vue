<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  groups: {
    type: Array,
    required: true,
    // [{ label?: string, items: [{ label, icon, color?, to?, href?, target?, hide?: boolean }] }]
  },
})

const APPS = [
  {
    id: 'outros',
    label: 'Sistemas',
    icon: 'apps',
    color: 'green',
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
  {
    id: 'estoque',
    label: 'Estoque',
    icon: 'inventory_2',
    color: 'brown',
    href: process.env.ESTOQUE_URL || '',
  },
  {
    id: 'agro',
    label: 'Agro',
    icon: 'agriculture',
    color: 'green',
    href: process.env.AGRO_URL || '',
  },
]

const currentApp = (process.env.APP_NAME || '').toLowerCase()

const appsItems = computed(() => APPS.filter((app) => app.id !== currentApp && app.href))

const dialogOpen = ref(false)
const search = ref('')
const selectedIndex = ref(-1)
const itemRefs = ref([])
const viewportWidth = ref(window.innerWidth)

const isMobile = computed(() => viewportWidth.value < 600)

const colsPerRow = computed(() => {
  if (viewportWidth.value < 600) return 3
  if (viewportWidth.value < 1024) return 4
  return 6
})

const normalize = (s) => (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

const visibleGroups = computed(() => {
  const merged = [...props.groups, { label: 'Outros Apps', items: appsItems.value }]
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

watch(dialogOpen, () => {
  selectedIndex.value = -1
})

watch(search, () => {
  if (isFiltering.value) {
    if (selectedIndex.value < 0) selectedIndex.value = 0
  } else {
    selectedIndex.value = -1
  }
})

watch(flatItems, () => {
  if (selectedIndex.value >= flatItems.value.length) selectedIndex.value = -1
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
  const isArrow =
    key === 'ArrowDown' || key === 'ArrowUp' || key === 'ArrowRight' || key === 'ArrowLeft'
  if (isArrow && selectedIndex.value < 0) {
    e.preventDefault()
    selectedIndex.value = 0
    return
  }
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
  viewportWidth.value = window.innerWidth
  dialogOpen.value = true
}

function handleShortcut(e) {
  if (e.ctrlKey && !e.altKey && !e.shiftKey && !e.metaKey && e.code === 'KeyM') {
    e.preventDefault()
    if (dialogOpen.value) dialogOpen.value = false
    else abrirDialog()
  }
}

onMounted(() => window.addEventListener('keydown', handleShortcut))
onBeforeUnmount(() => window.removeEventListener('keydown', handleShortcut))
</script>

<template>
  <q-btn flat round icon="apps" aria-label="Telas" @click="abrirDialog()">
    <q-tooltip>Apps e Telas · Ctrl+M</q-tooltip>
  </q-btn>

  <q-dialog v-model="dialogOpen" :maximized="isMobile">
    <q-card
      flat
      class="column no-wrap"
      :style="isMobile ? '' : 'width: 800px; max-width: 90vw; height: 600px; max-height: 80vh'"
    >
      <q-card-section class="q-pa-md">
        <div class="row no-wrap items-center q-gutter-sm">
          <q-input
            v-model="search"
            :autofocus="!isMobile"
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
        </div>
      </q-card-section>

      <q-separator inset />

      <q-card-section class="q-pt-none col scroll">
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
  border: 1px solid transparent;
}
.mg-screens-menu-item:hover {
  background-color: rgba(25, 118, 210, 0.12);
  border-color: rgba(25, 118, 210, 0.4);
}
.mg-screens-menu-item--active {
  background-color: rgba(25, 118, 210, 0.12);
  border-color: rgba(25, 118, 210, 0.4);
}
.mg-screens-menu-item.q-btn--disable {
  opacity: 1 !important;
}
</style>
