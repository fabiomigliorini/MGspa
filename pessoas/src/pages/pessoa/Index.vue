<template>
  <MGLayout drawer>
    <template #tituloPagina> Pessoas </template>

    <template #content>
      <q-infinite-scroll
        @load="scrollInfinito"
        :disable="loading"
        style="min-height: 100vh"
      >
        <div class="row q-pa-md q-col-gutter-md">
          <div
            class="col-xs-12 col-sm-6 col-md-4 col-lg-4 col-xl-3"
            v-for="listagempessoas in sPessoa.arrPessoas"
            v-bind:key="listagempessoas.codpessoa"
          >
            <!-- CARD AQUI -->
            <card-pessoas :listagempessoas="listagempessoas"></card-pessoas>
          </div>
        </div>
      </q-infinite-scroll>

      <q-page-sticky
        position="bottom-right"
        :offset="[18, 18]"
        v-if="user.verificaPermissaoUsuario('Publico')"
      >
        <q-btn fab icon="add" color="accent" :to="{ name: 'pessoanova' }" />
      </q-page-sticky>
    </template>

    <!-- Menu Drawer personalizado filtro -->
    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Pessoa
              <q-btn
                icon="replay"
                @click="buscarPessoas()"
                flat
                round
                no-caps
              />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarPessoas()">
          <div class="q-pa-md q-gutter-md">
            <q-select
              outlined
              v-model="sPessoa.filtroPesquisa.fisica"
              label="Tipo Pessoa"
              :options="[
                { label: 'Pessoa Física', value: true },
                { label: 'Pessoa Jurídica', value: false },
              ]"
              map-options
              emit-value
              clearable
            />
            <q-input
              outlined
              v-model="sPessoa.filtroPesquisa.codpessoa"
              label="#"
              ref="codpessoa"
              type="number"
            />
            <q-input
              outlined
              v-model="sPessoa.filtroPesquisa.pessoa"
              ref="pessoa"
              label="Pessoa"
              autofocus
              unmasked-value
            />
            <q-input
              outlined
              v-model="sPessoa.filtroPesquisa.cnpj"
              ref="cnpj"
              label="Cnpj/Cpf"
              unmasked-value
            />
            <q-input
              outlined
              v-model="sPessoa.filtroPesquisa.email"
              ref="email"
              label="Email"
            />
            <q-input
              outlined
              v-model="sPessoa.filtroPesquisa.fone"
              ref="fone"
              label="Fone"
              type="number"
            />
            <SelectGrupoEconomico
              v-model="sPessoa.filtroPesquisa.codgrupoeconomico"
              label="Grupo Econômico"
            >
            </SelectGrupoEconomico>
            <SelectCidade
              v-model="sPessoa.filtroPesquisa.codcidade"
              label="Cidade"
            >
            </SelectCidade>
            <q-select
              outlined
              v-model="sPessoa.filtroPesquisa.inativo"
              label="Ativo / Inativo"
              :options="[
                { label: 'Ativos', value: 'A' },
                { label: 'Inativos', value: 'I' },
              ]"
              map-options
              emit-value
              clearable
            />

            <SelectFormaPagamento
              v-model="sPessoa.filtroPesquisa.codformapagamento"
              label="Forma Pagamento"
            >
            </SelectFormaPagamento>
            <SelectGrupoCliente
              v-model="sPessoa.filtroPesquisa.codgrupocliente"
              label="Grupo Cliente"
            >
            </SelectGrupoCliente>
          </div>
        </q-form>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { ref, onMounted, defineAsyncComponent, watch } from "vue";
import { useQuasar } from "quasar";
import { formataDocumetos } from "src/stores/formataDocumentos";
import { useRouter } from "vue-router";
import { guardaToken } from "src/stores";
import { pessoaStore } from "src/stores/pessoa";
import { debounce } from "quasar";

export default {
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    SelectGrupoEconomico: defineAsyncComponent(() =>
      import("components/pessoa/SelectGrupoEconomico.vue")
    ),
    SelectCidade: defineAsyncComponent(() =>
      import("components/pessoa/SelectCidade.vue")
    ),
    SelectFormaPagamento: defineAsyncComponent(() =>
      import("components/pessoa/SelectFormaPagamento.vue")
    ),
    SelectGrupoCliente: defineAsyncComponent(() =>
      import("components/pessoa/SelectGrupoCliente.vue")
    ),
    CardPessoas: defineAsyncComponent(() =>
      import("components/pessoa/CardPessoas.vue")
    ),
  },

  setup() {
    const loading = ref(true);
    const $q = useQuasar();
    const router = useRouter();
    const user = guardaToken();
    const Documentos = formataDocumetos();
    const sPessoa = pessoaStore();
    const filtro = ref([]);

    const listapessoas = ref([]);

    const buscarPessoas = debounce(async () => {
      $q.loadingBar.start();
      sPessoa.filtroPesquisa.page = 1;
      try {
        const ret = await sPessoa.buscarPessoas();
        loading.value = false;
        $q.loadingBar.stop();
        if (ret.data.data.length == 0) {
          return $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "warning",
            message: "Nenhum Registro encontrado",
          });
        }
      } catch (error) {
        $q.loadingBar.stop();
      }
    }, 500);

    watch(
      () => sPessoa.filtroPesquisa.codformapagamento,
      () => buscarPessoas(),
      { deep: true }
    );

    watch(
      () => sPessoa.filtroPesquisa.inativo,
      () => buscarPessoas(),
      { deep: true }
    );

    watch(
      () => sPessoa.filtroPesquisa.codgrupocliente,
      () => buscarPessoas(),
      { deep: true }
    );

    watch(
      () => sPessoa.filtroPesquisa.fisica,
      () => buscarPessoas(),
      { deep: true }
    );

    onMounted(async () => {
      if (sPessoa.arrPessoas.length == 0) {
        buscarPessoas();
      }
      if (sPessoa.filtroPesquisa.codcidade) {
        const ret = await sPessoa.consultaCidade(
          sPessoa.filtroPesquisa.codcidade
        );
        sPessoa.filtroPesquisa.codcidade = ret.data[0];
      }
    });

    return {
      model: ref(null),
      listapessoas,
      filtro,
      Documentos,
      user,
      router,
      buscarPessoas,
      sPessoa,
      loading,
      async scrollInfinito(index, done) {
        loading.value = true;
        $q.loadingBar.start();
        sPessoa.filtroPesquisa.page++;
        const ret = await sPessoa.buscarPessoas();
        loading.value = false;
        $q.loadingBar.stop();
        if (ret.data.data.length == 0) {
          loading.value = true;
        }
        await done();
      },
    };
  },
};
</script>
