<script setup>

import { authStore } from 'stores/auth'

const auth = authStore();

</script>
<template>
    <q-btn flat  dense round color="blue-grey" icon="apps" >

        <q-menu transition-show="fade" transition-hide="fade" class="bg-blue-grey text-yellow-6 q-pa-xs">

            <q-list>

                <q-item class="q-px-sm">
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
                </q-item>

                <q-separator inset color="yelllow-6" />
                <q-item class="q-px-sm">
                    <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" to="/metas">
                        <q-icon name="trending_up" size="35px" />
                        <div>Metas</div>
                    </q-btn>
                </q-item>

                <template v-if="auth.verificaPermissaoUsuario('Administrador')">
                    <q-separator inset color="yelllow-6" />
                    <q-item class="q-px-sm">
                        <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'usuarios' }">
                            <q-icon name="admin_panel_settings" size="35px" />
                            <div>Usuários</div>
                        </q-btn>
                        <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'grupousuarios' }">
                            <q-icon name="groups" size="35px" />
                            <div>Grupo Usuários</div>
                        </q-btn>
                    </q-item>
                </template>

                <template v-if="auth.verificaPermissaoUsuario('Recursos Humanos')">
                    <q-separator inset color="yelllow-6" />
                    <q-item class="q-px-sm">
                        <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="'/ferias/' + $moment().year()">
                            <q-icon name="hotel" size="35px" />
                            <div>Férias</div>
                        </q-btn>
                        <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="{ name: 'cargosindex' }">
                            <q-icon name="work" size="35px" />
                            <div>Cargos</div>
                        </q-btn>
                        <q-btn flat stack class="col-xs-6 col-sm-3 col-md-3" :to="'/comissao-caixas/'">
                            <q-icon name="point_of_sale" size="35px" />
                            <div>Comissão Caixas</div>
                        </q-btn>
                    </q-item>
                </template>
            </q-list>

        </q-menu>
    </q-btn>
</template>
