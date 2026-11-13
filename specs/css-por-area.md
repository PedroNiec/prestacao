# SPEC — CSS dividido por área (base + um arquivo por área)

**Versão:** 1.0
**Status:** Implementada (2026-11-13)
**Data:** 2026-11-13

---

## 1. Contexto

Todo o CSS do sistema está em `public/assets/app.css` (40.915 de 40.960 bytes permitidos pelo teste de tamanho, DEC-L3).
O redesenho visual (fase 3, estilo de Municípios) ainda precisa passar por todas as telas e não cabe mais no arquivo único.
O arquivo já é organizado em seções numeradas (tokens, base, layout, componentes, responsivo, guia visual, tela do Projeto, demais telas,
menu lateral, Municípios, lista de Projetos, balancete, sidebar recolhível). Os testes de design leem esse arquivo único (tamanho, classes
usadas nos templates, variáveis sem definição, contraste).

Levantamento feito em 2026-11-13 (uso de cada seção pelos templates):

| Seção | Tamanho | Usada por | Destino |
|---|---|---|---|
| 1 a 6, 10, 15 (tokens, base, layout, componentes, compatibilidade, responsivo, menu lateral, sidebar recolhível) | ~26 KB | todas | **base** |
| 8 Tela do Projeto (4,7 KB) e 9 Demais telas (3 KB) | ~7,7 KB | **várias áreas** (kpi, secao, cartao-pdf, estado-vazio, trilha etc. aparecem em balancete, conciliação, bens, físico-financeiro, municípios) | **base** (compartilhado) |
| 7 Guia visual | 1,7 KB | só `guia_visual` | `guia-visual.css` |
| 11 Municípios | 3,5 KB | `municipios` (e alguns blocos reaproveitados) | `municipios.css` + o que for compartilhado vai para a base |
| 12 Lista de Projetos | 2,4 KB | só `projetos` | `projetos.css` |
| 13 e 14 Balancete | 1,3 KB | só `balancete` | `balancete.css` |

Ou seja, só uns 9 KB são realmente de uma área; a base continua com ~31 KB. O ganho principal não é espaço imediato, e sim que
**cada tela passa a ter o próprio arquivo e o próprio limite**, então o redesenho de uma tela não disputa espaço com as outras.

## 2. Objetivo

Dividir o CSS em um arquivo **base** (compartilhado) e **um arquivo por área** (carregado só nas telas daquela área), **sem nenhuma mudança visual**,
preparando o redesenho tela por tela (primeira: tela do Projeto, em spec própria).

## 3. Usuários envolvidos

Uma pessoa, sem login. A mudança é interna (organização do CSS); o usuário não deve perceber diferença nas telas.

## 4. Requisitos funcionais

- RF-01: `app.css` passa a conter só o que é compartilhado por mais de uma área (tokens, base, layout e menu lateral, componentes, compatibilidade, responsivo e as classes compartilhadas hoje nas seções 8 e 9).
- RF-02: Cada área tem seu arquivo em `public/assets/`: no mínimo `projetos.css` (lista de Projetos), `municipios.css`, `balancete.css` e `guia-visual.css`.
- RF-03: O layout carrega sempre `app.css` e, **somente quando a tela pede**, o arquivo da área (as demais telas não carregam arquivos de áreas que não usam).
- RF-04: Uma regra só vai para um arquivo de área se suas classes forem usadas **apenas** pelos templates daquela área; classes usadas por mais de uma área ficam na base.
- RF-05: Regras responsivas (`@media`) acompanham o arquivo da regra a que se referem.
- RF-06: Nenhuma tela existente muda de aparência (mesmas regras, mesma ordem de cascata efetiva).
- RF-07: As 5 classes sem uso hoje (`conteudo--largo`, `form-larga`, `item-corpo`, `item-titulo`, `lista-cartoes`) são removidas, após conferência de que nenhum template nem script as usa.

## 5. Requisitos não funcionais

- Desempenho: sistema local; uma requisição a mais por tela (arquivo da área) é desprezível.
- Manutenção: cada arquivo mantém o cabeçalho com sua finalidade e as seções numeradas.
- Compatibilidade: Linux e Windows; sem dependências novas, sem build (CSS escrito à mão, como hoje).
- Segurança: nenhum recurso externo (teste existente de recursos externos continua valendo para todos os arquivos).

