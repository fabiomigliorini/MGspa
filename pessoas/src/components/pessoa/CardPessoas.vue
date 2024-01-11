<template>
    <q-card v-ripple>
        <q-item :to="'/pessoa/' + listagempessoas.codpessoa" clickable>
            <q-item-section avatar>
                <q-avatar color="primary" class="q-my-md" size="35px" text-color="white" v-if="sPessoa.item.fantasia">
                    {{ primeiraLetra(listagempessoas.fantasia) }}
                </q-avatar>
            </q-item-section>
            <q-item-section>
                <q-item-label class="ellipsis text-h6" :class="listagempessoas.inativo ? 'text-strike text-red-14' : null">
                    {{ listagempessoas.fantasia }}
                </q-item-label>
                <q-item-label class="ellipsis" v-if="listagempessoas.inativo">
                    Inativo Desde: {{
                        Documentos.formataData(listagempessoas.inativo) }}
                </q-item-label>
                <q-item-label caption class="ellipsis">
                    {{ listagempessoas.pessoa }}
                </q-item-label>
                <q-item-label caption class="ellipsis" v-if="listagempessoas.codgrupoeconomico">
                    {{ listagempessoas.GrupoEconomico.grupoeconomico }}
                </q-item-label>
            </q-item-section>
        </q-item>

        <q-list>
            <q-item :to="'/pessoa/' + listagempessoas.codpessoa" clickable>
                <q-item-section avatar>
                    <q-icon color="primary" name="fingerprint" />
                </q-item-section>
                <q-item-section>
                    <q-item-label class="ellipsis">
                        <template v-if="listagempessoas.fisica">
                            {{ Documentos.formataCPF(listagempessoas.cnpj) }}
                        </template>
                        <template v-else>
                            {{ Documentos.formataCNPJ(listagempessoas.cnpj) }}
                        </template>
                    </q-item-label>
                    <q-item-label caption v-if="listagempessoas.ie">
                        {{ Documentos.formataIePorSigla(listagempessoas.ie) }}
                    </q-item-label>
                </q-item-section>
            </q-item>

            <template v-if="listagempessoas.PessoaEnderecoS">
                <q-item v-for="end in listagempessoas.PessoaEnderecoS.filter(end => end.nfe == true)"
                    :key="end.codpessoaendereco" :to="'/pessoa/' + listagempessoas.codpessoa" clickable>
                    <q-item-section avatar>
                        <q-icon color="red" name="place" />
                    </q-item-section>
                    <q-item-section>
                        <q-item-label class="ellipsis">
                            {{ end.cidade }} / {{ end.uf }}
                        </q-item-label>
                        <q-item-label caption class="ellipsis">
                            {{ end.endereco }},
                            {{ end.numero }},
                            <template v-if="end.complemento">
                                {{ end.complemento }},
                            </template>
                            {{ end.bairro }}
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </template>
            <template v-if="listagempessoas.PessoaEmailS">
                <q-item v-for="email in listagempessoas.PessoaEmailS.filter(email => email.nfe == true)"
                    :key="email.codpessoaemail" :to="'/pessoa/' + listagempessoas.codpessoa" clickable>
                    <q-item-section avatar>
                        <q-icon color="primary" name="email" />
                    </q-item-section>
                    <q-item-section>
                        <q-item-label class="ellipsis">
                            {{ email.email }}
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </template>

            <template v-if="listagempessoas.PessoaTelefoneS">
                <q-item v-for="telefone in listagempessoas.PessoaTelefoneS" :key="telefone.codpessoatelefone"
                    :to="'/pessoa/' + listagempessoas.codpessoa" clickable>
                    <q-item-section avatar>
                        <q-icon color="primary" name="phone" />
                    </q-item-section>
                    <q-item-section>
                        <q-item-label class="ellipsis">
                            ({{ telefone.ddd }})
                            {{ Documentos.formataFone(telefone.tipo, telefone.telefone) }}
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </template>
        </q-list>
    </q-card>
</template>


<script>
import { defineAsyncComponent } from 'vue'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { pessoaStore } from 'src/stores/pessoa'

export default {

    props: ['listagempessoas'],
    methods: {

        primeiraLetra(fantasia) {
            if (fantasia.charAt(0) == ' ') {
                return fantasia.charAt(1)
            }
            return fantasia.charAt(0)
        },
    },

    setup() {
        const Documentos = formataDocumetos()
        const sPessoa = pessoaStore()

        return {
            Documentos,
            sPessoa
        }
    },
}
</script>