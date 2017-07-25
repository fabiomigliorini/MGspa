<template>
  <!-- Don't drop "q-app" class -->
  <div id="q-app">
    <router-view></router-view>
  </div>
</template>

<script>
/*
 * Root component
 */
export default {}
</script>

<style>

.q-rating i.q-icon:not(.active) {
  color: grey;
  opacity: 0.1;
}

</style>
