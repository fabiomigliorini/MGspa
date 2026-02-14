<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import {
  formataDataInput,
  dataFormatoSql,
  localeBrasil,
  formataDataSemHora,
} from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const dialogNovoRegistroSpc = ref(false);
const modelRegistroSpc = ref({});
const editarRegistro = ref(false);
const filtroRegistroSpc = ref("abertos");

const registrosFiltrados = computed(() => {
  const lista = sPessoa.item?.RegistroSpc || [];
  if (filtroRegistroSpc.value === "abertos")
    return lista.filter((x) => !x.baixa);
  return lista;
});

const novoRegistroSpc = async () => {
  modelRegistroSpc.value.codpessoa = route.params.id;

  const novoRegistro = { ...modelRegistroSpc.value };

  if (novoRegistro.inclusao) {
    novoRegistro.inclusao = dataFormatoSql(novoRegistro.inclusao);
  }
  if (novoRegistro.baixa) {
    novoRegistro.baixa = dataFormatoSql(novoRegistro.baixa);
  }

  if (novoRegistro.valor.indexOf(",") > -1) {
    var removeVirgula = novoRegistro.valor.replace(/,([^,]*)$/, ".$1");
    novoRegistro.valor = removeVirgula;
  }

  try {
    const ret = await sPessoa.novoRegistroSpc(route.params.id, novoRegistro);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro Spc criado!",
      });
      dialogNovoRegistroSpc.value = false;
      sPessoa.item.RegistroSpc.push(ret.data.data);
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message: error.response.data.message,
    });
  }
};

const editarRegistroSpc = (
  codregistrospc,
  valor,
  inclusao,
  baixa,
  observacoes
) => {
  dialogNovoRegistroSpc.value = true;
  editarRegistro.value = true;
  modelRegistroSpc.value = {
    codregistrospc: codregistrospc,
    valor: valor,
    inclusao: inclusao ? formataDataInput(inclusao) : null,
    baixa: baixa ? formataDataInput(baixa) : null,
    observacoes: observacoes,
  };
};

const salvarRegistro = async () => {
  const editRegistro = { ...modelRegistroSpc.value };

  if (editRegistro.inclusao) {
    editRegistro.inclusao = dataFormatoSql(editRegistro.inclusao);
  }
  if (editRegistro.baixa) {
    editRegistro.baixa = dataFormatoSql(editRegistro.baixa);
  }

  if (editRegistro.valor.toString().indexOf(",") > -1) {
    var removeVirgula = editRegistro.valor.replace(/,([^,]*)$/, ".$1");
    editRegistro.valor = removeVirgula;
  }

  try {
    const ret = await sPessoa.salvarEdicaoRegistro(
      route.params.id,
      editRegistro.codregistrospc,
      editRegistro
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro Spc alterado!",
      });
      const i = sPessoa.item.RegistroSpc.findIndex(
        (item) => item.codregistrospc === modelRegistroSpc.value.codregistrospc
      );
      sPessoa.item.RegistroSpc[i] = ret.data.data;
      dialogNovoRegistroSpc.value = false;
      editarRegistro.value = false;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
};

const excluirRegistro = async (codregistrospc) => {
  $q.dialog({
    title: "Excluir Registro Spc",
    message: "Tem certeza que deseja excluir esse registro?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sPessoa.excluirRegistroSpc(route.params.id, codregistrospc);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro excluido",
      });
      sPessoa.get(route.params.id);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  });
};

const submit = () => {
  editarRegistro.value === false ? novoRegistroSpc() : salvarRegistro();
};

</script>

<template>
  <!-- Dialog novo Registro Spc -->
  <q-dialog v-model="dialogNovoRegistroSpc">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="editarRegistro === false">NOVO REGISTRO SPC</template>
        <template v-else>EDITAR REGISTRO SPC</template>
      </q-card-section>

      <q-form @submit="submit()">
        <q-separator inset />

        <q-card-section>
          <div class="col-6">
            <q-input
              outlined
              v-model="modelRegistroSpc.inclusao"
              mask="##/##/####"
              label="Inclusão"
              :rules="[
                (val) => (val && val.length > 0) || 'Inclusão obrigatório',
              ]"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-date
                      v-model="modelRegistroSpc.inclusao"
                      :locale="localeBrasil"
                      mask="DD/MM/YYYY"
                    >
                      <div class="row items-center justify-end">
                        <q-btn
                          v-close-popup
                          label="Fechar"
                          color="primary"
                          flat
                        />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input
              outlined
              v-model="modelRegistroSpc.baixa"
              mask="##/##/####"
              class="q-mb-md"
              label="Baixa"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-date
                      v-model="modelRegistroSpc.baixa"
                      :locale="localeBrasil"
                      mask="DD/MM/YYYY"
                    >
                      <div class="row items-center justify-end">
                        <q-btn
                          v-close-popup
                          label="Fechar"
                          color="primary"
                          flat
                        />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input
              outlined
              v-model="modelRegistroSpc.valor"
              label="Valor"
              type="numeric"
              :rules="[
                (val) =>
                  (val !== null && val !== '' && val !== undefined) ||
                  'Valor obrigatório',
              ]"
            />

            <q-input
              outlined
              v-model="modelRegistroSpc.observacoes"
              label="Observações"
              borderless
              autogrow
              type="textarea"
            />
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            color="grey-8"
            v-close-popup
            tabindex="-1"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      SPC
      <q-space />
      <q-btn-toggle
        v-model="filtroRegistroSpc"
        color="grey-3"
        toggle-color="primary"
        text-color="grey-7"
        toggle-text-color="grey-3"
        unelevated
        dense
        no-caps
        size="sm"
        :options="[
          { label: 'Abertos', value: 'abertos' },
          { label: 'Todos', value: 'todos' },
        ]"
      />
      <q-btn
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        v-if="user.verificaPermissaoUsuario('Publico')"
        @click="
          (dialogNovoRegistroSpc = true),
            (editarRegistro = false),
            (modelRegistroSpc = {})
        "
      />
    </q-card-section>

    <q-list v-if="registrosFiltrados.length > 0">
      <template
        v-for="registro in registrosFiltrados"
        v-bind:key="registro.codregistrospc"
      >
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-btn round flat icon="gavel" color="primary" />
          </q-item-section>

          <q-item-section>
            <q-item-label class="text-weight-bold">
              {{
                registro.valor.toLocaleString("pt-br", {
                  style: "currency",
                  currency: "BRL",
                })
              }}

              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="registro.usuariocriacao"
                :criacao="registro.criacao"
                :usuarioalteracao="registro.usuarioalteracao"
                :alteracao="registro.alteracao"
              />
            </q-item-label>

            <q-item-label caption v-if="registro.observacoes">
              {{ registro.observacoes }}
            </q-item-label>
            <q-item-label caption v-if="registro.baixa">
              Baixado em:
              <span class="text-weight-bold">{{
                formataDataSemHora(registro.baixa)
              }}</span>
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <!-- EDITAR -->
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="
                  editarRegistroSpc(
                    registro.codregistrospc,
                    registro.valor,
                    registro.inclusao,
                    registro.baixa,
                    registro.observacoes
                  )
                "
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- EXCLUIR -->
              <q-btn
                flat
                dense
                round
                icon="delete"
                size="sm"
                color="grey-7"
                @click="excluirRegistro(registro.codregistrospc)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
    <div v-else class="q-pa-md text-center text-grey">Nenhum registro SPC</div>
  </q-card>
</template>

<style scoped></style>
