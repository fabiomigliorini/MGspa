<script setup>
import { guardaToken } from "src/stores";
import { defineComponent } from "vue";
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
      <q-list>
        <q-item class="q-px-sm">
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'pessoa' }"
          >
            <q-icon name="person" size="35px" />
            <div>Pessoas</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'grupoeconomicoindex' }"
          >
            <q-icon name="apartment" size="35px" />
            <div>Grupo Econômico</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'aniversariosindex' }"
          >
            <q-icon name="celebration" size="35px" />
            <div>Aniversários</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'notafiscaldash' }"
          >
            <q-icon name="receipt" size="35px" />
            <div>Notas</div>
          </q-btn>
        </q-item>

        <q-separator inset color="yelllow-6" />
        <q-item class="q-px-sm">
          <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" to="/metas">
            <q-icon name="trending_up" size="35px" />
            <div>Metas</div>
          </q-btn>
        </q-item>

        <template v-if="user.verificaPermissaoUsuario('Administrador')">
          <q-separator inset color="yelllow-6" />
          <q-item class="q-px-sm">
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="{ name: 'usuarios' }"
            >
              <q-icon name="admin_panel_settings" size="35px" />
              <div>Usuários</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="{ name: 'grupousuarios' }"
            >
              <q-icon name="groups" size="35px" />
              <div>Grupo Usuários</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="{ name: 'empresa' }"
            >
              <q-icon name="work" size="35px" />
              <div>Empresas</div>
            </q-btn>
          </q-item>
        </template>

        <template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
          <q-separator inset color="yelllow-6" />
          <q-item class="q-px-sm">
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="'/ferias/' + moment().year()"
            >
              <q-icon name="hotel" size="35px" />
              <div>Férias</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="{ name: 'cargosindex' }"
            >
              <q-icon name="work" size="35px" />
              <div>Cargos</div>
            </q-btn>
            <q-btn
              flat
              stack
              class="col-xs-6 col-sm-3 col-md-3"
              :to="'/comissao-caixas/'"
            >
              <q-icon name="point_of_sale" size="35px" />
              <div>Comissão Caixas</div>
            </q-btn>
          </q-item>
        </template>

        <q-separator inset color="yelllow-6" />
        <q-item-label header class="text-yellow-6">Cadastros</q-item-label>
        <q-item class="q-px-sm">
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'etnia' }"
          >
            <q-icon name="diversity_1" size="35px" />
            <div>Etnia</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'grauinstrucao' }"
          >
            <q-icon name="school" size="35px" />
            <div>Grau Instrução</div>
          </q-btn>
          <q-btn
            flat
            stack
            class="col-xs-6 col-sm-3 col-md-3"
            :to="{ name: 'estadocivil' }"
          >
            <q-icon name="favorite" size="35px" />
            <div>Estado Civil</div>
          </q-btn>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>
</template>
