<script setup>
const props = defineProps({
  grupoUsuario: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <router-link
    :to="'/grupo-usuarios/' + grupoUsuario.codgrupousuario"
    class="full-height"
    style="text-decoration: none; color: inherit"
  >
    <q-card bordered flat class="cursor-pointer full-height q-pb-md">
      <!-- BADGE INATIVO -->
      <q-badge
        v-if="grupoUsuario.inativo"
        color="red"
        floating
        label="Inativo"
      />

      <!-- HEADER -->
      <q-card-section class="text-grey-9 text-overline q-pb-none">
        <div class="flex items-center" style="height: 3rem">
          <div class="text-body1 ellipsis">
            {{ grupoUsuario.grupousuario }}
          </div>
        </div>
      </q-card-section>

      <template v-if="grupoUsuario.observacoes">
        <q-separator inset class="q-mt-sm" />
        <q-list>
          <q-item dense>
            <q-item-section avatar>
              <q-icon color="grey" name="info" size="xs" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-caption ellipsis">
                {{ grupoUsuario.observacoes }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </template>
    </q-card>
  </router-link>
</template>
