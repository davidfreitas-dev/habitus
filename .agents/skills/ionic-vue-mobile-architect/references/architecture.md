# Arquitetura Mobile & Padrões TypeScript (Vue 3 + Ionic 8)

O aplicativo **Habitus** adota uma arquitetura em camadas com separação estrita de responsabilidades, tipagem forte com **TypeScript 5+**, **Vue 3 (Composition API)** e **Pinia**.

---

## 1. Fluxo de Dados em Camadas

```text
Componente / View (.vue)
        ↓ (dispara ação)
Pinia Store (src/stores/)
        ↓ (chama método tipado)
Service (src/services/)
        ↓ (chama cliente HTTP)
Axios API Client (src/api/index.ts)
        ↓ (HTTP com interceptors + HttpOnly cookies)
Backend Slim 4 REST API
```

### Regras das Camadas:
1. **Views (`src/views/`)**:
   - Estrutura de telas baseada em componentes do Ionic (`IonPage`, `IonHeader`, `IonToolbar`, `IonTitle`, `IonContent`).
   - Não conhecem detalhes de requisições HTTP; interagem apenas com **Stores** ou **Composables**.
2. **Components (`src/components/`)**:
   - `ui/`: Componentes base (Botões, Inputs, Checkboxes).
   - `layout/`: Componentes estruturais (Header, Modais, Containers).
   - `habits/`: Componentes específicos de domínio (ex: grid de hábitos, cards de progresso).
3. **Stores (`src/stores/`)**:
   - Centralizam estado reativo global (`defineStore` com setup syntax ou options).
   - Mantêm o estado da aplicação sincronizado com o backend.
4. **Services (`src/services/`)**:
   - Classes ou módulos puros sem estado reativo, responsáveis exclusivamente por chamar `api` (instância do Axios) com URLs e payloads tipados.
5. **API Client (`src/api/index.ts`)**:
   - Configura Axios com `baseURL`, timeout e interceptores.
   - Injeta o `Authorization: Bearer <token>` e intercepta respostas `401` para tentar refresh token transparente via cookie `HttpOnly`.

---

## 2. Padrões de Código Vue 3 com TypeScript

### Estrutura Padrão de Componentes (`<script setup lang="ts">`)

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { IonButton, IonIcon } from '@ionic/vue';
import { checkmarkOutline } from 'ionicons/icons';
import type { Habit } from '@/types/habit';

// 1. Props com tipagem estrita
interface Props {
  habit: Habit;
  isCompleted?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isCompleted: false,
  disabled: false,
});

// 2. Emits tipados
interface Emits {
  (e: 'toggle', habitId: number): void;
  (e: 'edit', habit: Habit): void;
}

const emit = defineEmits<Emits>();

// 3. Computed e Métodos
const buttonColor = computed(() => (props.isCompleted ? 'success' : 'medium'));

function handleToggle(): void {
  if (props.disabled) return;
  emit('toggle', props.habit.id);
}
</script>

<template>
  <div class="habit-item">
    <span class="habit-title">{{ habit.title }}</span>
    <IonButton :color="buttonColor" :disabled="disabled" @click="handleToggle">
      <IonIcon slot="icon-only" :icon="checkmarkOutline" />
    </IonButton>
  </div>
</template>

<style scoped>
.habit-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
}
</style>
```

---

## 3. Ciclo de Vida do Ionic no Vue Router

Ao usar `ion-router-outlet`, as páginas do Ionic **permanecem em cache no DOM** para navegação fluida para frente e para trás. 

> [!WARNING]
> O hook padrão do Vue `onMounted()` só é executado na **primeira vez** que a página é criada. Se o usuário navegar para outra página e voltar, `onMounted` **não rodará novamente**.

Para recarregar dados ou atualizar estados sempre que a página se tornar visível:
- Use `onIonViewWillEnter`: executado imediatamente antes da tela entrar na animação de transição.
- Use `onIonViewDidEnter`: executado assim que a animação termina.
- Use `onIonViewWillLeave`: para pausar timers, limpar listeners temporários.
- Use `onIonViewDidLeave`: executado quando a página não está mais visível.

```ts
import { onIonViewWillEnter } from '@ionic/vue';
import { useHabitsStore } from '@/stores/habitsStore';

const habitsStore = useHabitsStore();

onIonViewWillEnter(async () => {
  await habitsStore.fetchTodayHabits();
});
```

---

## 4. Composables Customizados (`src/composables/`)

- Nomeie sempre começando com `use` (ex: `useLoading.ts`, `useToast.ts`, `useNetwork.ts`).
- Retorne apenas refs reativas (`Ref<T>`), computadas (`ComputedRef<T>`) e funções com tipagem explícita.
- Encapsule chamadas assíncronas repetitivas e manipulação de estado local.
