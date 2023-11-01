<template>
    <MGLayout>
        <template #content>
            <div class="q-pa-md q-gutter-md">
                <q-avatar color="primary" size="150px" text-color="white">{{ primeiraletra }}</q-avatar>
                <q-item-label class="q-pa-xl inline-block text-h5">{{ listapessoasview.fantasia }}</q-item-label>
                <q-btn unelevated rounded color="primary" class="q-my-lg" label="Editar" no-caps />
            </div>
            <q-separator></q-separator>
            <div class="q-pa-md q-gutter-md">
                <div class="row col-4-md-4 inline-block ellipsis">
                    <div class="q-pa-md text-h6">
                        Detalhes da pessoa
                    </div>
                    <!-- CARD INF DA TABELA PESSOAS -->
                    <q-card dark bordered class="bg-grey-9 my-card">
                        <q-card-section>
                            <!-- <div class="text-h6">Our Changing Planet</div> -->
                            <div class="text-subtitle2">

                                <div class="row">
                                    <div>Razão Social: <b>{{ listapessoasview.pessoa }}</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.fisica == true">CNPJ/CPF:
                                        <b>{{ formataCPF(listapessoasview.cnpj) }}</b>
                                    </div>
                                    <div v-if="listapessoasview.fisica == false">CNPJ/CPF:
                                        <b>{{ formataCNPJ(listapessoasview.cnpj) }}</b>
                                    </div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.ie == null"> Inscrição Estadual: <b>Vazio</b></div>
                                    <div v-else> Inscrição Estadual: <b>{{ formataie(listapessoasview.ie) }}</b></div>

                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.rntrc !== null"> RNTRC: <b>{{ listapessoasview.rntrc }}</b>
                                    </div>
                                    <div v-else> RNTRC: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.tipotransportador !== null">Tipo Transportador: <b>
                                            {{ listapessoasview.tipotransportador }}</b></div>
                                    <div v-else>Tipo Transportador: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.GrupoCliente">Grupo de Cliente: <b>{{
                                        listapessoasview.GrupoCliente.grupocliente }}</b></div>
                                    <div v-else>Grupo de Cliente: <b>Nenhum</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="row col-1" name="list" />
                                    <div v-if="listapessoasview.notafiscal === 0">Nota Fiscal: <b>Tratamento Padrão</b>
                                    </div>
                                    <div v-if="listapessoasview.notafiscal === 1">Nota Fiscal: <b>Sempre</b></div>
                                    <div v-if="listapessoasview.notafiscal === 2">Nota Fiscal: <b>Somente Fechamento</b>
                                    </div>
                                    <div v-if="listapessoasview.notafiscal === 9">Nota Fiscal: <b>Nunca</b></div>
                                    <div v-if="listapessoasview.notafiscal == null">Nota Fiscal: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.consumidor == true">Consumidor Final: <b>Sim</b></div>
                                    <div v-else>Consumidor Final: <b>Não</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.cliente == true">Cliente: <b>Sim</b></div>
                                    <div v-else>Cliente: <b>Não</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.codformapagamento">Forma de Pagamento:
                                        <b>{{ formapagamento }}</b>
                                    </div>
                                    <div v-else>Forma de Pagamento: <b>Nenhum</b></div>
                                </div>
                            </div>
                        </q-card-section>
                        <q-separator dark inset />
                        <q-card-section>
                        </q-card-section>
                    </q-card>
                </div>
                <!-- CARD INF DA TABELA PESSOAS -->
                <div class="col-4-md-4 inline-block ellipsis">
                    <q-card dark bordered class="bg-grey-9 my-card">
                        <q-card-section>

                            <div class="text-subtitle2">

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.desconto !== null">Desconto:
                                        <b>{{ listapessoasview.desconto }}</b>
                                    </div>
                                    <div v-else>Desconto: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div>Saldo em Aberto: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.creditobloqueado == true">Credito Bloqueado: <b>Sim</b>
                                    </div>
                                    <div v-else>Credito Bloqueado: <b>Não</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.credito !== null">Limite de Credito:
                                        <b>{{ listapessoasview.credito }}</b>
                                    </div>
                                    <div v-else>Limite de Credito: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div>Primeiro Vencimento: <b>(0 dias)</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.toleranciaatraso !== null">Tolerância de Atraso:
                                        <b>{{ listapessoasview.toleranciaatraso }} Dias</b>
                                    </div>
                                    <div v-else>Tolerância de Atraso: <b>0 Dias</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" />
                                    <div v-if="listapessoasview.mensagemvenda !== null">Mensagem de Venda:
                                        <b>{{ listapessoasview.mensagemvenda }}</b>
                                    </div>
                                    <div v-else>Mensagem de Venda: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.observacoes !== null">Observações:
                                        <b>{{ listapessoasview.observacoes }}</b>
                                    </div>
                                    <div v-else>Observações: <b>Vazio</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.vendedor == true">Vendedor: <b>Sim</b></div>
                                    <div v-else>Vendedor: <b>Não</b></div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="list" />
                                    <div v-if="listapessoasview.fornecedor == true">Fornecedor: <b>Sim</b></div>
                                    <div v-else>Fornecedor: <b>Não</b></div>
                                </div>
                            </div>
                        </q-card-section>
                        <q-separator dark inset />
                        <q-card-section>
                        </q-card-section>
                    </q-card>
                </div>
            </div>

            <div class="q-pa-md q-gutter-md">
                <div class="row col-4-md-4 inline-block flex">

                    <div class="q-pa-md text-h6">
                        Contatos e endereço
                    </div>

                    <q-card dark bordered class="bg-grey-9 my-card">
                        <q-card-section>

                            <div class="text-subtitle2">
                                <!-- MOSTRA O ENDEREÇO, EMAIL E TELEFONE DA TABELA PESSOAS -->
                                <div class="row">
                                    <q-icon class="col-1" name="email" />
                                    <a href="#" @click.prevent="linkemail" class="text-white">{{ listapessoasview.email
                                    }}</a>
                                    <q-icon class="col-1" color="primary" name="add"
                                        @click="novoemail, promptnovoemail = true" />
                                </div>

                                <div class="row">
                                    <q-icon class="col-1" name="call" />
                                    <div> <a href="#" @click.prevent="linktel" class="text-white">{{
                                        listapessoasview.telefone1 }}</a>
                                        <q-icon class="col-1" color="primary" name="add"
                                            @click="novotel, promptnovotel = true" />
                                        <!-- <q-tooltip>
                                            Novo
                                        </q-tooltip> -->
                                    </div>
                                </div>

                                <div v-if="listapessoasview.telefone2 !== null || listapessoasview.telefone3 !== null"
                                    class="row">
                                    <q-icon class="col-1" name="call" />
                                    <div v-if="listapessoasview.telefone2 !== null"> <a href="#" @click.prevent="linktel"
                                            class="text-white">{{ listapessoasview.telefone2 }}</a>
                                        <q-icon class="col-1" color="primary" name="add"
                                            @click="novotel, promptnovotel = true" />
                                        <!-- <q-tooltip>
                                            Novo
                                        </q-tooltip> -->
                                    </div>

                                    <div v-if="listapessoasview.telefone3 !== null"> <a href="#" @click.prevent="linktel"
                                            class="text-white">{{ listapessoasview.telefone3 }}</a>
                                        <q-icon class="col-1" color="primary" name="add"
                                            @click="novotel, promptnovotel = true" />
                                        <!-- <q-tooltip>
                                            Novo
                                        </q-tooltip> -->
                                    </div>
                                </div>

                                <div class="row">
                                    <q-icon class="col-1 text-white" name="list">
                                        <!-- <img src="~assets/location.svg" class="bg-white" /> -->
                                    </q-icon>
                                    <div> <a href="#" @click="linkmaps" class="text-white">{{ listapessoasview.endereco }},
                                            {{ listapessoasview.numero }} - {{ listapessoasview.bairro }} - {{ cidade }}</a>
                                        <q-icon class="col-1" color="primary" name="add"
                                            @click="novoendereco, promptnovoendereco = true" />
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                    <!-- CARD TABELA PESSOAS FILHAS -->
                    <q-card dark bordered class="bg-grey-9 my-card"
                        v-if="listapessoasview.PessoaEmailS && listapessoasview.PessoaEmailS.length > 0 ||
                            listapessoasview.PessoaEnderecoS && listapessoasview.PessoaEnderecoS.length > 0 || listapessoasview.PessoaTelefoneS && listapessoasview.PessoaTelefoneS.length > 0">
                        <q-card-section>
                            <!-- <div class="text-h6">Our Changing Planet</div> -->
                            <div class="text-subtitle2">

                                <!-- MOSTRA OS EMAILS DA TABELA FILHA PESSOAS -->
                                <div class="row" v-for="(pessoaemail, index) in listapessoasview.PessoaEmailS"
                                    v-bind:key="index">
                                    <q-icon class="col-1" name="email" />
                                    <div><a href="#" @click.prevent="linkemail" class="text-white">{{ pessoaemail.email
                                    }}</a>
                                        <q-icon color="primary" name="edit"
                                            @click="editaremail(pessoaemail.codpessoatelefone, pessoaemail.email), prompteditaremail = true" />
                                        <q-icon color="primary" name="add" @click="novoemail, promptnovoemail = true" />
                                        <q-icon color="primary" name="delete"
                                            @click="excluiremail(pessoaemail.codpessoatelefone)" />
                                    </div>
                                </div>

                                <!-- MOSTRA OS TELEFONES DA TABELA FILHA PESSOAS -->
                                <div class="row" v-for="(pessoatelefone, index) in listapessoasview.PessoaTelefoneS"
                                    v-bind:key="index">
                                    <q-icon class="col-1" name="call" />
                                    <div> <a class="text-white">{{ '(' + pessoatelefone.ddd + ') ' + pessoatelefone.telefone
                                    }}</a>
                                        <q-icon color="primary" name="edit"
                                            @click="editartel(pessoatelefone.codpessoatelefone, pessoatelefone.ddd, pessoatelefone.telefone), prompt = true" />
                                        <q-icon color="primary" name="add" @click="novotel, promptnovotel = true" />
                                        <q-icon color="primary" name="delete"
                                            @click="excluirtel(pessoatelefone.codpessoatelefone)" />
                                        <!-- <q-tooltip>
                                            Novo
                                        </q-tooltip> -->
                                    </div>
                                </div>

                                <!-- MOSTRA OS ENDEREÇOS DA TABELA FILHA PESSOAS -->
                                <div class="row" v-for="(pessoaendereco, index) in listapessoasview.PessoaEnderecoS"
                                    v-bind:key="index">
                                    <q-icon class="col-1 text-white" name="list">
                                        <!-- <img src="~assets/location.svg" class="bg-white" /> -->
                                    </q-icon>

                                    <div> <u><a @click="linkmapscardfilhas(pessoaendereco.codpessoaendereco, pessoaendereco.codcidade, pessoaendereco.endereco, pessoaendereco.numero, pessoaendereco.bairro, pessoaendereco.cep)"
                                                class="text-white cursor-pointer">{{ pessoaendereco.endereco }},
                                                {{ pessoaendereco.numero }} - {{ pessoaendereco.bairro }} -
                                            </a></u>
                                        <q-icon color="primary" name="edit"
                                            @click="editarendereco(pessoaendereco.codpessoaendereco, pessoaendereco.endereco, pessoaendereco.numero, pessoaendereco.cep, pessoaendereco.complemento, pessoaendereco.bairro, pessoaendereco.codcidade), promptendereco = true" />
                                        <q-icon color="primary" name="add"
                                            @click="novoendereco, promptnovoendereco = true" />
                                        <q-icon color="primary" name="delete"
                                            @click="excluirendereco(pessoaendereco.codpessoaendereco)" />
                                    </div>
                                    <!-- <div hidden>{{consultarcidade(pessoaendereco.codcidade) }}</div> -->
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                    <!-- DIALOG EDITAR TELEFONE  -->
                    <q-dialog v-model="prompt" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Editar Telefone</div>
                            </q-card-section>
                            <q-form @submit.prevent="salvartel">
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modeltelupdate.ddd" autofocus label="DDD"
                                        @keyup.enter="prompt = false" />
                                    <q-input outlined dense v-model="modeltelupdate.telefone" autofocus label="Telefone"
                                        @keyup.enter="prompt = false" />
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="submit" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>
                    <!-- DIALOG NOVO TELEFONE  -->
                    <q-dialog v-model="promptnovotel" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Novo Telefone</div>
                            </q-card-section>
                            <q-form>
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modelnovotel.ddd" autofocus label="DDD"
                                        @keyup.enter="promptnovotel = false" />
                                    <q-input outlined dense v-model="modelnovotel.telefone" autofocus label="Telefone"
                                        @keyup.enter="promptnovotel = false" />
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="button" @click="novotel" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>
                    <!-- DIALOG EDITAR ENDERECO -->
                    <q-dialog v-model="promptendereco" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Editar Endereco</div>
                            </q-card-section>
                            <q-form @submit.prevent="salvarendereco">
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modeleditarendereco.endereco" autofocus
                                        label="Endereço" :rules="[
                                            val => val && val.length > 0 || 'Endereço obrigatório'
                                        ]" required />
                                    <q-input outlined dense v-model="modeleditarendereco.numero" autofocus label="Numero"
                                        :rules="[
                                            val => val && val.length > 0 || 'Número obrigatório'
                                        ]" required />
                                    <q-input outlined dense v-model="modeleditarendereco.bairro" autofocus label="Bairro"
                                        :rules="[
                                            val => val && val.length > 0 || 'Bairro obrigatório'
                                        ]" required />
                                    <q-input outlined v-model="modeleditarendereco.cep" label="CEP" mask="#####-###"
                                        @change="BuscaCep()" unmasked-value dense reactive-rules :rules="[
                                            val => val && val.length > 7 || 'CEP inválido'
                                        ]" required />
                                    <q-input outlined v-model="modeleditarendereco.complemento" label="Complemento" dense
                                        autofocus />
                                    <q-select outlined v-model="modeleditarendereco.codcidade" use-input input-debounce="0"
                                        label="Cidade" :options="options" options-label="label" options-value="value"
                                        map-options emit-value clearable @filter="filtrocidade" behavior="menu">

                                        <template v-slot:no-option>
                                            <q-item>
                                                <q-item-section class="text-grey">
                                                    Nenhum resultado encontrado.
                                                </q-item-section>
                                            </q-item>
                                        </template>
                                    </q-select>
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="submit" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>

                    <!-- DIALOG NOVO ENDERECO  -->
                    <q-dialog v-model="promptnovoendereco" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Novo Endereço</div>
                            </q-card-section>
                            <q-form>
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modelnovoendereco.endereco" autofocus label="Endereço"
                                        :rules="[
                                            val => val && val.length > 0 || 'Endereço obrigatório'
                                        ]" required />
                                    <q-input outlined dense v-model="modelnovoendereco.numero" autofocus label="Numero"
                                        :rules="[
                                            val => val && val.length > 0 || 'Número obrigatório'
                                        ]" required />
                                    <q-input outlined dense v-model="modelnovoendereco.bairro" autofocus label="Bairro"
                                        :rules="[
                                            val => val && val.length > 0 || 'Bairro obrigatório'
                                        ]" required />
                                    <q-input outlined v-model="modelnovoendereco.cep" label="CEP" mask="#####-###"
                                        @change="BuscaCep()" unmasked-value dense reactive-rules :rules="[
                                            val => val && val.length > 7 || 'CEP inválido'
                                        ]" required />
                                    <q-input outlined v-model="modelnovoendereco.complemento" label="Complemento" dense />
                                    <q-select outlined v-model="modelnovoendereco.codcidade" use-input input-debounce="0"
                                        label="Cidade" :options="options" options-label="label" options-value="value"
                                        map-options emit-value clearable @filter="filtrocidade" behavior="menu">

                                        <template v-slot:no-option>
                                            <q-item>
                                                <q-item-section class="text-grey">
                                                    Nenhum resultado encontrado.
                                                </q-item-section>
                                            </q-item>
                                        </template>
                                    </q-select>
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="button" @click="novoendereco" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>

                    <!-- DIALOG EDITAR EMAIL  -->
                    <q-dialog v-model="prompteditaremail" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Editar Email</div>
                            </q-card-section>
                            <q-form>
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modelemailupdate.email" autofocus label="Email" />
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="button" @click="salvaremail" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>

                    <!-- DIALOG NOVO EMAIL  -->
                    <q-dialog v-model="promptnovoemail" persistent>
                        <q-card style="min-width: 350px">
                            <q-card-section>
                                <div class="text-h6">Novo Email</div>
                            </q-card-section>
                            <q-form>
                                <q-card-section class="q-pt-none">
                                    <q-input outlined dense v-model="modelnovoemail.email" autofocus label="Email" />
                                </q-card-section>

                                <q-card-actions align="right" class="text-primary">
                                    <q-btn flat label="Cancelar" v-close-popup />
                                    <q-btn flat label="Salvar" type="button" @click="novoemail" v-close-popup />
                                </q-card-actions>
                            </q-form>
                        </q-card>
                    </q-dialog>
                </div>
            </div>
            <!-- HISTORICO DE COBRANÇA, SPC, CERTIDÕES -->
            <div class="row float-left" style="width: 100%;">

                <q-tabs v-model="tab" dense class="text-grey" active-color="primary" indicator-color="primary"
                    narrow-indicator>

                    <q-tab name="hist" label="Histórico de Cobrança" />
                    <q-tab name="spc" label="Registro SPC" />
                    <q-tab name="certidoes" label="Certidões" />
                </q-tabs>
            </div>

            <div class="row float-left" style="width: 100%;">
                <q-tab-panels v-model="tab" animated>
                    <q-tab-panel name="hist">
                        <div class="text-h6 clear-left">Histórico de Cobranças
                            <q-btn round color="primary" size="10px" icon="add" dense />
                        </div>
                        Nenhum resultado encontrado.
                    </q-tab-panel>

                    <q-tab-panel name="spc">
                        <div class="text-h6">Registro SPC
                            <q-btn round color="primary" size="10px" icon="add" dense />
                        </div>
                        Nenhum resultado encontrado.
                    </q-tab-panel>

                    <q-tab-panel name="certidoes">
                        <div class="text-h6">Certidões
                            <q-btn round color="primary" size="10px" icon="add" dense />
                        </div>
                        Nenhum resultado encontrado.
                    </q-tab-panel>
                </q-tab-panels>
            </div>
            <!-- <div class="q-pa-md float-right" style="max-width: 350px">
                <q-list bordered separator>
                <q-item>
                    <q-item-section>
                    <q-item-label overline bold>fantasia</q-item-label>
                    <q-item-label>{{ listapessoasview.fantasia }}</q-item-label>
                    </q-item-section>
                </q-item>
                </q-list>
                </div> -->
        </template>
    </MGLayout>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'

