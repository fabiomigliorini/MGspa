<template>
    <q-btn flat color="blue-grey" icon="apps">
        <q-tooltip transition-show="scale" transition-hide="scale">
            Menu
        </q-tooltip>
        <q-menu transition-show="rotate" transition-hide="rotate">
            <div class="row no-wrap">
                <div class="column">
                    <q-list float dense>
                        <q-item :to="{ name: 'inicio' }" active-class="q-item-no-link-highlighting">
                            <div class="q-pa-sm">
                                <q-icon name="start" size="25px" />
                                <q-item-label>Início</q-item-label>
                            </div>
                        </q-item>
                    </q-list>
                </div>

                <div class="column">
                    <q-list float dense>
                        <q-item :to="{ name: 'notafiscaldash' }" active-class="q-item-no-link-highlighting">

                            <div class="q-pa-sm">
                                <q-icon name="receipt" size="25px" />
                                <q-item-label>Notas</q-item-label>
                            </div>
                        </q-item>
                    </q-list>
                </div>
                <div class="column">
                    <q-list float dense>
                        <q-item :to="{ name: 'pessoa' }" active-class="q-item-no-link-highlighting">

                            <div class="q-pa-sm">
                                <q-icon name="people" size="25px" />
                                <q-item-label>Pessoas</q-item-label>
                            </div>
                        </q-item>
                    </q-list>
                </div>
            </div>
        </q-menu>
    </q-btn>
</template>