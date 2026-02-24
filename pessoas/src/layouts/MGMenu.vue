<script setup>
import { guardaToken } from "src/stores";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const user = guardaToken();
</script>

<template>
  <q-btn flat dense round color="text-grey-9" icon="apps">
    <q-menu
      transition-show="fade"
      transition-hide="fade"
      class="bg-grey-9 text-yellow-6 q-pa-xs"
    >
      <div class="menu-grid">
        <div class="row">
          <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'pessoa' }">
            <q-icon name="person" size="35px" />
            <div class="menu-label">Pessoas</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-4 menu-btn"
            :to="{ name: 'grupoeconomicoindex' }"
          >
            <q-icon name="apartment" size="35px" />
            <div class="menu-label">Grupo Econômico</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-4 menu-btn"
            :to="{ name: 'aniversariosindex' }"
          >
            <q-icon name="celebration" size="35px" />
            <div class="menu-label">Aniversários</div>
          </q-btn>
        </div>
        <div class="row">
          <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'metaIndex' }">
            <q-icon name="trending_up" size="35px" />
            <div class="menu-label">Metas</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-4 menu-btn"
            :to="{ name: 'unidadeNegocioIndex' }"
          >
            <q-icon name="store" size="35px" />
            <div class="menu-label">Unidades & Setores</div>
          </q-btn>
        </div>

        <template v-if="user.verificaPermissaoUsuario('Administrador')">
          <div class="row">
            <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'usuarios' }">
              <q-icon name="admin_panel_settings" size="35px" />
              <div class="menu-label">Usuários</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-4 menu-btn"
              :to="{ name: 'grupousuarios' }"
            >
              <q-icon name="groups" size="35px" />
              <div class="menu-label">Grupo Usuários</div>
            </q-btn>
            <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'empresa' }">
              <q-icon name="business" size="35px" />
              <div class="menu-label">Empresas</div>
            </q-btn>
          </div>
        </template>

        <template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
          <div class="row">
            <q-btn
              flat
              stack
              class="col-4 menu-btn"
              :to="'/ferias/' + moment().year()"
            >
              <q-icon name="hotel" size="35px" />
              <div class="menu-label">Férias</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-4 menu-btn"
              :to="{ name: 'cargosindex' }"
            >
              <q-icon name="work" size="35px" />
              <div class="menu-label">Cargos</div>
            </q-btn>
            <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'rhIndex' }">
              <q-icon name="paid" size="35px" />
              <div class="menu-label">Metas RH</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-4 menu-btn"
              :to="{ name: 'feriadoIndex' }"
            >
              <q-icon name="event_busy" size="35px" />
              <div class="menu-label">Feriados</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-4 menu-btn"
              :to="{ name: 'certidaoemissor' }"
            >
              <q-icon name="description" size="35px" />
              <div class="menu-label">Emissores de Certidões</div>
            </q-btn>
          </div>
        </template>

        <div class="text-caption text-yellow-6 q-pa-sm">Cadastros</div>
        <div class="row">
          <q-btn flat stack class="col-4 menu-btn" :to="{ name: 'etnia' }">
            <q-icon name="diversity_1" size="35px" />
            <div class="menu-label">Etnia</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-4 menu-btn"
            :to="{ name: 'grauinstrucao' }"
          >
            <q-icon name="school" size="35px" />
            <div class="menu-label">Grau Instrução</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-4 menu-btn"
            :to="{ name: 'estadocivil' }"
          >
            <q-icon name="favorite" size="35px" />
            <div class="menu-label">Estado Civil</div>
          </q-btn>
        </div>
      </div>
    </q-menu>
  </q-btn>
</template>

<style scoped>
.menu-grid {
  min-width: 280px;
}

.menu-btn {
  min-height: 70px;
}

.menu-label {
  font-size: 0.65rem;
  line-height: 1.2;
  text-align: center;
}
</style>
