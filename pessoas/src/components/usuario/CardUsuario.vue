<script setup>
const props = defineProps({
  usuario: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <router-link
    :to="'/usuarios/' + usuario.codusuario"
    class="full-height"
    style="text-decoration: none; color: inherit"
  >
    <q-card bordered flat class="cursor-pointer full-height q-pb-md">
      <!-- BADGE INATIVO -->
      <q-badge
        v-if="usuario.inativo"
        color="red"
        floating
        label="Inativo"
      />

      <!-- HEADER -->
      <q-card-section class="text-grey-9 text-overline q-pb-none">
        <div class="flex items-center" style="height: 3rem">
          <div class="text-body1 ellipsis">
            {{ usuario.usuario }}
          </div>
        </div>
        <div
          v-if="usuario.Pessoa"
          class="text-caption text-grey ellipsis"
          style="text-transform: none"
        >
          {{ usuario.Pessoa.pessoa }}
        </div>
      </q-card-section>

      <q-separator inset class="q-mt-sm" />

      <!-- DETALHES -->
      <q-list v-if="usuario.permissoes?.length">
        <q-item
          v-for="permissao in usuario.permissoes"
          :key="permissao.codgrupousuario"
          dense
        >
          <q-item-section avatar>
            <q-icon color="primary" name="security" size="xs" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis text-caption">
              {{ permissao.grupousuario }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card>
  </router-link>
</template>
