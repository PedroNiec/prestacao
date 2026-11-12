
# SPEC — Sistema de Prestação de Contas

**Versão:** 1.0
**Status:** Aprovada (2026-11-12)
**Data:** 2026-11-12

---

## 1. Contexto

A empresa realiza serviços para prefeituras que são financiados por convênios/repasses
de concedentes (ex.: Secretaria de Estado da Educação). Ao final, é necessário entregar
uma **prestação de contas** composta por um conjunto de PDFs, cada um agrupando um tipo
de informação (notas fiscais, pagamentos, balancete etc.).

Hoje o processo é manual:

- Balancete, conciliação bancária, relação de bens e relatório físico-financeiro são
  preenchidos em uma planilha Excel (`planilha/Balancete Prestação de Contas.xlsx`) e
  impressos em PDF. As abas compartilham dados por fórmulas.
- O acervo fotográfico é montado em PowerPoint.
- A placa de identificação é uma imagem PNG.
- Os demais documentos são PDFs escaneados/prontos que são juntados ou apenas anexados.

O projeto ainda não possui código. Tecnologia definida: **PHP**.

## 2. Objetivo

Substituir o fluxo manual (Excel + PowerPoint + junção manual de PDFs) por um sistema
único, no qual o usuário:

1. cadastra o projeto uma vez;
2. preenche os relatórios financeiros no próprio sistema (sem planilha);
3. envia/junta os PDFs de cada documento;
4. baixa a prestação de contas pronta (PDFs individuais ou ZIP).

**Problema resolvido:** retrabalho de digitação dos mesmos dados em várias abas, erros de
fórmula (a planilha atual possui, por exemplo, um `#REF!` em "Rel Pagamentos") e
montagem manual dos PDFs.

## 3. Usuários envolvidos

- **Um único usuário** (a pessoa que prepara a prestação de contas).
- Sem perfis de acesso e sem acesso da prefeitura ao sistema.

## 4. Requisitos funcionais

### 4.1 Cadastros

- RF-01: Cadastrar, editar e excluir **municípios** (executor), contendo: nome/UF,
  prefeito (nome) e contador (nome e CRC) como signatários padrão.
- RF-02: Cadastrar, editar e excluir **projetos**, vinculados a um município, com:
  concedente, nº do processo, nº PP, nº NE, nº NL, objeto, local e data de assinatura
  e signatários (pré-preenchidos do município, **editáveis** por projeto).
- RF-03: Listar projetos e abrir um projeto para ver todos os documentos da prestação.

### 4.2 Documentos da prestação

- RF-04: Todo projeto exibe **todos os 25 documentos** da lista (seção 6). **Nenhum
  documento é obrigatório**, e não há distinção por tipo de projeto (obra, serviço, bem).
- RF-05: Cada documento exibe seu estado (vazio / preenchido) e permite baixar o PDF
  correspondente quando existir.

### 4.3 Junção de PDFs (documentos 2, 3, 4, 5, 6, 7, 13, 14)

- RF-06: Enviar vários PDFs para um documento.
- RF-07: Reordenar os arquivos (arrastar e soltar) e remover arquivos.
- RF-08: Gerar um PDF único com os arquivos na ordem definida; o PDF pode ser gerado
  novamente após adicionar, remover ou reordenar arquivos.

### 4.4 Upload de PDF pronto (documentos 10a, 10b, 11, 12, 17, 18, 19, 20, 21, 23, 24)

- RF-09: Enviar um arquivo para o documento, substituir e remover.
- RF-10: O documento 17 (CNO) foi descrito como "upload de arquivo"; o formato aceito
  está em decisão pendente (DEC-03).

### 4.5 Conversão para PDF (documentos 8 e 16)

- RF-11: Documento 8 (Acervo fotográfico): enviar arquivo PowerPoint e o sistema o
  converte para PDF.
- RF-12: Documento 16 (Identificação oficial / placa): enviar imagem PNG e o sistema
  a converte para PDF.

### 4.6 Relatórios preenchidos no sistema (documentos 1, 9, 15, 22)

- RF-13: **Balancete (doc. 1)** — equivalente à aba "Rel Pagamentos". Cada lançamento
  tem: boletim de medição/parcela, descrição, CNPJ, nota fiscal, nota de empenho, documento
  (DOC), data, **origem do recurso** (Convênio, Rendimentos ou Contrapartida), fonte
  (1 – Concedente, 2 – Convenente) e um valor que é **Receita** (entrou na conta) **ou**
  **Despesa** (saiu da conta). Totais gerais, total por origem (convênio, rendimentos,
  contrapartida) e resumo calculados automaticamente. Retenções (IRRF, ISS, INSS) são
  lançadas como linhas de despesa separadas, em descrição livre, sem campo de tipo.
