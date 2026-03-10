# GitHub Commit And PR Writer

## Purpose
Use this skill when the user needs high-quality GitHub commit text, merge text, or PR text based on code changes.

This skill is responsible for:
- commit summary
- commit description
- PR title
- PR description
- changed files list
- unverified items list

## When To Use
Use this skill when the user asks for any of the following:
- create a commit message
- create commit title and description
- create merge title and description
- prepare PR title and PR description
- summarize changes for GitHub

## Core Rules
1. Inspect the actual changes first when possible: `git diff`, `git status --short`, changed files, and relevant source files.
2. Do not invent behavior, testing, or files. If something was not verified, state that explicitly.
3. Keep commit summary in English and to one line.
4. Keep commit description in English as a short flat list of changes.
5. Keep PR title in Russian and to one line.
6. Keep PR description in Russian and strictly split it into `Motivation`, `Description`, and `Testing`.
7. If the change is a merge, describe the resulting effect of the merge, not just the branch mechanics, unless the user explicitly wants merge mechanics.
8. Prefer concrete wording over generic wording such as "minor fixes" or "improvements".
9. If the scope is mixed, group changes by user-facing effect or technical purpose.
10. Preserve uncertainty explicitly in `Unverified`.

## Workflow
1. Gather context from repository changes and the user request.
2. Determine the main intent of the change:
- feature
- fix
- refactor
- docs
- test
- build or tooling
- merge or sync
3. Write a precise English commit summary.
4. Write a short English commit description as a flat list.
5. Write a Russian PR title.
6. Write a Russian PR description with exactly these sections:
- `Motivation`
- `Description`
- `Testing`
7. List changed files.
8. List unverified checks or explicitly state that nothing was verified.

## Output Contract
The response must always end with this mandatory section and must not skip it:

## Commit summary
(1 line, EN)

## Commit description
(short list of changes, EN)

## PR title
(1 line, RU)

## PR description
(RU, strictly with Motivation / Description / Testing sections)

## Changed files
(list of files)

## Unverified
(what was not run or not checked)

## Writing Guidance
- Use strong verbs: `add`, `fix`, `remove`, `rename`, `align`, `simplify`, `prevent`.
- Avoid vague adjectives: `better`, `nice`, `small`, `minor`.
- Mention impacted areas directly: API, UI, validation, auth, build, docs, tests.
- For PR title in Russian, optimize for reviewer clarity, not marketing tone.
- In `Testing`, mention only checks that were actually run. If none were run, say so directly.
- In `Changed files`, prefer repository-relative paths.

## Example Skeleton
## Commit summary
Refine order validation and align API error handling

## Commit description
- tighten order payload validation rules
- normalize API error responses for invalid requests
- update related tests and request fixtures

## PR title
Уточнить валидацию заказов и выровнять обработку ошибок API

## PR description
### Motivation
Нужно уменьшить количество неконсистентных ошибок при невалидных запросах и сделать поведение API предсказуемым для клиентов.

### Description
- Уточнены правила валидации входных данных заказа.
- Унифицирован формат ошибок для невалидных запросов.
- Обновлены связанные тесты и фикстуры.

### Testing
- Запущены целевые тесты для валидации заказов.

## Changed files
- `src/orders/validator.ts`
- `src/api/error-handler.ts`
- `tests/orders/validator.test.ts`

## Unverified
- Full test suite was not run.
