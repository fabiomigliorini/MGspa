<script setup>
import { useLoading } from "src/composables/useLoading";
import { ref } from "vue";

const props = defineProps({
  submitCallback: {
    type: Function,
    default: () => {},
  },
  errorMessage: {
    type: String,
    default: "",
  },
});

const form = ref(null);
const error = ref("");


const submit = async (event) => {

  event.preventDefault();

  if (!form.value) {
    return;
  }

  error.value = "";

  const formData = new FormData(event.target);

  const validated = await form.value?.validate();

  if (!validated) {
    return;
  }

  try {
    await props.submitCallback(formData);
  } catch (err) {
      error.value = err.message ?? props.errorMessage;
  }
};

const { loading, execute: executeSubmit } = useLoading(submit);
</script>

<template>
  <q-form class="q-pa-md" ref="form" @submit="executeSubmit">
    <slot name="fields" v-bind="{ loading }"></slot>

    <slot name="error" v-bind="{ error }">
      <div v-if="error" class="text-center text-red font-weight-bold q-mb-sm">
        {{ error }}
      </div>
    </slot>

    <div class="d-flex justify-between">
      <slot name="actions" v-bind="{ loading }"> </slot>
    </div>
  </q-form>
</template>

<style>
.d-flex {
  display: flex !important;
}
</style>
