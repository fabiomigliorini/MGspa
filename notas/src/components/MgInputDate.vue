<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: null },
  label: { type: String, default: '' },
  timestamp: { type: Boolean, default: false },
  seconds: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  bottomSlots: { type: Boolean, default: false },
  disable: { type: Boolean, default: false },
  rules: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

// timestamp=false          → date only (DD/MM/YYYY ↔ YYYY-MM-DD)
// timestamp=true           → date + time HH:mm (DD/MM/YYYY HH:mm ↔ YYYY-MM-DD HH:mm)
// timestamp=true seconds   → date + time HH:mm:ss (DD/MM/YYYY HH:mm:ss ↔ YYYY-MM-DD HH:mm:ss)
const hasTime = computed(() => props.timestamp)
const hasSeconds = computed(() => props.timestamp && props.seconds)

const inputMask = computed(() => {
  if (hasSeconds.value) return '##/##/#### ##:##:##'
  if (hasTime.value) return '##/##/#### ##:##'
  return '##/##/####'
})

const placeholderText = computed(() => {
  if (hasSeconds.value) return 'DD/MM/AAAA HH:mm:ss'
  if (hasTime.value) return 'DD/MM/AAAA HH:mm'
  return 'DD/MM/AAAA'
})

const isoMask = computed(() => {
  if (hasSeconds.value) return 'YYYY-MM-DD HH:mm:ss'
  if (hasTime.value) return 'YYYY-MM-DD HH:mm'
  return 'YYYY-MM-DD'
})

const timeMask = computed(() => hasSeconds.value ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD HH:mm')

const displayLength = computed(() => {
  if (hasSeconds.value) return 19
  if (hasTime.value) return 16
  return 10
})

// ISO → display
const isoToDisplay = (iso) => {
  if (!iso) return null
  if (hasTime.value) {
    const [datePart, timePart] = iso.split(' ')
    if (!datePart) return null
    const [y, m, d] = datePart.split('-')
    if (hasSeconds.value) {
      const time = timePart ? timePart.substring(0, 8) : '00:00:00'
      return `${d}/${m}/${y} ${time}`
    }
    const time = timePart ? timePart.substring(0, 5) : '00:00'
    return `${d}/${m}/${y} ${time}`
  }
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

// display → ISO
const displayToIso = (display) => {
  if (!display || display.length < displayLength.value) return null
  if (hasTime.value) {
    const [datePart, timePart] = display.split(' ')
    const [d, m, y] = datePart.split('/')
    return `${y}-${m}-${d} ${timePart}`
  }
  const [d, m, y] = display.split('/')
  return `${y}-${m}-${d}`
}

const displayValue = computed({
  get: () => isoToDisplay(props.modelValue),
  set: (val) => emit('update:modelValue', val ? displayToIso(val) : null),
})

const onDatePick = (val) => {
  emit('update:modelValue', val)
}

const onClear = () => {
  emit('update:modelValue', null)
}
</script>

<template>
  <q-input
    :model-value="displayValue"
    @update:model-value="displayValue = $event"
    :label="label"
    :mask="inputMask"
    :placeholder="placeholderText"
    outlined
    :clearable="clearable"
    :bottom-slots="bottomSlots"
    :disable="disable"
    :rules="rules"
    @clear="onClear"
  >
    <template #append>
      <q-icon name="event" class="cursor-pointer">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date
            :model-value="modelValue"
            @update:model-value="onDatePick"
            :mask="isoMask"
          >
            <div class="row items-center justify-end">
              <q-btn v-close-popup label="Fechar" color="primary" flat />
            </div>
          </q-date>
        </q-popup-proxy>
      </q-icon>
      <q-icon v-if="hasTime" name="access_time" class="cursor-pointer q-ml-xs">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-time
            :model-value="modelValue"
            @update:model-value="onDatePick"
            :mask="timeMask"
            :with-seconds="hasSeconds"
          >
            <div class="row items-center justify-end">
              <q-btn v-close-popup label="Fechar" color="primary" flat />
            </div>
          </q-time>
        </q-popup-proxy>
      </q-icon>
    </template>
  </q-input>
</template>
