<script setup>
import { ref, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, onIonViewWillEnter } from '@ionic/vue';
import { useVOnboarding, VOnboardingWrapper } from 'v-onboarding';
import { useProfileStore } from '@/stores/profile';
import { useHabitStore } from '@/stores/habits';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';
import { useOnboarding } from '@/composables/useOnboarding';
import { formSteps } from '@/onboarding/formSteps';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';
import BackButton from '@/components/layout/BackButton.vue';
import HabitForm from '@/components/habits/HabitForm.vue';
import ModalDialog from '@/components/layout/ModalDialog.vue';
import OnboardingStep from '@/components/onboarding/OnboardingStep.vue';

// Ajuste os caminhos de `onboarding/formSteps` e
// `components/onboarding/OnboardingStep.vue` conforme o local real.

const profileStore = useProfileStore();
const habitStore = useHabitStore();

const route = useRoute();
const router = useRouter();

const pageTitle = computed(() => {
  return route.params.id ? 'Editar hábito' : 'Criar hábito';
});

const habit = ref({
  id: route.params.id,
  title: '',
  week_days: '',
  reminder_time: null,
});

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

onIonViewWillEnter(async () => {
  await withLoading(async () => {
    await profileStore.fetchProfile();
    if (!habit.value.id) return;
    const fetchedHabit = await habitStore.getHabitDetails(habit.value.id);
    habit.value = fetchedHabit;
  }, 'Erro ao carregar detalhes do hábito.');

  await maybeStartFormOnboarding();
});

// --- Onboarding: tour do formulário de criação de hábito ---
// Só mostra o tour ao CRIAR um hábito (não ao editar), e apenas na primeira vez.
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref(null);
const { start: startFormOnboarding } = useVOnboarding(onboardingWrapper);

const onFormOnboardingFinish = () => {
  markStepSeen('form');
};

const maybeStartFormOnboarding = async () => {
  if (route.params.id) return; // está editando, não criando
  if (await isStepSeen('form')) return;
  await nextTick();
  startFormOnboarding();
};

const habitFormRef = ref(null);
const dialogRef = ref(null);

const handleFormError = (message) => {
  showToast('info', message);
};

const createHabit = async (formData) => {
  await withLoading(async () => {
    await habitStore.createHabit(formData.title, formData.weekDays, formData.reminder_time);
    habitFormRef.value?.clearFormData();
    showToast('success', 'Hábito criado com sucesso!');
  }, 'Erro ao criar hábito.');
};

const updateHabit = async (formData) => {
  await withLoading(async () => {
    await habitStore.updateHabit(habit.value.id, formData.title, formData.weekDays, formData.reminder_time);
    showToast('success', 'Hábito atualizado com sucesso!');
    router.go(-1);
  }, 'Erro ao atualizar hábito.');
};

const handleHabit = (formData) => {
  if (habit.value.id) {
    updateHabit(formData);
  } else {
    createHabit(formData);
  }
};

const handleDelete = () => {
  dialogRef.value?.setOpen(true);
};

const deleteHabit = async () => {
  await withLoading(async () => {
    await habitStore.deleteHabit(habit.value.id);
    router.go(-1);
  }, 'Erro ao excluir hábito.');
};
</script>

<template>
  <ion-page>
    <Header>
      <BackButton />
    </Header>

    <ion-content>      
      <Container>
        <Heading :title="pageTitle" />

        <HabitForm
          v-if="!route.params.id || habit.title"
          ref="habitFormRef"
          :habit="habit"
          :is-loading="isLoading"
          @on-submit="handleHabit"
          @on-error="handleFormError"
        />

        <Button
          v-if="route.params.id && habit.title"
          color="danger"
          class="ion-margin-top"
          @click="handleDelete"
        >
          Excluir
        </Button>
      </Container>

      <ModalDialog
        ref="dialogRef"
        message="Deseja realmente excluir este hábito?"
        @on-confirm="deleteHabit"
      />

      <!-- Removed Alert component as showAlert is removed -->
    </ion-content>

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="formSteps"
      @finish="onFormOnboardingFinish"
      @exit="onFormOnboardingFinish"
    >
      <template #default="{ step, index, isLast, steps, exit, nextStep }">
        <OnboardingStep
          :step="step"
          :index="index"
          :is-last="isLast"
          :total="steps.length"
          @next="isLast ? exit() : nextStep()"
          @skip="exit()"
        />
      </template>
    </VOnboardingWrapper>
  </ion-page>
</template>