# SPEC — ZIP da prestação de contas — Etapa 10

**Versão:** 1.2
**Status:** Implementada (2026-11-12) — aguardando o usuário abrir o ZIP no Windows
**Data:** 2026-11-12
**Relacionada a:** `specs/prestacao-de-contas.md` (RF-20, RF-21, EX-05, CA-09, DEC-06) e
`plans/prestacao-de-contas-plan.md` (Etapa 10).

---

## 1. Contexto

Hoje cada documento pode ser baixado individualmente (RF-20), com nome numerado do catálogo
(`01-balancete.pdf`, `10a-art-de-execucao.pdf`…). Falta o RF-21: **baixar de uma vez** todos os PDFs do projeto
num ZIP, que é a forma de entregar a prestação de contas.

## 2. Objetivo

Um botão na tela do Projeto que baixa um ZIP com todos os PDFs existentes, numerados na ordem da prestação, avisando
antes quando algum documento estiver desatualizado ou pendente de geração.

## 3. Usuários envolvidos

Um único usuário.

## 4. Decisões do usuário (2026-11-12)

| Tema | Decisão |
|---|---|
| DEC-06: nomes dentro do ZIP | **Iguais ao download individual** (nome do catálogo: `01-balancete.pdf`…) |
| Documentos "Desatualizado" | **Incluídos, com aviso antes** de baixar (nunca impede a entrega) |

## 5. Requisitos funcionais

- **RF-Z01 Botão "Baixar prestação (ZIP)"** no cabeçalho da tela do Projeto, ao lado de "Editar dados".
- **RF-Z02 Conteúdo:** todos os documentos do projeto que **têm PDF** (enviado, convertido ou gerado), cada um com o
  nome do catálogo, na raiz do ZIP. Documentos sem PDF ficam de fora, sem erro (nenhum documento é obrigatório).
- **RF-Z03 Nome do ZIP:** `prestacao-de-contas-<município>-projeto-<N>.zip`, sem acentos nem espaços (por exemplo,
  `prestacao-de-contas-caxambu-do-sul-projeto-1.zip`). Ver DEC-Z1.
- **RF-Z04 Aviso de pendências:** se houver documentos **desatualizados** ou **não gerados**, a tela mostra junto do
  botão um resumo (por exemplo, "2 desatualizados (01, 15) · 1 não gerado (03)"). Ao clicar, aparece uma confirmação
  com a mesma informação ("Baixar mesmo assim?"); se você cancelar, nada é baixado. Ver DEC-Z2.
  - *Desatualizado:* o PDF existe, mas os dados mudaram depois de gerar. **Entra no ZIP** como está.
  - *Não gerado:* há conteúdo (arquivos da junção ou linhas do formulário), mas o PDF nunca foi gerado. **Não há o que
    incluir**; o aviso só evita que a falta passe despercebida.
- **RF-Z05 Sem nenhum PDF (EX-05):** o botão aparece desabilitado com a explicação "Nenhum documento com PDF ainda". Se
  a rota for acessada mesmo assim, volta para o projeto com a mensagem "Não há documentos para baixar".

## 6. Requisitos não funcionais

- Sem dependências novas (extensão `zip` do PHP, já presente).
- O ZIP é montado na hora, num arquivo temporário, e **apagado depois do envio**. Nada fica acumulado em `storage/`.
- Os PDFs entram **como estão** (sem recompressão), então o tamanho do ZIP fica próximo da soma dos PDFs.
- Só entram arquivos registrados no banco para aquele projeto; nunca um caminho vindo da requisição (regra já usada
  no download individual).
- A confirmação usa o `confirm()` do navegador, como "Excluir" e "Remover" já fazem. Nenhum arquivo de JavaScript novo.

## 7. Regras de negócio

