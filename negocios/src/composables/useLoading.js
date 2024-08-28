import { ref } from "vue";

function useLoading(callback) {
  const loading = ref(false);
  const error = ref(null);

  const execute = async (...args) => {
    loading.value = true;

    try {
      const result = await callback(...args);

      loading.value = false;

      return result;
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    execute: execute,
  };
}

export { useLoading };
