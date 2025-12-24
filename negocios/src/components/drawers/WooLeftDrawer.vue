<script setup>
import { ref } from "vue";
import { wooStore } from "stores/woo";
import { Notify } from "quasar";

const sWoo = wooStore();

// torna reativo e inicializa com as opções do store
const statusOptions = ref([...sWoo.opcoes.status]);

// Função de filtro para o QSelect
// - quando o valor estiver vazio, restaura lista completa
// - usa includes() para legibilidade
const filterFn = (val, update, abort) => {
  // se input estiver vazio, restaura opções completas
  if (!val) {
    update(() => {
      statusOptions.value = [...sWoo.opcoes.status];
    });
    return;
  }
  update(() => {
    const needle = String(val).toLowerCase();
    statusOptions.value = sWoo.opcoes.status.filter((v) =>
      v.label.toLowerCase().includes(needle)
    );
  });
};

const buscarNovos = async () => {
  const ret = await sWoo.buscarNovos();
  Notify.create({
    type: "positive",
    message: ret + " Pedido(s) encontrados!",
    timeout: 3000, // 3 segundos
    actions: [{ icon: "close", color: "white" }],
  });
};

const buscarPorAlteracao = async () => {
  const ret = await sWoo.buscarPorAlteracao();
  Notify.create({
    type: "positive",
    message: ret + " Pedido(s) encontrados!",
    timeout: 3000, // 3 segundos
    actions: [{ icon: "close", color: "white" }],
  });
};
</script>

<template>
  <q-list>
    <!-- TITULO -->
    <q-item-label header>
      Filtro

      <q-btn
        flat
        dense
        size="small"
        color="primary"
        icon="mdi-cart-plus"
        class="q-ml-md"
        @click="buscarPorAlteracao(pedido)"
      >
        <q-tooltip>
          Busca novos pedidos no Woo pela data de alteração.
        </q-tooltip>
      </q-btn>

      <q-btn
        flat
        dense
        size="small"
        color="primary"
        icon="mdi-cart-arrow-down"
        @click="buscarNovos(pedido)"
      >
        <q-tooltip>
          Busca pedidos no Woo pelo status de Pendente/Processando/Aguardando.
        </q-tooltip>
      </q-btn>

      <q-btn
        flat
        dense
        size="small"
        color="primary"
        icon="mdi-view-column-outline"
        to="/woo/painel"
      >
        <q-tooltip> Painel de controle. </q-tooltip>
      </q-btn>
    </q-item-label>

    <!-- ID -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sWoo.filtro.id"
          input-class="text-right"
          label="ID Pedido Woo"
          type="number"
          step="1"
          min="1"
        >
        </q-input>
      </q-item-section>
    </q-item>

    <!-- STATUS -->
    <q-item>
      <q-item-section>
        <q-select
          outlined
          v-model="sWoo.filtro.status"
          use-input
          fill-input
          label="Status"
          input-debounce="0"
          :options="statusOptions"
          @filter="filterFn"
          multiple
          use-chips
          emit-value
          map-options
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey"> Sem opções </q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-item-section>
    </q-item>

    <!-- NOME -->
    <q-item>
      <q-item-section>
        <q-input
          outlined
          v-model="sWoo.filtro.nome"
          input-class="text-left"
          label="Cliente"
          type="text"
        >
        </q-input>
      </q-item-section>
    </q-item>

    <!-- CRIACAOWOO -->
    <q-item>
      <q-item-section>
        <div class="row q-col-gutter-sm">
          <q-input
            outlined
            type="date"
            input-class="text-right"
            v-model="sWoo.filtro.criacaowoo_de"
            :max="sWoo.filtro.valor_ate"
            label="De"
            class="col-6"
          />
          <q-input
            outlined
            type="date"
            input-class="text-right"
            v-model="sWoo.filtro.criacaowoo_ate"
            label="Até"
            class="col-6"
          />
        </div>
      </q-item-section>
    </q-item>

    <!-- VALORTOTAL -->
    <q-item>
      <q-item-section>
        <div class="row q-col-gutter-sm">
          <q-input
            outlined
            type="number"
            step="0.01"
            min="0.01"
            input-class="text-right"
            v-model="sWoo.filtro.valortotal_de"
            :max="sWoo.filtro.valortotal_ate"
            label="Valor de"
            class="col-6"
            prefix="R$"
          />
          <q-input
            outlined
            type="number"
            step="0.01"
            :min="
              sWoo.filtro.valortotal_de > 0 ? sWoo.filtro.valortotal_de : 0.01
            "
            input-class="text-right"
            v-model="sWoo.filtro.valortotal_ate"
            label="até"
            class="col-6"
            prefix="R$"
          />
        </div>
      </q-item-section>
    </q-item>
  </q-list>
</template>
