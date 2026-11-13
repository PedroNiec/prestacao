# SPEC — Relação de bens (documento 09) — Etapa 9

**Versão:** 1.2
**Status:** Implementada (2026-11-12) — aguardando conferência do PDF pelo usuário
**Data:** 2026-11-12
**Relacionada a:** `specs/prestacao-de-contas.md` (RF-16, RF-17, RF-18, RF-19, DEC-02) e
`plans/prestacao-de-contas-plan.md` (Etapa 9). Referência visual: aba **"Rel de Bens construidos"** da planilha
`planilha/Balancete Prestação de Contas.xlsx` (lida em 2026-11-12).

---

## 1. Contexto

O documento 09 ("Relação de bens adquiridos/produzidos/construídos") é o último dos 4 formulários do sistema que
ainda não existe. Hoje a linha 09 da tela do Projeto mostra "Disponível em etapa futura".

**Como é na planilha (aba "Rel de Bens construidos"):**

| Parte | Conteúdo |
|---|---|
| Página | Retrato |
| Cabeçalho | Executor, Concedente, Processo nº, Nº PP / Nº NE / Nº NL, Objeto (o mesmo dos outros relatórios) |
| Título | "RELAÇÃO DE BENS ADQUIRIDOS" (fixo) |
| Linhas | **Item** (1) · **Descrição** · **Valor**. Um único bem, sem linha de cabeçalho de colunas e sem total |
| Rodapé | Local e data por extenso; assinaturas do **Contador** (nome e CRC) e do **Prefeito** |
| Valor | Fórmula vinda do físico-financeiro (realizado até o período − outros rendimentos) |

## 2. Objetivo

Permitir cadastrar os bens do projeto e gerar o PDF do documento 09 com visual muito próximo ao da planilha.

## 3. Usuários envolvidos

Um único usuário.

## 4. Decisões do usuário (2026-11-12)

| Tema | Decisão |
|---|---|
| Quantidade de bens | **Várias linhas** (uma lista de bens) |
| Valor | **Tudo digitado**, sem cálculo e sem valor sugerido. Isto **altera o RF-18** da spec principal para o doc. 09 |
| Título do PDF | **Texto livre editável** por projeto; padrão "RELAÇÃO DE BENS ADQUIRIDOS" |
| Total | **Sempre** mostrar a linha de total no PDF |

## 5. Requisitos funcionais

- **RF-B01 Lista de bens por projeto.** Cada bem tem: **Descrição** (obrigatória, até 500 caracteres) e **Valor**
  (obrigatório, em reais, maior que zero). O **número do item** (1, 2, 3…) é a posição na lista.
- **RF-B02 Ações:** acrescentar, editar, excluir (com confirmação) e reordenar (↑ ↓), como no físico-financeiro.
- **RF-B03 Sugestão de descrição:** ao cadastrar o **primeiro** bem, o campo Descrição já vem preenchido com o
  **objeto do projeto** (editável), como na planilha e como o físico-financeiro já faz. O valor vem vazio.
- **RF-B04 Título do PDF:** campo de texto do projeto, com o padrão "RELAÇÃO DE BENS ADQUIRIDOS" (até 200 caracteres),
  editado na própria tela da relação de bens.
- **RF-B05 Tela da relação de bens** (`/projetos/{id}/relacao-de-bens`), no padrão das telas de documento
  (balancete e físico-financeiro): trilha, cabeçalho com "Novo bem", cartão do PDF, tabela Item · Descrição · Valor
  com o total, campo do título e estado vazio.
- **RF-B06 PDF** (retrato), igual à planilha:
  - cabeçalho padrão;
  - título (RF-B04);
  - uma linha por bem (Item · Descrição · Valor);
  - **linha "TOTAL"** com a soma;
  - local e data por extenso;
  - assinaturas do Contador e do Prefeito.

  Com a lista vazia, o PDF é gerado mesmo assim, com a frase "Nenhum bem cadastrado." e o total 0,00 (como os outros
  relatórios vazios).
- **RF-B07 Situação na tela do Projeto:** a linha 09 passa a funcionar como os outros formulários: etiqueta (Vazio /
  Não gerado / Gerado / Desatualizado), detalhe "N bem(ns)", botão **"Abrir relação de bens"** e "Baixar".
- **RF-B08 Desatualizado:** qualquer mudança nos bens ou no título depois de gerar o PDF marca o documento 09 como
  "Desatualizado", como nos outros relatórios.
- **RF-B09** Os textos provisórios "Disponível em etapa futura" e "O formulário da relação de bens será habilitado na
  próxima etapa" **deixam de existir**, porque o formulário passa a existir (eles existiam só para o doc. 09).

## 6. Requisitos não funcionais

