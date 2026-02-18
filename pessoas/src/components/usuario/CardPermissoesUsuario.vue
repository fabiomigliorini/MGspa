<script setup>
import { usuarioStore } from "src/stores/usuario";

const sUsuario = usuarioStore();
</script>

<template>
  <q-card bordered flat v-if="sUsuario.detalheUsuarios?.permissoes">
    <q-card-section class="text-grey-9 text-overline">
      PERMISSÕES
    </q-card-section>

    <q-separator inset />

    <q-list v-if="sUsuario.detalheUsuarios.permissoes.length">
      <template
        v-for="(grupo, index) in sUsuario.detalheUsuarios.permissoes"
        :key="grupo.codgrupousuario"
      >
        <q-separator inset v-if="index > 0" />
        <q-item :to="'/grupo-usuarios/' + grupo.codgrupousuario">
          <q-item-section avatar>
            <q-icon color="primary" name="security" size="xs" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis text-caption">
              {{ grupo.grupousuario }}
            </q-item-label>
            <q-item-label caption v-if="grupo.observacoes" class="ellipsis">
              {{ grupo.observacoes }}
            </q-item-label>
            <q-item-label caption v-if="grupo.filiais?.length">
              <template
                v-for="(filial, i) in grupo.filiais"
                :key="filial.codfilial"
              >
                <span v-if="i !== 0"> | </span>
                {{ filial.filial }}
              </template>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>

    <div v-else class="q-pa-md text-center text-grey">
      Nenhuma permissão atribuída
    </div>
  </q-card>
</template>

