<template>
  <!-- root node required -->
  <mg-layout navigation drawer>

    <!-- your content -->
    <template slot="title">Marcas</template>

    <template slot="navigation">
      <q-tab icon="mail" route="/layout" exact replace>Mails</q-tab>
      <q-tab icon="mail" route="/layout" exact replace>Mails</q-tab>
      <q-tab icon="mail" route="/layout" exact replace>Mails</q-tab>
      <q-tab icon="mail" route="/layout" exact replace>Mails</q-tab>
      <q-tab icon="mail" route="/layout" exact replace>Mails</q-tab>
      <q-tab icon="alarm" route="/layout/alarm" exact replace>Alarms</q-tab>
      <q-tab icon="help" route="/layout/help" exact replace>Help</q-tab>
    </template>

    <template slot="drawer">
      <div class="toolbar">
        <q-toolbar-title>
          Marcas
        </q-toolbar-title>
      </div>

      <div class="list no-border platform-delimiter">
        <q-drawer-link icon="mail" :to="{path: '/', exact: true}">
          Link daqui
        </q-drawer-link>
      </div>
    </template>

    <div slot="content" class="layout-padding">
      teste
      <!-- if you want automatic padding -->
    </div>


  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'

export default {
  data () {
    return {}
  },
  components: {
    MgLayout
  }
}
</script>

<style>
</style>