- Dinheiro sempre em **centavos** (inteiros), como no resto do sistema; entrada no formato "1.234,56".
- Todo texto escapado (`e()`), CSRF em todos os POST, confirmação ao excluir.
- PDF gerado pelo Dompdf (sem Ghostscript). **Neste computador, os testes que leem o texto do PDF não rodam**
  (falta o Ghostscript), então o conteúdo também é testado pelo HTML que gera o PDF (ver plano).
- Sem dependências novas.

## 7. Regras de negócio

- RN-B01 Valor de cada bem > 0; descrição obrigatória.
- RN-B02 Total = soma dos valores dos bens.
- RN-B03 A numeração dos itens segue a ordem da lista (reordenar muda a numeração).
- RN-B04 O documento 09 continua **opcional**, como todos os outros.

## 8. Fluxos de utilização

**Principal:** Projeto → linha 09 "Abrir relação de bens" → "Novo bem" (descrição sugerida com o objeto) → digitar o
valor → Salvar → (opcional) ajustar o título → **Gerar PDF** → Ver/Baixar.

**Alternativos:** editar, excluir ou reordenar bens; regenerar depois de mudar ("Desatualizado").

## 9. Exceções e erros

- EX-B01 Valor vazio, zero, negativo ou inválido → mensagem de erro e o formulário volta com o que foi digitado.
- EX-B02 Descrição vazia → mensagem de erro.
- EX-B03 Título vazio → volta ao padrão "RELAÇÃO DE BENS ADQUIRIDOS".
- EX-B04 Bem de outro projeto (id trocado na URL) → 404.

## 10. Critérios de aceitação

- [x] CA-B01 Cadastrar, editar, excluir e reordenar bens; a numeração acompanha a ordem.
- [x] CA-B02 O primeiro bem vem com a descrição sugerida (objeto do projeto).
- [x] CA-B03 O PDF tem cabeçalho, título, itens, TOTAL, local/data e as duas assinaturas, em retrato.
- [x] CA-B04 Título editável, com o padrão quando vazio.
- [x] CA-B05 A tela do Projeto mostra a situação da linha 09 e "Abrir relação de bens"; os textos provisórios
      somem.
- [x] CA-B06 Mudar bens ou título depois de gerar marca "Desatualizado".
- [x] CA-B07 Validações (EX-B01 a EX-B04).
- [x] CA-B08 Nenhuma falha nova nos testes.
- [ ] CA-B09 Conferência do PDF pelo usuário (no computador com Ghostscript, ou abrindo o PDF baixado).

## 11. Restrições técnicas

- Reaproveitar `templates/pdf/_cabecalho.php`, `_rodape.php` e `_estilo.php` (**sem alterá-los**).
- O teste `testPdfTemplatesWereNotChangedByTheVisualRefactor` lista os templates de PDF com o hash de cada um; um
  template novo (`pdf/relacao_bens.php`) exige acrescentá-lo à lista (alteração prevista e registrada).
- Na tela do Projeto, o teste `TelaProjetoTest` verifica hoje os textos provisórios (DEC-P3); essa asserção muda para
  o comportamento novo (RF-B09), com registro.

## 12. Fora do escopo

- Valor calculado ou sugerido (decisão: tudo digitado).
- Campos além de descrição e valor (quantidade, unidade, patrimônio, nota fiscal…).
- ZIP (Etapa 10).

## 13. Alterações na spec principal (feitas na aprovação, spec v1.9)

- RF-16: detalhar "várias linhas, descrição e valor digitados; título editável; total".
- RF-18: o doc. 09 **não** é calculado (passa a valer só para o doc. 15).
- DEC-02: marcar "Relação de bens: decidido".

## 14. Decisões pendentes

- [x] DEC-B1 **Aprovada.** **Descrição sugerida no primeiro bem** (RF-B03). *Recomendo aprovar:* economiza digitação no caso
      comum (um bem igual ao objeto). *Padrão: aprovar.*
- [x] DEC-B2 **Aprovada.** **Remover os textos provisórios** da tela do Projeto (RF-B09). *Recomendo aprovar:* eles só existiam
      porque o formulário não existia. *Padrão: aprovar.*

## 15. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação: entrevista com o usuário e leitura da aba "Rel de Bens construidos" da planilha | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações (DEC-B1 e DEC-B2); spec principal atualizada (v1.9) | 2026-11-12 |
| 1.2 | **Implementada.** CA-B01 a B08 verificados (306 testes, nenhuma falha nova; 11 testes novos). PDF real gerado no servidor de demonstração: 1 página A4 retrato, texto conferido extraindo as streams do PDF com PHP (sem Ghostscript). Testes ajustados e registrados: `TelaProjetoTest` (textos provisórios removidos, DEC-B2) e `DesignSystemTest` (hash do PDF novo, DT-1 e DT-2, que corrigiram as 2 falhas de Windows) | 2026-11-12 |
