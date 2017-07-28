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
@media (max-width: 1366px) and (min-width: 992px) {
  .layout-padding {
    padding: 1.5rem 2rem;
    margin: auto;
  }
  .layout-padding.horizontal {
    padding: 0 3rem;
  }
}

</style>
