# MGspa

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

**1. Toda pendência descoberta vira task, na hora.** Bug encontrado, TODO que ia virar
comentário no código, ideia que surgiu no meio de outra coisa — criar a task, não deixar no
chat nem comentada no fonte.

    ./backlog.sh task create "Título curto" -l bug,negocios -d "contexto e origem"

Labels: `bug` ou `feature`, mais o app de origem — `pessoas`, `notas`, `negocios`, `contas`,
`estoque`, `agro`, `api`, `components`.

Prioridade só quando for evidente: bug, defeito em produção ou problema de segurança nascem
`high`. Fora isso, **deixar sem prioridade** — não chutar. Nunca atribuir responsável a outra
pessoa; cada um pega a sua.

**2. Ao começar uma task:** marcar em andamento e atribuir a si.

    ./backlog.sh task edit TASK-42 -s "In Progress" -a @fabio

**3. Ao terminar:** marcar concluída.

    ./backlog.sh task edit TASK-42 -s Done

**4. Commits de correção citam o id da task**, seguindo a convenção do repo:

    [FIX] TASK-42 Corrige rateio de xerox quando não fecha 100%

O arquivo `.md` da task entra no mesmo commit do código.
