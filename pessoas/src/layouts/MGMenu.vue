<template>
    <q-btn flat color="blue-grey" icon="apps">

        <q-menu transition-show="fade" transition-hide="fade" class="bg-blue-grey text-yellow-6 ">

            <div class="row q-col-gutter-md q-py-sm q-px-lg">
                <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'pessoa' }">
                    <q-icon name="person" size="35px" />
                    <div>Pessoas</div>
                </q-btn>
                <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'grupoeconomicoindex' }">
                    <q-icon name="apartment" size="35px" />
                    <div>Grupo Econômico</div>
                </q-btn>
                <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'aniversariosindex' }">
                    <q-icon name="celebration" size="35px" />
                    <div>Aniversários</div>
                </q-btn>
                <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'notafiscaldash' }">
                    <q-icon name="receipt" size="35px" />
                    <div>Notas</div>
                </q-btn>
            </div>

            <template v-if="user.verificaPermissaoUsuario('Administrador')">
                <!-- <hr class="q-mx-md"> -->
                <q-separator inset color="yelllow-6" />

                <div class="row q-col-gutter-md q-py-sm q-px-lg">
                    <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'usuarios' }">
                        <q-icon name="admin_panel_settings" size="35px" />
                        <div>Usuários</div>
                    </q-btn>
                    <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'grupousuarios' }">
                        <q-icon name="groups" size="35px" />
                        <div>Grupo Usuários</div>
                    </q-btn>
                </div>
            </template>

            <template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
                <!-- <hr class="q-mx-md"> -->
                <q-separator inset color="yelllow-6" />

                <div class="row q-col-gutter-md q-py-sm q-px-lg">
                    <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="'/ferias/' + moment().year()">
                        <q-icon name="hotel" size="35px" />
                        <div>Férias</div>
                    </q-btn>
                    <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'cargosindex' }">
                        <q-icon name="work" size="35px" />
                        <div>Cargos</div>
                    </q-btn>
                </div>
            </template>

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