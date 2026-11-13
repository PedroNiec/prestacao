# PLAN — Redesenho visual: lista de Projetos (Fase 3.2)

**Versão:** 1.0
**Status:** Concluído (2026-11-12) — aprovado pelo usuário
**SPEC relacionada:** `specs/redesenho-lista-projetos.md` (v1.1, aprovada)
**Data:** 2026-11-12

---

## 1. Resumo da implementação

Reescrever `templates/projetos/index.php` e acrescentar a seção "12. LISTA DE PROJETOS" ao `app.css`. Nenhum
controlador, rota ou banco muda (o controlador já passa `$projetos` e `$temMunicipios`).

## 2. Análise da arquitetura atual

- `ProjetoController::index()` passa `projetos` (com `municipio_nome`, `municipio_uf`, `objeto`, `processo`, `id`) e
  `temMunicipios`.
- O ícone de pasta já existe no CSS, como `--icone` de `a[href="/projetos"]` no menu. Para reaproveitá-lo sem
  duplicar o SVG, ele vira um token em `:root` (`--icone-pasta`), usado pelo menu e pela lista.
- A técnica do "link esticado": o link do objeto recebe `::after { position: absolute; inset: 0 }` dentro do cartão
  com `position: relative`. O cartão inteiro fica clicável com **um único link**.

## 3. Arquivos envolvidos

| Arquivo | Ação | Justificativa |
|---|---|---|
| `templates/projetos/index.php` | Alterar | RF-LP01 a LP05 |
| `public/assets/app.css` | Alterar | Token `--icone-pasta` (o menu passa a usá-lo) + seção 12 |
| `tests/TelasPadraoTest.php` | Alterar 1 asserção | A classe do link do objeto muda (`item-titulo` → `projeto-link`); registrado na spec |
| `tests/ListaProjetosTest.php` | **Criar** | CA-LP01 a LP04 |
| spec, plano, `docs/HISTORICO.md` | Alterar (no fim) | Registro |

## 4. Banco de dados

**Nenhuma alteração.**

## 5. Serviços e regras de negócio

Nenhuma alteração.

## 6. Fluxo técnico

```html
<div class="barra projeto-cab"> h1 "Projetos" + p.projeto-sub "2 projetos" + a.btn "+ Novo projeto" </div>
<ul class="lista-projetos">
  <li class="cartao-projeto">
    <span class="icone-pasta" aria-hidden="true"></span>
    <div class="cartao-projeto-corpo">
      <h2 class="cartao-projeto-titulo"><a class="projeto-link" href="/projetos/1">OBJETO</a></h2>
      <span class="item-sub">Caxambu do Sul - SC · PROCESSO … · #1</span>
    </div>
    <span class="cartao-projeto-abrir" aria-hidden="true">Abrir ›</span>
  </li>
</ul>
```

- `.cartao-projeto`: grade `auto 1fr auto`, `position: relative`; hover com a mesma borda e sombra de `.cartao-mun`;
  `:focus-within` com o anel de foco no cartão.
- `.projeto-link::after`: cobre o cartão (`inset: 0`), o que deixa o cartão inteiro clicável.
- ≤ 640 px: "Abrir ›" vai para baixo do texto (grade em 2 colunas, `grid-column` ajustado).
- Estado vazio: `estado-vazio estado-vazio--pasta` (variante com o ícone de pasta).

## 7. Segurança e permissões

Objeto, município e processo escapados com `e()`; sem formulários nesta tela.

## 8. Riscos e impactos

| Risco | Impacto | Mitigação |
|---|---|---|
| Trocar o ícone do menu por um token | O ícone do menu sumir se o token estiver errado | O teste de `var()` sem definição pega isso; conferência visual. |
| Link esticado cobrir outros controles | Nenhum outro controle no cartão hoje | Se um dia houver ⋮ no cartão, ele recebe `position: relative; z-index: 1`. |

## 9. Estratégia de implementação

- [x] **Etapa 1:** CSS (token + seção 12), template e testes.
- [~] **Etapa 2:** suíte completa sem falha nova, HTML bem formado e conferência sua. *(aprovada pelo usuário)*

## 10. Testes e validações

- [ ] `ListaProjetosTest`:
  - contagem no singular e no plural;
  - cartão com objeto (link único para `/projetos/N`), município, processo e número;
  - sem processo, sem o " · " sobrando;
  - projeto sem objeto → "Projeto #N";
  - estado vazio com "Cadastrar município" e com "Novo projeto";
  - escape de `<script>`.
- [ ] Regressão: suíte completa comparada com a lista de falhas de ambiente, **conferindo o conteúdo** da falha
      de classes do `DesignSystemTest` (lição da fase 3.1).

## 11. Critérios de conclusão

CA-LP01 a LP07 verificados; documentos atualizados.

## 12. Decisões técnicas pendentes

Nenhuma.

## 13. Aprovação

- [x] Especificação aprovada (v1.1, 2026-11-12).
- [x] Planejamento aprovado (2026-11-12).
- [x] Autorização para implementação (2026-11-12).
