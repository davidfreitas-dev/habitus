<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, onIonViewWillEnter, onIonViewDidEnter } from '@ionic/vue';
import { useVOnboarding, VOnboardingStep, VOnboardingWrapper } from 'v-onboarding';
import { useProfileStore } from '@/stores/profileStore';
import { useHabitStore } from '@/stores/habitsStore';
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
import type { HabitDraft, HabitFormSubmitPayload } from '@/types';

const profileStore = useProfileStore();
const habitStore = useHabitStore();

const route = useRoute();
const router = useRouter();

const habitId = route.params.id as string | undefined;

const pageTitle = computed(() => {
  return habitId ? 'Editar hábito' : 'Criar hábito';
});

const habit = ref<HabitDraft>({
  id: habitId,
  title: '',
  week_days: '',
  reminder_time: null,
});

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

let detailsLoaded: Promise<void> | null = null;

onIonViewWillEnter(() => {
  detailsLoaded = withLoading(async () => {
    await profileStore.fetchProfile();
    if (!habit.value.id) return;
    const fetchedHabit = await habitStore.getHabitDetails(habit.value.id);
    habit.value = fetchedHabit;
  }, 'Erro ao carregar detalhes do hábito.');
});

onIonViewDidEnter(async () => {
  if (detailsLoaded) {
    try {
      await detailsLoaded;
    } catch (err) {
      // Ignora o erro
    }
  }
  await maybeStartFormOnboarding();
});

// --- Onboarding: tour do formulário de criação de hábito ---
const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref<any>(null);
const { start: startFormOnboarding, finish: finishFormOnboarding } = useVOnboarding(onboardingWrapper);

const onFormOnboardingComplete = () => {
  markStepSeen('form');
};

const onFormOnboardingExit = () => {
  finishFormOnboarding();
};

const maybeStartFormOnboarding = async () => {
  if (route.params.id) return;
  if (await isStepSeen('form')) return;
  await nextTick();
  startFormOnboarding();
};

const habitFormRef = ref<any>(null);
const dialogRef = ref<any>(null);

const handleFormError = (message: string) => {
  showToast('info', message);
};

const createHabit = async (formData: HabitFormSubmitPayload) => {
  await withLoading(async () => {
    await habitStore.createHabit(formData.title, formData.weekDays, formData.reminder_time);
    habitFormRef.value?.clearFormData();
    showToast('success', 'Hábito criado com sucesso!');
  }, 'Erro ao criar hábito.');
};

const updateHabit = async (formData: HabitFormSubmitPayload) => {
  if (!habit.value.id) return;
  await withLoading(async () => {
    await habitStore.updateHabit(habit.value.id!, formData.title, formData.weekDays, formData.reminder_time);
    showToast('success', 'Hábito atualizado com sucesso!');
    router.go(-1);
  }, 'Erro ao atualizar hábito.');
};

const handleHabit = (formData: HabitFormSubmitPayload) => {
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
  if (!habit.value.id) return;
  await withLoading(async () => {
    await habitStore.deleteHabit(habit.value.id!);
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
    </ion-content>

    <VOnboardingWrapper
      ref="onboardingWrapper"
      :steps="formSteps"
      @finish="onFormOnboardingComplete"
      @exit="onFormOnboardingExit"
    >
      <template #default="{ step, isLast, next, exit }">
        <VOnboardingStep>
          <OnboardingStep
            :step="step"
            :index="formSteps.indexOf(step)"
            :is-last="isLast"
            :total="formSteps.length"
            @next="isLast ? exit() : next()"
            @skip="exit()"
          />
        </VOnboardingStep>
      </template>
    </VOnboardingWrapper>
  </ion-page>
</template>
