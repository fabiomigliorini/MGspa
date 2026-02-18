<script setup>
import { computed } from "vue";
import {
  formataData,
  formataCPF,
  formataCNPJ,
  formataFone,
} from "src/utils/formatador";

const props = defineProps({
  listagempessoas: {
    type: Object,
    required: true,
  },
});

const primeiroEndereco = computed(() => {
  const enderecos = props.listagempessoas.PessoaEnderecoS;
  if (!enderecos) return null;
  return enderecos.find((e) => e.nfe) || null;
});

const primeiroEmail = computed(() => {
  const emails = props.listagempessoas.PessoaEmailS;
  if (!emails || emails.length === 0) return null;
  return emails[0];
});

const primeiroTelefone = computed(() => {
  const telefones = props.listagempessoas.PessoaTelefoneS;
  if (!telefones || telefones.length === 0) return null;
  return telefones[0];
});
</script>

<template>
  <router-link
    :to="'/pessoa/' + listagempessoas.codpessoa"
    class="full-height link-card"
  >
  <q-card
    bordered
    flat
    class="cursor-pointer full-height q-pb-md"
  >
    <!-- BADGE INATIVO -->
    <q-badge
      v-if="listagempessoas.inativo"
      color="red"
      floating
      label="Inativo"
    />

    <!-- HEADER -->
    <q-card-section class="text-grey-9 text-overline q-pb-none">
      <div class="flex items-center" style="height: 3rem">
        <div class="ellipsis-2-lines titulo-fantasia">
          {{ listagempessoas.fantasia }}
        </div>
      </div>
      <div class="text-caption text-grey ellipsis razao-social">
        {{ listagempessoas.pessoa }}
      </div>
    </q-card-section>

    <q-separator inset class="q-mt-sm" />

    <!-- DETALHES -->
    <q-list>
      <!-- CNPJ/CPF -->
      <q-item>
        <q-item-section avatar>
          <q-icon color="primary" name="fingerprint" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            <template v-if="listagempessoas.fisica">
              {{ formataCPF(listagempessoas.cnpj) }}
            </template>
            <template v-else>
              {{ formataCNPJ(listagempessoas.cnpj) }}
            </template>
          </q-item-label>
          <q-item-label caption class="ellipsis" v-if="listagempessoas.ie">
            {{ listagempessoas.ie }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- ENDERECO -->
      <q-item v-if="primeiroEndereco" dense>
        <q-item-section avatar>
          <q-icon color="red" name="place" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ primeiroEndereco.cidade }} / {{ primeiroEndereco.uf }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ primeiroEndereco.endereco }}, {{ primeiroEndereco.numero
            }}<template v-if="primeiroEndereco.complemento"
              >, {{ primeiroEndereco.complemento }}</template
            >
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ primeiroEndereco.bairro }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- EMAIL -->
      <q-item v-if="primeiroEmail" dense>
        <q-item-section avatar>
          <q-icon color="primary" name="email" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ primeiroEmail.email }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- TELEFONE -->
      <q-item v-if="primeiroTelefone" dense>
        <q-item-section avatar>
          <q-icon color="primary" name="phone" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            ({{ primeiroTelefone.ddd }})
            {{ formataFone(primeiroTelefone.tipo, primeiroTelefone.telefone) }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-card>
  </router-link>
</template>

<style scoped>
.titulo-fantasia {
  line-height: 1.3;
  font-size: 1rem;
}

.razao-social {
  text-transform: none;
}

.link-card {
  text-decoration: none;
  color: inherit;
}
</style>
