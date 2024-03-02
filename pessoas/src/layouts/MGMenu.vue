<template>
    <q-btn flat color="blue-grey" icon="apps">
        <q-tooltip transition-show="rotate" transition-hide="rotate">
            Menu
        </q-tooltip>
        <q-menu transition-show="rotate" transition-hide="rotate">
            <div class="row q-pt-md">
                <div class="col-6">
                    <q-list float dense>
                        <q-item :to="{ name: 'inicio' }" active-class="q-item-no-link-highlighting">
                            <q-icon name="start" size="25px" class="q-pr-sm" />
                            <q-item-label>Início</q-item-label>
                        </q-item>

                        <q-item :to="{ name: 'notafiscaldash' }">
                            <q-icon name="receipt" size="25px" class="q-pr-sm" />
                            <q-item-label>Notas</q-item-label>
                        </q-item>
                    </q-list>
                    <q-list float dense>
                        <q-item :to="{ name: 'grupoeconomicoindex' }">
                            <q-icon name="groups" size="25px" class="q-pr-sm" />
                            <q-item-label>Grupo Econômico</q-item-label>
                        </q-item>
                    </q-list>
                </div>

                <div class="col-6">
                    <q-list float dense>
                        <q-item :to="{ name: 'pessoa' }">
                            <q-icon name="people" size="25px" class="q-pr-sm" />
                            <q-item-label>Pessoas</q-item-label>
                        </q-item>

                        <q-item v-if="user.verificaPermissaoUsuario('Administrador')" :to="{ name: 'usuarios' }">
                            <q-icon name="admin_panel_settings" size="25px" class="q-pr-sm" />
                            <q-item-label>Usuários</q-item-label>
                        </q-item>
                    </q-list>

                    <q-list float dense v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
                        <q-item :to="'/ferias/' + moment().year()">
                            <q-icon name="holiday_village" size="25px" class="q-pr-sm" />
                            <q-item-label>Férias</q-item-label>
                        </q-item>
                    </q-list>
                </div>

                <div class="col-6" v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
                    <q-list float dense>
                        <q-item :to="{ name: 'cargosindex' }">
                            <q-icon name="work" size="25px" class="q-pr-sm" />
                            <q-item-label>Cargos</q-item-label>
                        </q-item>
                    </q-list>
                </div>

                <div class="col-6">
                    <q-list float dense>
                        <q-item :to="{ name: 'aniversariosindex' }"> 
                            <q-icon name="celebration" size="25px" class="q-pr-sm" />
                            <q-item-label>Aniversários</q-item-label>
                        </q-item>
                    </q-list>
                </div>

            </div>
        </q-menu>
    </q-btn>
</template>

<script>
import { guardaToken } from 'src/stores'
import { defineComponent } from 'vue'
import moment from 'moment';
import 'moment/min/locales';
moment.locale("pt-br")

export default defineComponent({
    name: "MGMenu",

    setup() {
        const user = guardaToken()

        return {
            user,
            moment,
        }
    },
})
</script>