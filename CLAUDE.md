
# Instruções do Projeto

## 1. Comportamento geral

- Antes de modificar qualquer código, analise o contexto existente.
- Preserve funcionalidades e regras de negócio existentes.
- Evite alterações fora do escopo solicitado.
- Priorize soluções simples, organizadas e sustentáveis.
- Não adicione dependências sem autorização.
- Não presuma decisões importantes.

## 2. Metodologia de desenvolvimento

Este projeto utiliza Spec-Driven Development.

Para novas funcionalidades ou mudanças relevantes,
utilize a Skill `spec-driven`.

A Skill está localizada em:

.claude/skills/spec-driven/SKILL.md

Siga o processo definido nela:

1. Descoberta de requisitos.
2. Análise crítica.
3. Criação da especificação.
4. Aprovação da especificação.
5. Planejamento técnico.
6. Aprovação do planejamento.
7. Implementação autorizada.
8. Validação.

## 3. Documentação

As especificações devem ser armazenadas em:

specs/

Utilize como referência:

specs/SPEC_TEMPLATE.md

Os planejamentos técnicos devem ser armazenados em:

plans/

Utilize como referência:

plans/PLAN_TEMPLATE.md

## 4. Controle de alterações

- Mantenha a documentação sincronizada com o sistema.
- Não considere uma SPEC aprovada como autorização automática
  para modificar o código.
- Registre alterações relevantes nas especificações.
- Não remova decisões anteriores sem discutir com o usuário.

## 5. Comunicação

- Trabalhe em pequenas etapas.
- Explique decisões técnicas importantes.
- Apresente riscos e alternativas.
- Solicite aprovação nos checkpoints definidos.
- Não avance sem autorização quando houver uma etapa
  que dependa de aprovação do usuário.

## 6. Contexto e histórico do projeto

Antes de começar qualquer trabalho, leia `docs/HISTORICO.md`: ele registra o estado atual,
as decisões já tomadas, as pendências e o próximo passo. Ao encerrar uma sessão, atualize esse arquivo.
Para retomar o trabalho em outro computador, siga `docs/GUIA-OUTRO-COMPUTADOR.md`.
