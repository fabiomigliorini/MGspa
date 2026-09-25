# MGspa

## Commits — sempre esperar autorização

**Nunca commitar sem o OK explícito de quem pediu.** Ao terminar uma alteração:

1. Deixe o trabalho pronto na árvore de trabalho — **sem** `git add`, **sem** `git commit`.
2. Diga o que mudou e **como testar**: a tela, a URL, o comando a rodar.
3. Espere a validação. Só commite depois de um "pode commitar" claro.

Vale para **qualquer** alteração, inclusive as que parecem triviais, as de documentação e as
do próprio `backlog/`. "Terminei a implementação" não é autorização para commitar — quem
testa é quem autoriza.

**Instrução sobre _como_ commitar não é permissão para commitar.** "Faça commits separados",
"um commit por task", "vai fazendo", "resolve tudo" dizem o formato e o ritmo do trabalho,
para quando a autorização vier. Continue deixando na árvore e perguntando.

Só faça o commit se for escrito explicitamente para fazer.

Se a validação apontar problema, corrija e volte ao passo 2: a correção também não vai
commitada sozinha.

## Gestão de tarefas — Backlog.md

As tarefas do projeto vivem em `backlog/` como arquivos markdown, versionadas junto com o
código. **Não usar GitHub Issues, Trello nem arquivos `todo`** — foram abandonados justamente
por virarem um segundo lugar pra manter.

Nada precisa ser instalado. O wrapper `./backlog.sh` roda a ferramenta num container
descartável (`docker run --rm node:22-slim`), porque os containers do projeto são Alpine/musl
e o binário do Backlog.md é glibc.

    ./backlog.sh task list -s "To Do"
    ./backlog.sh task list --priority high --plain
    ./backlog.sh board
    ./backlog.sh task view TASK-42

Atalho sugerido no seu shell: `alias backlog=/opt/www/MGspa/backlog.sh`

### Regras de trabalho

**0. O Backlog.md é o controle de tarefas do projeto — use sempre, do começo ao fim.**
Antes de começar qualquer trabalho, procure no backlog se já existe task para aquilo; se
existir, trabalhe nela em vez de abrir outra. Durante o trabalho, mantenha o estado da task
em dia: a task é a fonte de verdade do que está sendo feito, não a conversa do chat. Ao
concluir, feche-a. Nenhum trabalho de projeto deve acontecer fora de uma task.

    ./backlog.sh search "banco"
    ./backlog.sh task list -s "To Do"
    ./backlog.sh task list -s "In Progress"

**1. Task só nasce a pedido de quem prioriza — nunca por iniciativa própria.** Achou um bug,
um TODO no fonte, uma ideia, um efeito colateral de outra correção? **Conte no chat e espere.**
Quem decide se aquilo merece existir no backlog é quem vai priorizar e testar. Sem um "pode
criar" explícito, não existe `task create` — vale inclusive para o que parece obviamente um
bug, e vale para task de documentação e do próprio `backlog/`.

Pendência descoberta não fica esquecida em comentário no fonte: ela é relatada. Mas o destino
dela não é `task create` automático — é critério de aceite numa task que já existe (regra 3),
ou task nova depois do OK.

**Revisar o próprio trabalho não abre task.** Se a revisão do que você acabou de escrever
achou problema, conserte antes de pedir validação — não abra task para o efeito colateral do
seu próprio fix.

**Não proponha task que você não consiga explicar em uma frase a quem não leu o código.** Se o
único jeito de justificar é citar nome de função ou flag, o item é critério de aceite de algo
maior — ou não existe.

**2. Antes de criar uma task autorizada, procurar sinergia e consolidar.** O achado quase
sempre tem casa numa task que já existe:

    ./backlog.sh search "nota fiscal"
    ./backlog.sh task list --search "transmissao" --plain
    ./backlog.sh task list -s "To Do" -l negocios --plain

`--search` varre título, descrição, notas e comentários. Se existe task aberta do mesmo
sintoma, da mesma tela ou do mesmo mecanismo, o achado entra **nela** como critério de aceite
(regra 3) — não vira arquivo novo. Arquivo novo só quando a busca não achou nenhuma casa. Na
dúvida entre duas casas, perguntar em vez de duplicar.

    ./backlog.sh task create "Título curto" --type bug -l negocios -d "contexto e origem"