- RN-Z01 Ordem e nomes dos arquivos = ordem e nomes do catálogo (DEC-06).
- RN-Z02 Desatualizados entram no ZIP; o sistema só avisa.
- RN-Z03 Um documento cujo arquivo sumiu do disco é ignorado no ZIP e citado no aviso (ver EX-Z02).

## 8. Fluxos de utilização

**Principal:** Projeto → "Baixar prestação (ZIP)" → (se houver pendências: confirmação) → o navegador baixa o ZIP.

**Alternativo:** cancelar na confirmação, regenerar os documentos desatualizados e baixar de novo.

## 9. Exceções e erros

- EX-Z01 (= EX-05) Nenhum documento com PDF → botão desabilitado; a rota direta volta ao projeto com mensagem.
- EX-Z02 Arquivo registrado, mas ausente do disco (por exemplo, apagado à mão) → não entra; o ZIP é gerado com os
  demais e a mensagem seguinte avisa ("01 não foi incluído: arquivo não encontrado").
- EX-Z03 Falha ao criar o ZIP (disco cheio, permissão) → volta ao projeto com mensagem de erro; nada é baixado.
- EX-Z04 Projeto inexistente → 404.

## 10. Critérios de aceitação

- [x] CA-Z01 O ZIP contém exatamente os documentos com PDF, com os nomes do catálogo, e cada arquivo é idêntico ao
      original (mesmo conteúdo).
- [x] CA-Z02 O nome do ZIP segue o RF-Z03.
- [x] CA-Z03 Com desatualizados ou não gerados, a tela mostra o resumo e o botão pede confirmação; sem pendências, não
      pede.
- [x] CA-Z04 Sem nenhum PDF: botão desabilitado e a rota direta volta com a mensagem.
- [x] CA-Z05 O arquivo temporário é apagado depois do envio.
- [x] CA-Z06 Nenhuma falha nova nos testes.
- [ ] CA-Z07 Conferência do usuário: baixar e abrir o ZIP no Windows.

## 11. Restrições técnicas

- A tela do Projeto tem testes de HTML (`TelaProjetoTest`): no máximo 1 botão destacado por linha **da lista** (o
  botão do ZIP fica no cabeçalho, fora da lista) e a zona de perigo com "Excluir projeto".
- O CSS está em 39,2 KB, com limite de 40 KB: usar classes existentes (`btn`, `dica`, `tag aviso-tag`).

## 12. Fora do escopo

- Gerar automaticamente os PDFs desatualizados ou não gerados antes de montar o ZIP.
- Subpastas no ZIP, índice, capa ou um PDF único juntando tudo.
- Envio por e-mail ou para sistemas externos.

## 13. Decisões pendentes

- [x] DEC-Z1 **Aprovada.** **Nome do ZIP com o município** (RF-Z03). *Recomendo aprovar:* fica fácil identificar o arquivo na pasta
      de downloads quando você trabalha com vários municípios. *Padrão: aprovar.*
- [x] DEC-Z2 **Aprovada.** **Avisar também os "não gerados"** (RF-Z04), além dos desatualizados. *Recomendo aprovar:* é o caso mais
      perigoso, porque o documento simplesmente não vai no ZIP. *Padrão: aprovar.*

## 14. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação, com DEC-06 decidida (nomes do catálogo) e a regra dos desatualizados (incluir e avisar) | 2026-11-12 |
| 1.1 | Aprovada pelo usuário com as recomendações (DEC-Z1 e DEC-Z2) | 2026-11-12 |
| 1.2 | **Implementada.** CA-Z01 a Z06 verificados (314 testes, nenhuma falha nova, 8 testes novos). ZIP real no servidor de demonstração: nome correto, conteúdo idêntico (hash) aos downloads individuais, arquivos guardados sem recompressão, temporário apagado, EX-Z02 exercitado com registros sem arquivo. Observação: o resumo "N documento(s) no ZIP" conta os registros e não confere o disco; um arquivo apagado à mão só é avisado depois do download (EX-Z02) | 2026-11-12 |
