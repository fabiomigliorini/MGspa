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
</style>