- RF-14: **Relatório de execução físico-financeiro (doc. 22)** — equivalente à aba
  "Rel Fis x Fin". Um projeto pode ter **várias linhas (metas/etapas)**. Cada linha tem meta,
  etapa/fase, descrição, unidade; físico (programado e executado, "no período" e "até o
  período"); financeiro realizado no período (concedente, executor, outros rendimentos) e até o
  período (concedente, executor). **Todos os valores são digitados pelo usuário** (não são
  calculados a partir do balancete). O sistema soma automaticamente os totais: por linha
  (período = concedente + executor + outros rendimentos; até o período = concedente + executor)
  e do relatório. O total físico só é exibido quando todas as linhas têm a mesma unidade.
- RF-15: **Conciliação bancária (doc. 15)** — equivalente à aba "Conciliação Bancária", em A4
  retrato, com o cabeçalho padrão do projeto e só o Prefeito assinando. Seções: (1) receitas —
  1º a 3º repasse, 1ª a 3ª contrapartida e rendimentos, **calculadas a partir das receitas do
  balancete** (cada receita de Convênio é um repasse; de Contrapartida, uma contrapartida; os
  rendimentos são somados) e **editáveis**; (2) despesas por **nota fiscal, digitadas na própria
  conciliação** (bruto, IR, ISS, INSS e outros descontos; líquido = bruto − retenções), mais 5
  devoluções digitadas; (3) saldo = receitas − despesas; (4) extrato bancário (saldo da conta
  corrente e da aplicação numa data); (5) conciliação = saldo − saldo bancário. A coluna "soma"
  é acumulada. Título e cabeçalho da coluna do extrato são textos editáveis (DEC-07).
- RF-16: **Relação de bens (doc. 9)** — equivalente à aba "Rel de Bens construídos":
  relação de bens adquiridos/produzidos/construídos com descrição e valor.
- RF-17: Cada relatório gera um **PDF com visual muito próximo ao da planilha atual**
  (mesmas colunas, ordem, cabeçalho e rodapé com local, data e assinaturas).
- RF-18: Os valores dos relatórios 15 e 9 são **calculados a partir dos lançamentos**
  do balancete e dos demais dados do projeto, mas **podem ser sobrescritos
  manualmente** (o relatório 22 não é calculado: seus valores são digitados). Campos sobrescritos devem ser sinalizados visualmente e deve ser possível
  restaurar o valor calculado.
- RF-19: Os dados do cabeçalho e as assinaturas vêm do cadastro do projeto, sem redigitação.

### 4.7 Entrega

- RF-20: Baixar individualmente o PDF de cada documento.
- RF-21: Baixar um **ZIP** com todos os PDFs existentes do projeto, nomeados e numerados
  conforme a lista de documentos.

## 5. Requisitos não funcionais

- Desempenho: uso por uma única pessoa; geração de PDF em tempo aceitável
  (alguns segundos) para volumes típicos de uma prestação de contas.
- Segurança: execução **local**, sem login. Os dados e arquivos ficam na máquina do usuário.
  O sistema não deve ser exposto à internet.
- Usabilidade: interface em português do Brasil; valores monetários em R$ no formato
  brasileiro; datas por extenso nos relatórios (ex.: "05 de março de 2026").
- Disponibilidade: não aplicável (uso local).
- Compatibilidade: navegador moderno; PHP.
- Integridade: cálculos monetários sem erros de arredondamento de ponto flutuante.

## 6. Documentos da prestação

| Nº | Documento | Comportamento |
|---|---|---|
| 1 | Balancete | Formulário no sistema |
| 2 | Notas de empenho | Junção de PDFs |
| 3 | Notas fiscais | Junção de PDFs |
| 4 | Comprovantes de pagamentos | Junção de PDFs |
| 5 | Ordens de serviços | Junção de PDFs |
| 6 | Relatório de medições | Junção de PDFs |
| 7 | Laudo técnico de engenharia | Junção de PDFs |
| 8 | Acervo fotográfico | Converte PowerPoint em PDF |
| 9 | Relação de bens adquiridos/produzidos/construídos | Formulário no sistema |
| 10a | ART de execução | Upload de PDF |
| 10b | ART de fiscalização | Upload de PDF |
| 11 | Aporte de contrapartida | Upload de PDF |
| 12 | Execução de contrapartida | Upload de PDF |
| 13 | Extratos da conta corrente | Junção de PDFs |
| 14 | Extratos da conta aplicação | Junção de PDFs |
| 15 | Conciliação bancária | Formulário no sistema |
| 16 | Identificação oficial (placa da obra/serviço/bens) | Converte PNG em PDF |
| 17 | Cadastro Nacional de Obras (CNO) | Upload de arquivo |
| 18 | Parecer do Controle Interno | Upload de PDF |
| 19 | Certidão negativa de débitos (CND) | Upload de PDF |
| 20 | Comprovante de devolução de saldos | Upload de PDF |
| 21 | Comprovante de recolhimento de rendimentos | Upload de PDF |
| 22 | Relatório de execução físico-financeiro | Formulário no sistema |
| 23 | Termo de aceitação definitiva do objeto | Upload de PDF |
| 24 | Termo de encerramento da execução do objeto | Upload de PDF |

## 7. Regras de negócio

- RN-01: Todo projeto pertence a exatamente um município.
- RN-02: Nenhum documento é obrigatório; a prestação pode ser baixada com qualquer
  combinação de documentos preenchidos.
- RN-03: Na junção de PDFs, a ordem final é a definida pelo usuário.
- RN-04: Balancete — total geral = soma das linhas de receita e despesa. As retenções
  (ex.: IRRF) são lançadas como linhas de despesa vinculadas à mesma nota fiscal.
- RN-05: Conciliação — para cada nota fiscal: valor líquido = valor bruto − IR − ISS − INSS
  − outros descontos. Total de receitas = soma de repasses, contrapartidas e rendimentos.
  **Total de despesas = soma do valor bruto de todas as notas fiscais** (as devoluções ficam
  **fora** do total, por decisão do usuário; a planilha antiga somava só a 1ª nota, o que era um
  erro). Consequência: se houve devolução de saldo, a conciliação pode não fechar em zero; o
  sistema avisa na tela.
- RN-06: Físico-financeiro — valores digitados pelo usuário; "no período" e "até o período" são
  informados separadamente. Apenas os totais são somas automáticas (ver RF-14).
- RN-07: Valores calculados podem ser sobrescritos; o sistema mantém o valor calculado
  para permitir restauração.
- RN-08: Fonte do recurso: 1 – Concedente, 2 – Convenente. A **origem** do lançamento define a
  fonte sugerida (Convênio e Rendimentos = 1; Contrapartida = 2), que o usuário pode alterar.
- RN-09: Balancete tem **uma única coluna de Receita** (o que entrou na conta) e uma de Despesa
  (o que saiu). A coluna "Receita" duplicada da planilha antiga não existe no sistema.

> As fórmulas exatas devem ser confirmadas no planejamento técnico a partir da planilha
> `planilha/Balancete Prestação de Contas.xlsx`.

## 8. Fluxos de utilização

### Fluxo principal

1. O usuário cadastra o município (uma única vez).
2. Cria um projeto escolhendo o município; os dados do cabeçalho e os signatários vêm
   preenchidos e podem ser editados.
3. Lança as receitas e despesas no Balancete.
4. Revisa os relatórios calculados (físico-financeiro, conciliação, relação de bens) e
   ajusta o que for necessário.
5. Envia e reordena os PDFs dos documentos de junção; envia os uploads e as conversões.
6. Baixa os PDFs individualmente ou o ZIP completo.

### Fluxos alternativos

1. Substituir ou remover um arquivo já enviado e gerar o PDF novamente.
2. Baixar apenas um documento específico sem concluir os demais.
3. Restaurar o valor calculado de um campo sobrescrito.

## 9. Exceções e erros

- EX-01: Arquivo enviado não é PDF válido (ou formato esperado) — recusar e informar.
- EX-02: PDF protegido por senha ou corrompido na junção — informar qual arquivo falhou
  e não gerar o PDF.
- EX-03: Falha na conversão de PowerPoint ou PNG — informar o erro e manter o estado anterior.
- EX-04: Excluir município com projetos vinculados — impedir ou pedir confirmação.
- EX-05: Baixar ZIP sem nenhum documento — informar que não há documentos.
- EX-06: Arquivo acima do tamanho máximo configurado — recusar e informar.

## 10. Critérios de aceitação

- [ ] CA-01: Cadastrar um município e um projeto, com o cabeçalho e as assinaturas
      aparecendo automaticamente nos relatórios.
- [ ] CA-02: Todos os 25 documentos aparecem em qualquer projeto, sem nenhum obrigatório.
- [ ] CA-03: Juntar vários PDFs com ordem definida pelo usuário e baixar o resultado.
- [ ] CA-04: Fazer upload, substituir e remover um PDF pronto.
- [ ] CA-05: Converter um PowerPoint e um PNG em PDF.
- [ ] CA-06: Lançar o balancete do exemplo (`planilha/…xlsx`) e obter os mesmos totais da
      planilha em todos os relatórios.
- [ ] CA-07: Sobrescrever um valor calculado, vê-lo sinalizado e restaurá-lo.
- [ ] CA-08: PDFs dos 4 relatórios com visual muito próximo ao da planilha atual.
- [ ] CA-09: Baixar cada PDF individual e o ZIP com nomes numerados.

## 11. Restrições técnicas

- Linguagem: PHP. Framework e bibliotecas serão avaliados no planejamento técnico
  (nenhuma dependência será adicionada sem autorização).
- Execução local, em navegador, sem login.
- Conversão de PowerPoint para PDF normalmente depende de software externo no computador
  (ex.: LibreOffice); a decisão será tratada no planejamento.
- O projeto não possui código; a arquitetura será definida do zero.

## 12. Fora do escopo

- Login, perfis de acesso e acesso de prefeituras ao sistema.
- Execução em servidor/internet.
- Leitura de dados de PDFs escaneados (OCR); PDFs são apenas anexados.
- Importação de planilhas Excel.
- Checklist ou validação de documentos exigidos por tipo de projeto.
- Aba "Dem Rec x Desp" (Relatório Demonstrativo da Execução da Receita e Despesa) —
  ignorada por enquanto.
- Geração do acervo fotográfico a partir de fotos individuais (somente conversão do PowerPoint).

## 13. Decisões pendentes

- [ ] DEC-01: Aba "Dem Rec x Desp" — confirmar futuramente se é entregue e se deve entrar.
- [ ] DEC-02: Detalhar os campos de cada relatório. *Balancete: decidido (ver RF-13, RN-08 e RN-09). Físico-financeiro: decidido (ver RF-14 e RN-06). Conciliação: decidido (ver RF-15 e RN-05). Pendente: itens da relação de bens.*
- [ ] DEC-03: Formato aceito para o CNO (doc. 17): somente PDF ou outros. *Provisório na implementação: somente PDF (configurável em `DocumentCatalog::extensoesUpload`).*
- [ ] DEC-04: Aceitar imagens (JPG/PNG) na junção de PDFs, além de PDFs. *Provisório: somente PDF.*
- [ ] DEC-05: Tamanho máximo de arquivo e política de backup dos dados. *Provisório: 50 MB por arquivo; backup = copiar a pasta `storage/` (ver README).*
- [ ] DEC-06: Padrão de nomes dos arquivos no ZIP.
- [ ] DEC-08: Formatos de PowerPoint aceitos no acervo fotográfico (doc. 8). *Provisório: somente .pptx (não .ppt antigo).*
- [x] DEC-07: Título variável da conciliação — campo de texto livre (decidido na Etapa 8).

## 14. Histórico de alterações

| Versão | Alteração | Data |
|---|---|---|
| 1.0 | Criação inicial | 2026-11-12 |
| 1.1 | Especificação aprovada pelo usuário (sem alterações de conteúdo) | 2026-11-12 |
| 1.2 | Registradas premissas provisórias da implementação (DEC-03: CNO somente PDF; DEC-05: limite de 50 MB) | 2026-11-12 |
| 1.3 | Registrada premissa provisória DEC-04 (junção aceita somente PDF); PDF final marcado como "desatualizado" quando a lista de arquivos muda (detalhe de RF-08) | 2026-11-12 |
| 1.4 | Conversões (RF-11/RF-12): doc. 8 aceita somente .pptx e doc. 16 somente .png (DEC-08 provisória); só o PDF convertido é guardado, o original é descartado | 2026-11-12 |
| 1.5 | DEC-02 (balancete) decidida: origem do recurso define a fonte; uma única coluna de Receita; retenções em linhas separadas sem tipo | 2026-11-12 |
| 1.6 | Balancete (RF-13/RF-17) implementado. Acrescentada ao PDF a linha "SALDO (RECEITA - DESPESA)", ausente na planilha mas prevista no seu título; lançamentos podem ser reordenados | 2026-11-12 |
| 1.7 | Físico-financeiro (RF-14, RN-06, RF-18) implementado conforme decisão do usuário: várias metas/etapas, financeiro sempre digitado, "no período" e "até o período" separados; deixa de ser derivado do balancete | 2026-11-12 |
| 1.8 | Conciliação bancária (RF-15, RN-05, DEC-07) implementada: receitas do balancete editáveis (com sobrescrita/restauração), notas fiscais digitadas, total de despesas = soma do bruto das notas (corrige defeito da planilha), devoluções fora do total, cabeçalho padrão, só o Prefeito assina | 2026-11-12 |
