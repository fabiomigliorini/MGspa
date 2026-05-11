<script setup>
import { ref, computed, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: { type: String, default: null },
  type: {
    type: String,
    default: "date",
    validator: (v) => ["date", "timestamp"].includes(v),
  },
  seconds: { type: Boolean, default: true },
  defaultTime: {
    type: String,
    default: "start",
    validator: (v) => ["start", "end", "now"].includes(v),
  },
  yearDigits: {
    type: [Number, String],
    default: 4,
    validator: (v) => +v === 2 || +v === 4,
  },
  label: { type: String, default: "" },
  min: { type: String, default: null },
  max: { type: String, default: null },
  readonly: { type: Boolean, default: false },
  rules: { type: Array, default: () => [] },
  outlined: { type: Boolean, default: true },
  dense: { type: Boolean, default: false },
  stackLabel: { type: Boolean, default: false },
  inputClass: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const inputRef = ref(null);
const popupRef = ref(null);
const popupOpen = ref(false);
const hourRef = ref(null);
const minuteRef = ref(null);
const secondRef = ref(null);

const isTimestamp = computed(() => props.type === "timestamp");
const hasSeconds = computed(() => isTimestamp.value && props.seconds);

const computedMask = computed(() => {
  const datePart = +props.yearDigits === 2 ? "##/##/##" : "##/##/####";
  if (!isTimestamp.value) return datePart;
  if (hasSeconds.value) return `${datePart} ##:##:##`;
  return `${datePart} ##:##`;
});

const displayLength = computed(() => {
  const dl = +props.yearDigits === 2 ? 8 : 10;
  if (!isTimestamp.value) return dl;
  if (hasSeconds.value) return dl + 9;
  return dl + 6;
});

const qDateMask = computed(() => {
  if (!isTimestamp.value) return "YYYY-MM-DD";
  if (hasSeconds.value) return "YYYY-MM-DD HH:mm:ss";
  return "YYYY-MM-DD HH:mm";
});

const pad = (n, w = 2) => String(n).padStart(w, "0");

function expand2DigitYear(yy) {
  const n = +yy;
  return n <= 49 ? 2000 + n : 1900 + n;
}

function validatedDate(y, mo, d, h = 0, mi = 0, s = 0) {
  const dt = new Date(y, mo - 1, d, h, mi, s);
  if (
    dt.getFullYear() !== y ||
    dt.getMonth() !== mo - 1 ||
    dt.getDate() !== d ||
    dt.getHours() !== h ||
    dt.getMinutes() !== mi ||
    dt.getSeconds() !== s
  ) {
    return null;
  }
  return dt;
}

function parseIso(iso) {
  if (!iso) return null;
  const m = String(iso).match(
    /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/,
  );
  if (!m) return null;
  return validatedDate(
    +m[1],
    +m[2],
    +m[3],
    m[4] ? +m[4] : 0,
    m[5] ? +m[5] : 0,
    m[6] ? +m[6] : 0,
  );
}

function dateToIso(dt) {
  if (!dt) return null;
  const datePart = `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
  if (!isTimestamp.value) return datePart;
  if (hasSeconds.value) {
    return `${datePart} ${pad(dt.getHours())}:${pad(dt.getMinutes())}:${pad(dt.getSeconds())}`;
  }
  return `${datePart} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

function dateToDisplay(dt) {
  if (!dt) return "";
  const yStr =
    +props.yearDigits === 2
      ? pad(dt.getFullYear() % 100)
      : String(dt.getFullYear());
  const datePart = `${pad(dt.getDate())}/${pad(dt.getMonth() + 1)}/${yStr}`;
  if (!isTimestamp.value) return datePart;
  if (hasSeconds.value) {
    return `${datePart} ${pad(dt.getHours())}:${pad(dt.getMinutes())}:${pad(dt.getSeconds())}`;
  }
  return `${datePart} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

function parseDisplay(str) {
  if (!str) return null;
  const m = str.match(
    /^(\d{2})\/(\d{2})\/(\d{2}|\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?\s*$/,
  );
  if (!m) return null;
  let y = +m[3];
  if (m[3].length === 2) y = expand2DigitYear(m[3]);
  return validatedDate(
    y,
    +m[2],
    +m[1],
    m[4] ? +m[4] : 0,
    m[5] ? +m[5] : 0,
    m[6] ? +m[6] : 0,
  );
}

function parsePasted(raw) {
  const isoRe =
    /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)?\s*$/;
  let m = raw.match(isoRe);
  if (m) {
    return validatedDate(
      +m[1],
      +m[2],
      +m[3],
      m[4] ? +m[4] : 0,
      m[5] ? +m[5] : 0,
      m[6] ? +m[6] : 0,
    );
  }
  const brRe =
    /^(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?\s*$/;
  m = raw.match(brRe);
  if (m) {
    let y = +m[3];
    if (m[3].length === 2) y = expand2DigitYear(m[3]);
    return validatedDate(
      y,
      +m[2],
      +m[1],
      m[4] ? +m[4] : 0,
      m[5] ? +m[5] : 0,
      m[6] ? +m[6] : 0,
    );
  }
  const t = Date.parse(raw);
  if (!isNaN(t)) return new Date(t);
  return null;
}

function addMonths(dt, n) {
  const r = new Date(dt);
  const day = r.getDate();
  r.setDate(1);
  r.setMonth(r.getMonth() + n);
  const lastDay = new Date(r.getFullYear(), r.getMonth() + 1, 0).getDate();
  r.setDate(Math.min(day, lastDay));
  return r;
}

function addYears(dt, n) {
  return addMonths(dt, n * 12);
}

function clampToRange(dt) {
  if (!dt) return dt;
  const minDt = props.min ? parseIso(props.min) : null;
  const maxDt = props.max ? parseIso(props.max) : null;
  if (minDt && dt < minDt) return new Date(minDt);
  if (maxDt && dt > maxDt) return new Date(maxDt);
  return dt;
}

const lastValid = ref(null);
const displayRef = ref("");

watch(
  () => props.modelValue,
  (iso) => {
    const dt = parseIso(iso);
    lastValid.value = dt;
    displayRef.value = dt ? dateToDisplay(dt) : "";
  },
  { immediate: true },
);

watch(
  () => !!displayRef.value && !props.readonly,
  async (visible) => {
    if (!visible) return;
    await nextTick();
    const fieldRoot = inputRef.value?.nativeEl?.closest?.(".q-field");
    fieldRoot
      ?.querySelector?.(".q-field__focusable-action")
      ?.setAttribute("tabindex", "-1");
  },
  { immediate: true },
);

const qDateValue = computed(() =>
  lastValid.value ? dateToIso(lastValid.value) : null,
);

const hourValue = computed(() =>
  lastValid.value ? lastValid.value.getHours() : null,
);
const minuteValue = computed(() =>
  lastValid.value ? lastValid.value.getMinutes() : null,
);
const secondValue = computed(() =>
  lastValid.value ? lastValid.value.getSeconds() : null,
);

function setTimePart(part, val) {
  if (val === "" || val == null) return;
  let num = parseInt(val, 10);
  if (Number.isNaN(num)) return;
  const base = lastValid.value ? new Date(lastValid.value) : new Date();
  base.setMilliseconds(0);
  if (part === "h") {
    num = Math.max(0, Math.min(23, num));
    base.setHours(num);
  } else if (part === "m") {
    num = Math.max(0, Math.min(59, num));
    base.setMinutes(num);
  } else if (part === "s") {
    num = Math.max(0, Math.min(59, num));
    base.setSeconds(num);
  } else {
    return;
  }
  emitFromDate(clampToRange(base));
}

function onTimeKeydown(e, part, isFirst, isLast) {
  if (e.key === "Tab") {
    if (e.shiftKey && isFirst) {
      e.preventDefault();
      inputRef.value?.focus?.();
      selectAllInput();
      return;
    }
    if (!e.shiftKey && isLast) {
      popupRef.value?.hide();
    }
    return;
  }
  if (e.key === "ArrowDown" || e.key === "ArrowUp") {
    const current =
      part === "h"
        ? hourValue.value
        : part === "m"
          ? minuteValue.value
          : secondValue.value;
    const max = part === "h" ? 23 : 59;
    if (e.key === "ArrowDown" && (current == null || current === 0)) {
      e.preventDefault();
      setTimePart(part, max);
    } else if (e.key === "ArrowUp" && (current == null || current === max)) {
      e.preventDefault();
      setTimePart(part, 0);
    }
  }
}

const dateOptions = computed(() => {
  if (!props.min && !props.max) return undefined;
  const minDate = props.min ? props.min.substring(0, 10) : null;
  const maxDate = props.max ? props.max.substring(0, 10) : null;
  return (dateStr) => {
    const part = String(dateStr).substring(0, 10);
    if (minDate && part < minDate) return false;
    if (maxDate && part > maxDate) return false;
    return true;
  };
});

function emitFromDate(dt) {
  lastValid.value = dt;
  displayRef.value = dateToDisplay(dt);
  emit("update:modelValue", dateToIso(dt));
}

function selectAllInput() {
  const el = inputRef.value?.nativeEl;
  if (el && typeof el.select === "function") {
    setTimeout(() => el.select(), 0);
  }
}

function onFocus() {
  if (props.readonly) return;
  selectAllInput();
  popupOpen.value = true;
  popupRef.value?.show();
}

function onBlur(evt) {
  const next = evt?.relatedTarget;
  if (next?.closest?.(".q-menu")) return;
  const text = displayRef.value;
  if (text === "") {
    if (lastValid.value !== null) {
      lastValid.value = null;
      emit("update:modelValue", null);
    }
  } else {
    const parsed = parseDisplay(text);
    if (parsed) {
      const clamped = clampToRange(parsed);
      if (lastValid.value?.getTime() !== clamped.getTime()) {
        emitFromDate(clamped);
      }
    } else {
      displayRef.value = lastValid.value ? dateToDisplay(lastValid.value) : "";
    }
  }
  popupRef.value?.hide();
}

function onTyped(val) {
  displayRef.value = val ?? "";
  if (val == null || val === "") {
    if (lastValid.value !== null) {
      lastValid.value = null;
      emit("update:modelValue", null);
    }
    return;
  }
  if (val.length >= displayLength.value) {
    const parsed = parseDisplay(val);
    if (parsed) {
      const clamped = clampToRange(parsed);
      if (lastValid.value?.getTime() !== clamped.getTime()) {
        emitFromDate(clamped);
      }
    }
  }
}

function isArrowKey(key) {
  return (
    key === "ArrowLeft" ||
    key === "ArrowRight" ||
    key === "ArrowUp" ||
    key === "ArrowDown"
  );
}

function applyDateArrow(e) {
  const base = lastValid.value ? new Date(lastValid.value) : new Date();
  if (!isTimestamp.value) {
    base.setHours(0, 0, 0, 0);
  } else if (!lastValid.value) {
    applyDefaultTime(base);
  }
  base.setMilliseconds(0);
  let next;
  if (e.shiftKey) {
    const sign = e.key === "ArrowLeft" || e.key === "ArrowDown" ? -1 : +1;
    next = addMonths(base, sign);
  } else if (e.ctrlKey || e.metaKey) {
    const sign = e.key === "ArrowLeft" || e.key === "ArrowDown" ? -1 : +1;
    next = addYears(base, sign);
  } else {
    let delta = 0;
    if (e.key === "ArrowLeft") delta = -1;
    else if (e.key === "ArrowRight") delta = +1;
    else if (e.key === "ArrowUp") delta = -7;
    else if (e.key === "ArrowDown") delta = +7;
    next = new Date(base);
    next.setDate(base.getDate() + delta);
  }
  emitFromDate(clampToRange(next));
  selectAllInput();
}

function onKeydown(e) {
  if (props.readonly) return;
  if (!popupOpen.value) return;
  if (e.key === "Escape" || e.key === "Enter") {
    e.preventDefault();
    e.stopPropagation();
    popupRef.value?.hide();
    return;
  }
  if (e.key === "Tab") {
    if (isTimestamp.value && !e.shiftKey) {
      const el = hourRef.value?.nativeEl;
      if (el) {
        e.preventDefault();
        el.focus();
        el.select();
        return;
      }
    }
    popupRef.value?.hide();
    return;
  }
  if (isArrowKey(e.key)) {
    e.preventDefault();
    e.stopPropagation();
    e.qKeyEvent = true;
    applyDateArrow(e);
  }
}

function onPopupKeydown(e) {
  if (e.key === "Escape" || e.key === "Enter") {
    e.preventDefault();
    e.stopPropagation();
    popupRef.value?.hide();
    inputRef.value?.focus?.();
    return;
  }
  if (!isArrowKey(e.key)) return;
  const active = document.activeElement;
  if (active && (active.tagName === "INPUT" || active.tagName === "TEXTAREA"))
    return;
  e.preventDefault();
  e.stopPropagation();
  applyDateArrow(e);
}

function applyDefaultTime(dt) {
  if (props.defaultTime === "end") {
    dt.setHours(23, 59, 59, 0);
  } else if (props.defaultTime === "now") {
    const now = new Date();
    dt.setHours(now.getHours(), now.getMinutes(), now.getSeconds(), 0);
  } else {
    dt.setHours(0, 0, 0, 0);
  }
}

function onDatePick(val) {
  const dt = parseIso(val);
  if (!dt) return;
  if (isTimestamp.value) {
    applyDefaultTime(dt);
  }
  emitFromDate(clampToRange(dt));
}

function onPaste(e) {
  const text = (e.clipboardData?.getData("text") ?? "").trim();
  if (!text) return;
  const parsed = parsePasted(text);
  if (parsed) {
    e.preventDefault();
    emitFromDate(clampToRange(parsed));
    selectAllInput();
  }
}
</script>

<template>
  <q-input
    ref="inputRef"
    :model-value="displayRef"
    :mask="computedMask"
    :label="label"
    :outlined="outlined"
    :dense="dense"
    :stack-label="stackLabel"
    :readonly="readonly"
    :rules="rules"
    :input-class="['text-center', inputClass]"
    @focus="onFocus"
    @blur="onBlur"
    @keydown="onKeydown"
    @paste="onPaste"
    @update:model-value="onTyped"
  >
    <q-popup-proxy
      ref="popupRef"
      :no-parent-event="true"
      :no-focus="true"
      :no-refocus="false"
      transition-show="scale"
      transition-hide="scale"
      @show="popupOpen = true"
      @hide="popupOpen = false"
    >
      <div class="column bg-white" @keydown="onPopupKeydown">
        <q-date
          class="mg-input-data__date"
          :model-value="qDateValue"
          :mask="qDateMask"
          :options="dateOptions"
          minimal
          flat
          square
          @update:model-value="onDatePick"
        />
        <div
          v-if="isTimestamp"
          class="row justify-center items-center q-gutter-xs q-pt-none q-mt-none q-pb-md"
        >
          <q-input
            ref="hourRef"
            :model-value="hourValue"
            type="number"
            dense
            outlined
            min="0"
            max="23"
            input-class="text-center"
            style="width: 56px"
            :bottom-slots="false"
            hide-bottom-space
            @update:model-value="(v) => setTimePart('h', v)"
            @keydown="(e) => onTimeKeydown(e, 'h', true, false)"
            @focus="(e) => e.target?.select?.()"
          />
          <span class="text-h6">:</span>
          <q-input
            ref="minuteRef"
            :model-value="minuteValue"
            type="number"
            dense
            outlined
            min="0"
            max="59"
            input-class="text-center"
            style="width: 56px"
            :bottom-slots="false"
            hide-bottom-space
            @update:model-value="(v) => setTimePart('m', v)"
            @keydown="(e) => onTimeKeydown(e, 'm', false, !hasSeconds)"
            @focus="(e) => e.target?.select?.()"
          />
          <template v-if="hasSeconds">
            <span class="text-h6">:</span>
            <q-input
              ref="secondRef"
              :model-value="secondValue"
              type="number"
              dense
              outlined
              min="0"
              max="59"
              input-class="text-center"
              style="width: 56px"
              :bottom-slots="false"
              hide-bottom-space
              @update:model-value="(v) => setTimePart('s', v)"
              @keydown="(e) => onTimeKeydown(e, 's', false, true)"
              @focus="(e) => e.target?.select?.()"
            />
          </template>
        </div>
      </div>
    </q-popup-proxy>
  </q-input>
</template>

<style>
.mg-input-data__date .q-date__view {
  min-height: auto;
  padding-bottom: 0;
}
</style>
