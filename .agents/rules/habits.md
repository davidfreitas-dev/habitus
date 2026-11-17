# Project: Habits — Developer Guidelines

This document consolidates the global guidelines for the Habits ecosystem:
- **Habits REST API** — PHP 8.4 + Slim Framework 4.x (backend, managed via the `senior-backend-architect` skill)
- **Habits Mobile App** — Ionic 8 + Vue 3 + TypeScript + Capacitor 8 (frontend, managed via the `senior-mobile-architect` skill)

---

## Table of Contents

1. [Critical Rules (All Projects)](#1-critical-rules-all-projects-)
2. [REST API (Backend Delegation)](#2-rest-api-backend-delegation)
3. [Mobile App (Frontend Delegation)](#3-mobile-app-frontend-delegation)
4. [Quick Reference](#4-quick-reference)

---

## 1. Critical Rules (All Projects) ⚠️

These rules apply to **both projects** and must **NEVER** be violated under any circumstances:

### Rule #1: No Git Operations
**NEVER** use shell commands like `git add` or `git commit`. All Git operations must be done manually by the user. The assistant should only create, modify, or delete files as requested.

### Rule #2: No Hardcoded Credentials
- Never hardcode credentials, API keys, secrets, or Base URLs.
- Always use environment variables and `.env` files for sensitive data (`.env` must be in `.gitignore`).
- Always ensure newly created sensitive files (`.env`, private keys, logs, temp files) are immediately added to `.gitignore`.

---

## 2. REST API (Backend Delegation)

All architecture rules, domain knowledge, implementation flows, Redis caching decorator guidelines, security rules, and Docker/PHPUnit/Composer commands for the **Habits REST API (`api/`)** are maintained in the **`senior-backend-architect`** skill (`.agents/skills/senior-backend-architect/SKILL.md`).

> **Mandatory Rule**: Whenever a task involves implementing, reviewing, debugging, testing, or optimizing any code in `api/`, **always activate and follow the `senior-backend-architect` skill**. Never run PHP or Composer commands directly on the host machine; always use `docker compose exec api <command>` from the workspace root.

---

## 3. Mobile App (Frontend Delegation)

All architecture rules, Vue 3 Composition API guidelines with `<script setup lang="ts">`, TypeScript standards, Capacitor 8 native plugin integrations (Local Notifications, Haptics, Network, StatusBar, Keyboard), Deep Links, assets, and Docker/Vite/Capacitor commands for the **Habitus Mobile App (`mobile/`)** are maintained in the **`senior-mobile-architect`** skill (`.agents/skills/senior-mobile-architect/SKILL.md`).

> **Mandatory Rule**: Whenever a task involves implementing, reviewing, debugging, styling, or compiling code in `mobile/`:
> 1. **Always activate and follow the `senior-mobile-architect` skill**.
> 2. **Docker vs. Host Division**:
>    - Run package management, Vite builds, Lint and Vitest inside Docker: `docker compose exec mobile <command>`.
>    - Run native synchronization and IDE launch commands on the host macOS: `npx cap sync`, `npx cap open ios`, `npx cap open android`.
> 3. **TypeScript Only**: Use strict types, Vue 3 `<script setup lang="ts">`, and maintain types in `src/types/`.

---

## 4. Quick Reference

### Common Rules (Both Projects)
✅ Never use Git commands (`git add`, `git commit`)  
✅ Never hardcode credentials, API keys, or secrets  
✅ Always use environment variables for sensitive data  
✅ Always add sensitive files to `.gitignore`  

### REST API (`api/`)
✅ Activate the `senior-backend-architect` skill  
✅ Always run PHP/Composer/PHPUnit via Docker (`docker compose exec api ...`)  

### Mobile App (`mobile/`)
✅ Activate the `senior-mobile-architect` skill  
✅ Always use `<script setup lang="ts">` and layered architecture (View → Store → Service → API)  
✅ Run web tasks via Docker (`docker compose exec mobile ...`) and native syncing on host (`npx cap sync`)
