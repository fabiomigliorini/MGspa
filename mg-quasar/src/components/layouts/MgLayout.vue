<template>
<q-layout>

  <div slot="header" class="toolbar">
    <button class="hide-on-drawer-visible" @click="$refs.drawer.toggle()">
        <i>menu</i>
      </button>
    <q-toolbar-title :padding="1">
      <slot name="title"></slot>
    </q-toolbar-title>

    <button @click="$refs.rightDrawer.open()">
        <i>apps</i>
      </button>
  </div>

  <q-tabs slot="navigation" v-if="navigation">
    <slot name="navigation"></slot>
  </q-tabs>

  <q-drawer ref="drawer" v-if="drawer">
    <slot name="drawer"></slot>
  </q-drawer>

  <!--
  <router-view class="layout-view"></router-view>
  -->

  <div class="layout-view">
    <slot name="content"></slot>
  </div>

  <q-drawer ref="rightDrawer" swipe-only right-side>

    <div class="container">
    <div class="list-header">
      Right Side Drawer
    </div>
    </div>

    <div class="row wrap">
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>
      <div class="auto text-center width-1of4" style="border: 1px solid blue">
        <router-link :to="{ path: '/marca', params: {} }">
          <i style="font-size: 48px;">account_circle</i>
        </router-link>
      </div>

    </div>
    <div class="list platform-delimiter item-inset-delimiter">
      <div class="list-header">
        Right Side Drawer
      </div>
      <q-drawer-link icon="mail" to="/shopping-cart">Shopping Cart</q-drawer-link>
      <q-drawer-link icon="mail" to="/weather">Weather</q-drawer-link>
    </div>
  </q-drawer>

  <div slot="footer" class="toolbar">
    <slot name="footer">
      &copy; MG Papelaria
    </slot>
  </div>

</q-layout>
</template>

<script>
export default {
  data () {
    return {}
  },
  props: {
    navigation: {
      type: Boolean,
      default: false
    },
    drawer: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style>
</style>