**Type** = a natureza do trabalho: `bug` ou `feature` (o Backlog.md também aceita
`enhancement`, `task`, `chore`, `docs`, `spike`). **Label** = só o app de origem —
`pessoas`, `notas`, `negocios`, `contas`, `estoque`, `agro`, `api`, `components`.
Não repita a natureza como label; para filtrar use `--type`:

    ./backlog.sh task list --type bug --plain
    ./backlog.sh task list -l negocios --plain

**Prioridade é obrigatória — toda task nasce com uma.** São quatro níveis, definidos por
**impacto**, nunca pela natureza do trabalho (essa já é o `type`). Bug não é automaticamente
urgente e feature pode ser: o que decide é o quanto dói.

- `Critical` — gente parada, dinheiro saindo errado ou dado exposto **agora**. Larga o que
  está fazendo. Exige justificar o porquê na descrição. Se tiver mais de 2 ou 3 no backlog,
  alguma não é Critical.
- `High` — dói toda semana, tem workaround manual, ou tem prazo externo (fiscal/legal).
- `Medium` — melhora real de rotina, sem workaround doendo.
- `Low` — quando sobrar tempo; não morre se ficar um ano. **É o default**: na dúvida, entra
  `Low`. Errar pra baixo é barato de promover; `high` inflado não ordena nada.

    ./backlog.sh task list --sort priority --plain
    ./backlog.sh task list --priority critical --plain

Nunca atribuir responsável a outra pessoa; cada um pega a sua.

**3. Uma task por problema vivido — não uma task por causa encontrada.** O título é o
**sintoma, na língua de quem usa o sistema**: "Emissão de nota enrosca e não sai a impressão
automática". Não é a causa técnica: "Guard de resposta atrasada não protegia o IndexedDB". Se
o título só faz sentido depois de ler o código, está errado — quem abre o board tem que
reconhecer o problema porque viveu.

Investigar um sintoma acha várias causas. Elas **não** viram tasks: viram critérios de aceite
dentro da task-mãe, um por causa.

    ./backlog.sh task edit TASK-42 --ac "achado novo da investigação"
    ./backlog.sh task edit TASK-42 --check-ac 1

A task fecha quando todos os critérios estão marcados. Causa raiz, arquivo, linha e roteiro de
teste ficam na descrição e nas notas da task-mãe — ali pode ser técnico quanto precisar.

**Task separada — quando autorizada — só se o trabalho for de OUTRA funcionalidade**: outra
tela, outro app, outro domínio. Bug no quiosque achado enquanto se conserta a nota: candidato
a task nova, porque quem testa é outra pessoa em outro lugar. Terceira causa do mesmo sintoma
na mesma tela: critério de aceite, sem perguntar nada.

**4. Ao começar uma task:** marcar em andamento e atribuir a si.

    ./backlog.sh task edit TASK-42 -s "In Progress" -a @fabio

**5. Ao terminar:** marcar concluída.

    ./backlog.sh task edit TASK-42 -s Done

**6. Commits de correção citam o id da task**, seguindo a convenção do repo:

    [FIX] TASK-42 Corrige rateio de xerox quando não fecha 100%

O arquivo `.md` da task entra no mesmo commit do código — e o commit só acontece depois da
autorização, conforme a seção **Commits** acima.

## Campos de formulário — os componentes da casa, nunca o Quasar cru

**Campo de texto é `@components/MgInput.vue`, não `<q-input>`.** É o mesmo q-input, com as
duas regras que a gente vinha repetindo à mão em cada tela: o X de limpar (`clearable`) e o
campo `readonly` ficam **fora da ordem do Tab**, para quem preenche no teclado andar campo a
campo sem parar no botão de limpar. O X do `clearable` do Quasar tem `tabindex="0"` fixo no
fonte e não tem prop para desligar — por isso o componente.

A troca é 1:1: o `MgInput` repassa os atributos (`label`, `type`, `mask`, `maxlength`,
`rules`, `autofocus`…), os slots `prepend`/`append`/`before`/`after`/`hint` e expõe
`focus`/`blur`/`select`/`validate`/`resetValidation`/`nativeEl`. `outlined` já vem ligado.

Cada tipo de campo tem o seu: valor/número é `MgInputValor`, data/timestamp é `MgInputData`,
seleção é o `MgSelectXxx` do domínio. Componente do Quasar cru só quando nenhum deles cobre.

**Campo novo nasce em `MgInput`.** E **todo formulário que receber manutenção troca os
`q-input` que ainda estiverem nele**, mesmo os que não são o motivo da mexida — é assim que a
varredura acaba. O que sobra está na **TASK-174**.