## 6. Regras de negócio

- RN-01: **Limites de tamanho** (propostos): base **40 KB** (o limite atual; a base fica ~31 KB após a divisão, folga de ~9 KB) e **16 KB por arquivo de área**. Os limites ficam em teste, como alerta.
- RN-02: Os tokens (variáveis `--x`) existem **só na base**; arquivos de área apenas os usam. O teste de variável sem definição passa a valer para o conjunto dos arquivos.
- RN-03: O teste de classes usadas nos templates considera a **união** de todos os arquivos de CSS.
- RN-04: O teste de contraste continua lendo os tokens da base.

## 7. Fluxos de utilização

### Fluxo principal

1. O usuário abre qualquer tela; o navegador carrega `app.css` e, se houver, o CSS da área.
2. A tela aparece exatamente como antes.

### Fluxos alternativos

1. Tela sem arquivo de área (ex.: conciliação, físico-financeiro, relação de bens, erros): carrega só `app.css`.
2. Arquivo da área indisponível (falha de rede/arquivo): a tela continua legível com a base (as regras da área são refinamentos).

## 8. Exceções e erros

- EX-01: Se uma classe for movida para o arquivo errado (usada por outra área que não o carrega), a tela dessa outra área perderia estilo. Mitigação: teste que, para cada template, confere que toda classe usada está definida na base ou no arquivo carregado por aquela tela.

## 9. Critérios de aceitação

- [x] CA-01: `app.css` contém só regras compartilhadas; existem `projetos.css`, `municipios.css`, `balancete.css` e `guia-visual.css`.
- [x] CA-02: Cada tela carrega `app.css` + apenas o(s) arquivo(s) da própria área (verificado por teste no HTML).
- [x] CA-03: Para cada tela, toda classe usada nos templates está definida na base ou no arquivo que a tela carrega (novo teste, por tela).
- [x] CA-04: Nenhuma regra duplicada entre arquivos; nenhuma variável sem definição.
- [x] CA-05: Comparação visual antes/depois (capturas das telas principais no navegador, em tela larga e estreita) sem diferença.
- [x] CA-06: Limites de tamanho por arquivo conforme RN-01, com teste; suíte completa passando.
- [x] CA-07: As 5 classes sem uso foram removidas.

## 10. Restrições técnicas

- Sem ferramenta de build e sem dependências novas (decisão do projeto).
- O arquivo da área é escolhido pelo servidor ao montar a tela (mecanismo a definir no plano).
- Os testes de design (`DesignSystemTest`) precisam ser adaptados para ler vários arquivos; o contrato dos testes (tamanho, classes, variáveis, contraste, recursos externos) é preservado.
- O CSS de impressão/PDF (Dompdf) não é afetado: os PDFs têm CSS próprio em `templates/pdf/`.

## 11. Fora do escopo

- **Qualquer mudança visual**, inclusive o redesenho da tela do Projeto (spec própria, depois desta).
- Criar arquivos de área para conciliação, físico-financeiro, relação de bens e junção agora (nascem quando essas telas forem redesenhadas e tiverem CSS próprio).
- Minificação, concatenação ou pipeline de build.
- Mudar tokens, cores, tipografia ou componentes.

## 12. Decisões pendentes

Aprovadas em 2026-11-13 (com as recomendações):

- [x] DEC-01: Limites: base 40 KB e 16 KB por área (RN-01).
- [x] DEC-02: As seções 8 (Tela do Projeto) e 9 (Demais telas) ficam na **base** por serem compartilhadas; ao redesenhar a tela do Projeto, o que for só dela migra para `projetos.css`.
- [x] DEC-03: Remover as 5 classes sem uso junto com a divisão (RF-07).

## 13. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial (descoberta: dividir em base + um arquivo por área; tela do Projeto será a primeira a ser redesenhada depois) | 2026-11-13 |
| 1.1 | Aprovada com as recomendações; implementada e validada (CA-05 por estilo computado: 42/42 capturas idênticas) | 2026-11-13 |
