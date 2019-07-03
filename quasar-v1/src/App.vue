<template>
  <div id="q-app">
    <router-view />
  </div>
</template>

<script>
  export default {
    name: 'App'
  }
</script>

<style>
  .q-if {
    padding-bottom: 2px;
  }
  .q-if,
  .q-field,
  .q-uploader {
    margin: 14px 0;
  }

  .q-field .q-if,
  .q-uploader .q-if {
    margin: 0;
  }

  .q-field .q-uploader {
    margin: 0;
  }
  .q-search {
    padding-top: 12px;
  }

  dt {
    color: rgba(0,0,0,0.54);
  }

  dl {
    margin: 0;
  }

  dd {
    margin-bottom: 16px;
  }

  dd:last-child {
    margin-bottom: 0;
  }

  .q-rating i.q-icon:not(.active) {
    color: grey;
    opacity: 0.1;
  }
</style>
