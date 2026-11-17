# Ionic 8 + Vue 3: Guia de Componentes e Melhores Práticas

Este guia é a referência rápida e direta para criar e manter componentes de interface no **Habitus**.

---

## 1. Regra de Ouro: Ionic-First (Componentes Oficiais)

> [!CRITICAL]
> **Nunca use HTML puro (`div`, `button`, `input`) quando houver um componente equivalente no Ionic.**
> 
> Componentes do Ionic fornecem suporte nativo a gestos, ripple effect, acessibilidade (ARIA), modos `ios` / `md`, suporte a teclado virtual e safe areas.
> 
> 🔗 **Documentação Oficial dos Componentes:** [https://ionicframework.com/docs/components](https://ionicframework.com/docs/components)
> Consulte a documentação oficial para inspecionar slots (`slot="start"`, `slot="end"`), eventos e propriedades antes de implementar qualquer elemento.

---

## 2. Tabela Rápida de Decisão (HTML vs Ionic)

Antes de criar uma tag no `<template>`, verifique a tabela:

| Caso de Uso | ❌ Nunca use | ✅ Sempre use (Ionic Framework) | Documentação Oficial |
| :--- | :--- | :--- | :--- |
| **Chips / Tags de Filtro** | `<div class="chip">` | `<ion-chip>`, `<ion-label>`, `<ion-icon>` | [/components/chip](https://ionicframework.com/docs/api/chip) |
| **Botões de Ação** | `<button class="btn">` | `<ion-button>`, `<ion-fab>`, `<ion-fab-button>` | [/components/button](https://ionicframework.com/docs/api/button) |
| **Entrada de Texto / Busca** | `<input>`, `<textarea>` | `<ion-input>`, `<ion-textarea>`, `<ion-searchbar>` | [/components/input](https://ionicframework.com/docs/api/input) |
| **Seleções e Toggles** | `<input type="checkbox">` | `<ion-checkbox>`, `<ion-toggle>`, `<ion-select>` | [/components/checkbox](https://ionicframework.com/docs/api/checkbox) |
| **Abas / Segmentos** | `<div class="tabs">` | `<ion-segment>`, `<ion-segment-button>` | [/components/segment](https://ionicframework.com/docs/api/segment) |
| **Listas e Linhas** | `<ul><li>`, `<div class="item">` | `<ion-list>`, `<ion-item>`, `<ion-item-sliding>` | [/components/list](https://ionicframework.com/docs/api/list) |
| **Cards e Painéis** | `<div class="card">` | `<ion-card>`, `<ion-card-header>`, `<ion-card-content>` | [/components/card](https://ionicframework.com/docs/api/card) |
| **Spinners / Loading** | `<div class="spinner">` | `<ion-spinner>`, `<ion-skeleton-text>` | [/components/spinner](https://ionicframework.com/docs/api/spinner) |
| **Telas / Conteúdo** | `<div class="page">` | `<ion-page>`, `<ion-header>`, `<ion-content>` | [/components/content](https://ionicframework.com/docs/api/content) |

---

## 3. Padrão Canônico de Componente (`<script setup lang="ts">`)

Todo componente de UI em `src/components/ui/` deve seguir este modelo limpo:

```vue
<script setup lang="ts">
import { IonChip, IonLabel, IonIcon } from '@ionic/vue';
import { useHaptics } from '@/composables/useHaptics';

// 1. Props estritas com defaults
interface Props {
  label: string;
  isActive?: boolean;
  disabled?: boolean;
  icon?: string;
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false,
  disabled: false,
  icon: undefined,
});

// 2. Emits explícitos
const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void;
}>();

const { triggerHaptic } = useHaptics();

async function handleClick(event: MouseEvent): Promise<void> {
  if (props.disabled) return;
  await triggerHaptic('light');
  emit('click', event);
}
</script>

<template>
  <ion-chip
    :color="isActive ? 'primary' : 'medium'"
    :disabled="disabled"
    :class="{ 'chip-active': isActive }"
    @click="handleClick"
  >
    <ion-icon v-if="icon" slot="start" :icon="icon" />
    <ion-label>{{ label }}</ion-label>
  </ion-chip>
</template>

<style scoped>
/* 3. Customização SEMPRE via CSS Variables oficiais do Ionic */
ion-chip {
  --background: var(--color-background-secondary);
  --color: var(--color-text-secondary);
  margin: 0;
}

ion-chip.chip-active {
  --background: var(--color-primary);
  --color: var(--color-neutral-900);
}
</style>
```

---

## 4. O Checklist do Componente Limpo

Antes de dar como concluído qualquer componente ou tela:

1. **Imports do Ionic**: Todos os componentes visuais são importados de `@ionic/vue` (ex: `IonChip`, `IonButton`)?
2. **Sem CSS Reset Manual**: Você evitou criar do zero animações, estados hover/active ou resets que o componente do Ionic já faz por padrão?
3. **CSS Variables**: A estilização usa `--background`, `--color` e variáveis de tema (`var(--color-primary)`) em vez de regras agressivas que quebram o Shadow DOM?
4. **Ciclo de Vida Correto**: Se o componente recarrega dados em páginas com rota, usou `onIonViewWillEnter` em vez de apenas `onMounted`?
5. **Views Magras**: Se o arquivo está em `src/views/`, ele contém apenas orquestração de stores e composição, sem centenas de linhas de CSS ou marcação profunda?