const primeiraletra = ref('')
export default {

    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))
    },
    methods: {

        formataCPF(cpf) {
            if (cpf == null) {
                return cpf
            }
            cpf = cpf.toString().padStart(11, '0')
            return cpf.slice(0, 3) + "." +
                cpf.slice(3, 6) + "." +
                cpf.slice(6, 9) + "-" +
                cpf.slice(9, 11)
        },

        formataie(ie) {
            if (ie == null) {
                return ie
            }
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "-" +
                ie.slice(8, 9)
        },

        formataCNPJ(cnpj) {
            if (cnpj == null) {
                return cnpj
            }
            cnpj = cnpj.toString().padStart(14, '0')
            return cnpj.slice(0, 2) + "." +
                cnpj.slice(2, 5) + "." +
                cnpj.slice(5, 8) + "/" +
                cnpj.slice(8, 12) + "-" +
                cnpj.slice(12, 14)
        },
    },

    async mounted() {
        const route = useRoute()
        const { data } = await api.get('v1/pessoa/' + route.params.id)

        // PEGA A PRIMEIRA LETRA DO NOME PARA PREENCHER NO AVATAR
        if (data.data.fantasia.charAt(0) == ' ') {
            primeiraletra.value = data.data.fantasia.charAt(1)
        } else {
            primeiraletra.value = data.data.fantasia.charAt(0)
        }
    },

    setup() {
        const loading = ref(true)
        const $q = useQuasar()
        const route = useRoute()
        const router = useRouter()

        const OptionsCidade = ref([])
        const options = ref(OptionsCidade)
        const listapessoasview = ref([])
        const cidade = ref([])
        const formapagamento = ref([])
        const pessoatelefonecod = ref('')
        const modeltelupdate = ref({ ddd: '', telefone: '' })
        const modeleditarendereco = ref({ endereco: '', numero: '', cep: '', complemento: '', bairro: '', codcidade: '' })
        const modelnovotel = ref({ ddd: '', telefone: '', codpessoa: '' })
        const modelnovoendereco = ref({ endereco: '', numero: '', cep: '', complemento: '', bairro: '', codcidade: '' })
        const modelemailupdate = ref({ codpessoatelefone: '', email: '' })
        const modelnovoemail = ref({ codpessoa: '', email: '' })

        // Mostra as primeiras 100 cidades da API
        const SelectCidade = async () => {

            try {
                const { data } = await api.get('v1/select/cidade')
                OptionsCidade.value = data
            } catch (error) {
                console.log(error.data)
            }
        }


        // Faz as listagem dos detalhes da pessoa
        const getDetalhesPessoa = async () => {
            $q.loading.show({
            })
            try {
                const { data } = await api.get('v1/pessoa/' + route.params.id)
                const consultacidade = await api.get('v1/select/cidade?codcidade=' + data.data.codcidade)
                const consultaformapagamento = await api.get('v1/pessoa/formadepagamento?codformapagamento=' + data.data.codformapagamento)
                cidade.value = consultacidade.data[0].label
                formapagamento.value = consultaformapagamento.data

                loading.value = false
                listapessoasview.value = data.data


                $q.loading.hide()
            } catch (error) {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'warning',
                    message: 'Erro registro não encontrado.'
                })
                router.push('/pessoa/')
            }
        }

        // BOTÃO EDITAR PESSOA
        const editar = async () => {

            if (route.params.id) {
                var a = document.createElement('a');
                // a.target="_blank";
                a.href = "/#/pessoa/edit/" + route.params.id
                a.click();
            }
        }

        // CARREGA AS INFORMAÇOES DO ENDEREÇO PARA EDITAR
        const editarendereco = async (codpessoaendereco, endereco, numero, cep, complemento, bairro, codcidade) => {

            modeleditarendereco.value = {
                codpessoaendereco: codpessoaendereco, endereco: endereco, numero: numero, cep: cep,
                complemento: complemento, bairro: bairro, codcidade: codcidade
            }

        }
        // CARREGA AS INFORMAÇOES DO TELEFONE PARA EDITAR
        const editartel = async (codpessoatelefone, ddd, telefone) => {

            modeltelupdate.value = { ddd: ddd, telefone: telefone }
            pessoatelefonecod.value = codpessoatelefone
        }

        // CARREGA AS INFORMAÇOES DO TELEFONE PARA EDITAR
        const editaremail = async (codpessoatelefone, email) => {

            modelemailupdate.value = { codpessoatelefone: codpessoatelefone, email: email }
        }

        // SALVA UPDATE DO TELEFONE NO BANCO
        const salvartel = async () => {

            try {
                const data = await api.put('v1/pessoa/' + route.params.id + '/telefone/' + pessoatelefonecod.value, modeltelupdate.value)
                // '?telefone=' + modeltelupdate.value.telefone + '&ddd=' + modeltelupdate.value.ddd)
                if (data) {
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Telefone alterado'
                    })
                    getDetalhesPessoa()
                } else {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao alterar telefone'
                    })
                }
            } catch (error) {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'Erro ao alterar telefone'
                })
            }

        }

        // SALVA O UPDATE DO ENDEREÇO
        const salvarendereco = async () => {

            try {
                const data = await api.put('v1/pessoa/' + route.params.id + '/endereco/' + modeleditarendereco.value.codpessoaendereco, modeleditarendereco.value)
                // '?telefone=' + modeltelupdate.value.telefone + '&ddd=' + modeltelupdate.value.ddd)
                if (data) {
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Endereco alterado'
                    })
                    getDetalhesPessoa()
                } else {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao alterar endereco'
                    })
                }
            } catch (error) {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'Erro ao alterar endereco'
                })
            }

        }

        // SALVA UPDATE DO EMAIL NO BANCO
        const salvaremail = async () => {


            try {
                const data = await api.put('v1/pessoa/' + route.params.id + '/email/' + modelemailupdate.value.codpessoatelefone, { email: modelemailupdate.value.email })
                // '?telefone=' + modeltelupdate.value.telefone + '&ddd=' + modeltelupdate.value.ddd)

                if (data) {
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Email alterado'
                    })
                    getDetalhesPessoa()
                } else {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao alterar email, tente novamente'
                    })
                }
            } catch (error) {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'Erro desconhecido ao alterar email'
                })
            }

        }

        const excluirtel = async (codpessoatelefone) => {

            $q.dialog({
                title: 'Excluir Contato',
                message: 'Tem certeza que deseja excluir esse telefone?',
                cancel: true,
                persistent: true
            }).onOk(async () => {
                try {
                    const { data } = await api.delete('v1/pessoa/' + route.params.id + '/telefone/' + codpessoatelefone)
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Telefone excluido'
                    })
                    getDetalhesPessoa()
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao excluir telefone'
                    })
                }
            })
        }

        const excluirendereco = async (codpessoaendereco) => {

            $q.dialog({
                title: 'Excluir Endereço',
                message: 'Tem certeza que deseja excluir esse endereço?',
                cancel: true,
                persistent: true
            }).onOk(async () => {
                try {
                    const { data } = await api.delete('v1/pessoa/' + route.params.id + '/endereco/' + codpessoaendereco)
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Endereço excluido'
                    })
                    getDetalhesPessoa()
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao excluir endereço'
                    })
                }
            })
        }


        const excluiremail = async (codpessoatelefone) => {

            $q.dialog({
                title: 'Excluir Email',
                message: 'Tem certeza que deseja excluir esse email?',
                cancel: true,
                persistent: true
            }).onOk(async () => {
                try {
                    const { data } = await api.delete('v1/pessoa/' + route.params.id + '/email/' + codpessoatelefone)
                    $q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Email excluido'
                    })
                    getDetalhesPessoa()
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro desconhecido ao excluir email'
                    })
                }
            })
        }

        // CRIA UM NOVO TELEFONE
        const novotel = async () => {

            if (modelnovotel.value.telefone !== '') {
                modelnovotel.value.codpessoa = route.params.id
                try {
                    const { data } = await api.post('v1/pessoa/' + route.params.id + '/telefone', modelnovotel.value)

                    if (data) {
                        $q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Telefone criado.'
                        })
                        getDetalhesPessoa()
                    }
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao criar telefone'
                    })
                }
            } else {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'Erro ao criar telefone, campo telefone obrigatório'
                })
            }
        }

        // CRIA NOVO ENDERECO
        const novoendereco = async () => {

            if (modelnovoendereco.value.endereco !== '') {
                modelnovoendereco.value.codpessoa = route.params.id

                try {
                    const { data } = await api.post('v1/pessoa/' + route.params.id + '/endereco', modelnovoendereco.value)

                    if (data) {
                        $q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Endereço criado.'
                        })
                        getDetalhesPessoa()
                    }
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao criar endereço'
                    })
                }
            } else {
                $q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'Erro ao criar endereço, campo endereço é obrigatório'
                })
            }
        }

        // CRIA NOVO EMAIL
        const novoemail = async () => {

            if (modelnovoemail.value.email !== '') {
                modelnovoemail.value.codpessoa = route.params.id

                try {
                    const { data } = await api.post('v1/pessoa/' + route.params.id + '/email', modelnovoemail.value)

                    if (data) {
                        $q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Email criado.'
                        })
                        getDetalhesPessoa()
                    }
                } catch (error) {
                    $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro desconhecido ao criar email'
                    })
                }
            }else{
                $q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Erro ao criar email, campo email é obrigatório'
                    })
            }
        }

        // GERA O LINK EMAILS CARD TABELA PESSOA
        const linkemail = async () => {

            var a = document.createElement('a');
            a.href = "mailto:" + listapessoasview.value.email
            a.click();
        }

        // GERA O LINK TEL CARD TABELA PESSOA
        const linktel = async () => {

            var a = document.createElement('a');
            a.href = "tel:" + listapessoasview.value.telefone1
            a.click();
        }

        //// GERA O LINK GOOGLE MAPS CARD TABELA PESSOA
        const linkmaps = async () => {

            var cidademaps = cidade.value
            cidademaps = cidademaps.split('/')
            var a = document.createElement('a');
            a.target = "_blank"
            a.href = "https://www.google.com/maps/search/?api=1&query=" + listapessoasview.value.endereco + ',' +
                listapessoasview.value.numero + ',' + cidademaps[0] + ',' + listapessoasview.value.cep
            a.click();
        }

        // GERA O LINK GOOGLE MAPS CARD PESSOA FILHA // PESSOA ENDEREÇO
        const linkmapscardfilhas = async (codpessoaendereco, codcidade, endereco, numero, bairro, cep) => {

            const consultacidade = await api.get('v1/select/cidade?codcidade=' + codcidade)

            var cidademaps = consultacidade.data[0].label

            cidademaps = cidademaps.split('/')
            var a = document.createElement('a');
            a.target = "_blank"
            a.href = "https://www.google.com/maps/search/?api=1&query=" + endereco + ',' +
                numero + ',' + cidademaps[0] + ',' + cep
            a.click();
        }

        // EXCLUI A PESSOA 
        const ExcluirPessoa = async () => {

            $q.dialog({
                title: 'Excluir Pessoa',
                message: 'Tem certeza que deseja excluir essa pessoa?',
                cancel: true,
                persistent: false
            }).onOk(async () => {
                const { data } = await api.delete('v1/pessoa/' + route.params.id)

                if (data.result == true) {
                    $q.notify({
                        color: 'green-4',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Pessoa excluida.'
                    })
                    router.push('/pessoa/')
                }
            })

        }

        // Busca CEP e preenche o input do endereço e bairro do prompt editar endereço e novo endereço
        const BuscaCep = async () => {
            setTimeout(async () => {

                if (modeleditarendereco.value.cep.length > 7) {
                    const { data } = await api.get('https://viacep.com.br/ws/' + modeleditarendereco.value.cep + '/json/')

                    if (data.logradouro) {

                        modeleditarendereco.value.endereco = data.logradouro
                        modeleditarendereco.value.bairro = data.bairro
                        return false
                    }
                    if (data.erro == true) {

                        return true
                    }
                    return
                }

                if (modelnovoendereco.value.cep.length > 7) {
                    const { data } = await api.get('https://viacep.com.br/ws/' + modelnovoendereco.value.cep + '/json/')

                    if (data.logradouro) {

                        modelnovoendereco.value.endereco = data.logradouro
                        modelnovoendereco.value.bairro = data.bairro
                        return false
                    }
                    if (data.erro == true) {

                        return true
                    }
                    return
                } else return

            }, 1000)
        }

        onMounted(() => {
            getDetalhesPessoa()
            SelectCidade()
        })

        return {
            listapessoasview,
            primeiraletra,
            linkemail,
            formapagamento,
            cidade,
            linktel,
            linkmaps,
            editartel,
            tab: ref('hist'),
            editar,
            ExcluirPessoa,
            modeltelupdate,
            salvartel,
            novotel,
            modelnovotel,
            linkmapscardfilhas,
            excluirtel,
            editarendereco,
            modeleditarendereco,
            options,
            salvarendereco,
            BuscaCep,
            excluirendereco,
            novoendereco,
            modelnovoendereco,
            pessoatelefonecod,
            modelemailupdate,
            editaremail,
            salvaremail,
            modelnovoemail,
            novoemail,
            excluiremail,
            prompt: ref(false),
            promptnovotel: ref(false),
            promptendereco: ref(false),
            promptnovoendereco: ref(false),
            promptnovoemail: ref(false),
            prompteditaremail: ref(false),
            filtrocidade(val, update) {
                if (val === '') {
                    update(() => {
                        options.value = OptionsCidade.value
                    })
                    return
                }
                update(async () => {
                    const needle = val.toLowerCase()
                    try {
                        if (needle.length > 3) {
                            const { data } = await api.get('v1/select/cidade?cidade=' + needle)
                            options.value = data
                            return
                        }
                    } catch (error) {
                        console.log(error)
                    }
                })
            }
        }
    },
}
</script>