<template>
  <q-layout view="hHr LpR fFr">
    <q-header elevated reveal>
      <q-ajax-bar size="3px" />
      <q-toolbar color="primary">
        <slot name="menu">
          <q-btn
            flat
            round
            dense
            icon="menu"
            @click="leftDrawer = !leftDrawer"
            v-if="drawer"
          />
        </slot>

        <q-toolbar-title>
          <slot name="title">Padrão</slot>
        </q-toolbar-title>

        <q-btn
          flat
          round
          class="within-iframe-hide"
          v-if="backPath"
          :to="backPath"
          replace
          style="margin-right: 15px"
        >
          <q-icon name="arrow_back" />
        </q-btn>

        <slot name="menuRight">
          <q-btn
            flat
            round
            dense
            icon="apps"
            @click="rightDrawer = !rightDrawer"
          />
        </slot>
      </q-toolbar>

      <slot name="tabHeader"></slot>
    </q-header>

    <!-- Left Side Panel -->
    <q-drawer
      elevated
      v-if="drawer"
      side="left"
      v-model="leftDrawer"
      no-swipe-backdrop
    >
      <slot name="drawer"></slot>
    </q-drawer>

    <!-- Right Side Panel -->
    <q-drawer v-model="rightDrawer" side="right" behavior="mobile" bordered>
      <q-item>
        <q-item-section avatar link to="/inbox/1">
          <q-icon name="person" color="primary" />
        </q-item-section>

        <q-item-section class="text-subtitle1 text-primary">
          {{ perfil.usuario }}
        </q-item-section>

        <q-item-section
          avatar
          @click.native="logout"
          class="cursor-pointer text-primary"
        >
          <q-btn icon="exit_to_app" flat />
          <q-tooltip>
            Sair do sistema
          </q-tooltip>
        </q-item-section>
      </q-item>

      <q-separator />

      <div class="row">
        <div class="text-center col-4" v-for="aplicativo in aplicativos">
          <q-item :to="aplicativo.path" clickable>
            <q-item-section>
              <q-item-label>
                <q-icon size="50px" :name="aplicativo.icon" color="primary" />
              </q-item-label>
              <q-item-label caption class="text-primary">
                {{ aplicativo.title }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </div>
    </q-drawer>

    <q-page-container>
      <slot name="content">
        <router-view />
      </slot>
    </q-page-container>

    <q-footer elevated reveal class="bg-grey-8 text-white">
      <div class="q-ma-xs text-weight-light text-center">
        MGspa - &copy; MG Papelaria
      </div>
    </q-footer>
  </q-layout>
</template>

<script>
import auth from "../services/auth";
export default {
  name: "mg-layout",
  data() {
    return {
      // leftDrawer: false,
      // rightDrawer: false,
    };
  },
  components: {},
  computed: {
    aplicativos: {
      get() {
        return this.$store.state.aplicativos.aplicativos;
      }
    },
    perfil: {
      get() {
        return this.$store.state.perfil.perfilState;
      }
    }
  },
  props: {
    drawer: {
      type: Boolean,
      default: false
    },
    backPath: {
      type: String,
      default: null
    },
    leftDrawer: {
      type: Boolean,
      default: false
    },
    rightDrawer: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    logout() {
      const vm = this;
      this.$q
        .dialog({
          cancel: "Cancelar",
          persistent: true,
          title: "Sair do sistema",
          message: "Tem certeza que deseja sair do sistema?"
        })
        .onOk(async () => {
          // RFC 7009 — revoga o token no api-dev (mesma rota dos apps contas/notas/pessoas)
          // e limpa estado local. auth.logout() já redireciona pra '/'.
          vm.$q.notify({
            message: "Até mais...",
            color: "positive"
          });
          await auth.logout();
        })
        .onCancel(() => {});
    }
  }
};
</script>

<style></style>
