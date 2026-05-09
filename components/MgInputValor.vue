<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  decimals: { type: Number, default: 2 },
  min: { type: Number, default: null },
  max: { type: Number, default: null },
  label: { type: String, default: '' },
  prefix: { type: String, default: null },
  suffix: { type: String, default: null },
  outlined: { type: Boolean, default: true },
  dense: { type: Boolean, default: false },
  clearable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  bgColor: { type: String, default: '' },
  rules: { type: Array, default: () => [] },
  stackLabel: { type: Boolean, default: false },
  bottomSlots: { type: Boolean, default: true },
  inputClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const displayRef = ref('')
const focused = ref(false)
const lastValid = ref(null)

const formatter = computed(
  () =>
    new Intl.NumberFormat('pt-BR', {
      minimumFractionDigits: props.decimals,
      maximumFractionDigits: props.decimals,
    }),
)

function formatNumber(n) {
  if (n === null || n === undefined || isNaN(n)) return ''
  return formatter.value.format(n)
}

function roundDecimals(n) {
  const f = Math.pow(10, props.decimals)
  return Math.round(n * f) / f
}

function clamp(n) {
  if (n === null) return null
  if (props.min !== null && n < props.min) return props.min
  if (props.max !== null && n > props.max) return props.max
  return n
}

function parseValor(raw) {
  if (raw === null || raw === undefined) return null
  let s = String(raw).trim()
  if (!s) return null

  let negative = false
  if (s.startsWith('(') && s.endsWith(')')) {
    negative = true
    s = s.slice(1, -1).trim()
  }
  if (s.startsWith('-')) {
    negative = true
    s = s.slice(1).trim()
  } else if (s.startsWith('+')) {
    s = s.slice(1).trim()
  }

  s = s.replace(/[R$€£¥\s]/g, '')
  if (!s) return null
  if (!/^[\d.,]+$/.test(s)) return null

  const hasComma = s.includes(',')
  const hasDot = s.includes('.')

  let intPart = ''
  let decPart = ''

  if (hasComma && hasDot) {
    const lastComma = s.lastIndexOf(',')
    const lastDot = s.lastIndexOf('.')
    if (lastComma > lastDot) {
      intPart = s.slice(0, lastComma).replace(/\./g, '')
      decPart = s.slice(lastComma + 1)
    } else {
      intPart = s.slice(0, lastDot).replace(/,/g, '')
      decPart = s.slice(lastDot + 1)
    }
  } else if (hasComma) {
    const lastComma = s.lastIndexOf(',')
    intPart = s.slice(0, lastComma).replace(/,/g, '')
    decPart = s.slice(lastComma + 1)
  } else if (hasDot) {
    const dots = s.match(/\./g) || []
    const lastDot = s.lastIndexOf('.')
    const after = s.slice(lastDot + 1)
    if (dots.length > 1 || after.length === 3) {
      intPart = s.replace(/\./g, '')
      decPart = ''
    } else {
      intPart = s.slice(0, lastDot)
      decPart = after
    }
  } else {
    intPart = s
  }

  if (!/^\d*$/.test(intPart) || !/^\d*$/.test(decPart)) return null
  if (intPart === '' && decPart === '') return null

  let num = parseFloat(`${intPart || '0'}.${decPart || '0'}`)
  if (isNaN(num)) return null
  if (negative) num = -num
  return roundDecimals(num)
}

const wrappedRules = computed(() => props.rules.map((r) => () => r(lastValid.value)))

watch(
  () => props.modelValue,
  (val) => {
    const n = val === null || val === undefined || val === '' ? null : Number(val)
    lastValid.value = n === null || isNaN(n) ? null : n
    if (!focused.value) {
      displayRef.value = formatNumber(lastValid.value)
    }
  },
  { immediate: true },
)

function emitFromNumber(n) {
  const clamped = n === null ? null : clamp(roundDecimals(n))
  lastValid.value = clamped
  displayRef.value = formatNumber(clamped)
  emit('update:modelValue', clamped)
}

function selectAllInput() {
  const el = inputRef.value?.nativeEl
  if (el && typeof el.select === 'function') {
    setTimeout(() => el.select(), 0)
  }
}

function onFocus() {
  focused.value = true
  if (props.readonly) return
  selectAllInput()
}

function onBlur() {
  focused.value = false
  const text = displayRef.value
  if (text === '' || text === null) {
    if (lastValid.value !== null) emitFromNumber(null)
    else displayRef.value = ''
    return
  }
  const parsed = parseValor(text)
  if (parsed === null) {
    displayRef.value = formatNumber(lastValid.value)
  } else {
    emitFromNumber(parsed)
  }
}

function onTyped(val) {
  displayRef.value = val ?? ''
  if (val === null || val === '') {
    if (lastValid.value !== null) {
      lastValid.value = null
      emit('update:modelValue', null)
    }
    return
  }
  const parsed = parseValor(val)
  if (parsed !== null && parsed !== lastValid.value) {
    lastValid.value = parsed
    emit('update:modelValue', parsed)
  }
}

function onPaste(e) {
  const text = (e.clipboardData?.getData('text') ?? '').trim()
  if (!text) return
  const parsed = parseValor(text)
  if (parsed !== null) {
    e.preventDefault()
    emitFromNumber(parsed)
    selectAllInput()
  }
}

function applyArrow(e) {
  let step = 1
  if (e.ctrlKey || e.metaKey) step = 100
  else if (e.shiftKey) step = 10
  const sign = e.key === 'ArrowUp' ? +1 : -1
  const current = parseValor(displayRef.value) ?? lastValid.value ?? 0
  emitFromNumber(current + sign * step)
  selectAllInput()
}

function onKeydown(e) {
  if (props.readonly) return
  if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
    e.preventDefault()
    e.stopPropagation()
    applyArrow(e)
  }
}
</script>

<template>
  <q-input
    ref="inputRef"
    :model-value="displayRef"
    :label="label"
    :prefix="prefix"
    :suffix="suffix"
    :outlined="outlined"
    :dense="dense"
    :readonly="readonly"
    :bg-color="bgColor"
    :rules="wrappedRules"
    :stack-label="stackLabel"
    :bottom-slots="bottomSlots"
    :input-class="['text-right', inputClass]"
    inputmode="decimal"
    @focus="onFocus"
    @blur="onBlur"
    @paste="onPaste"
    @keydown="onKeydown"
    @update:model-value="onTyped"
  >
    <template v-if="clearable && lastValid !== null && !readonly" #append>
      <q-icon
        name="cancel"
        tabindex="-1"
        class="cursor-pointer"
        @click.stop="emitFromNumber(null)"
      />
    </template>
  </q-input>
</template>
