<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, onIonViewWillEnter, onIonViewDidEnter } from '@ionic/vue';
import { useHabitStore } from '@/stores/habitsStore';
import { useLoading } from '@/composables/useLoading';
import { useToast } from '@/composables/useToast';
import { useHaptics } from '@/composables/useHaptics';
import { formSteps } from '@/onboarding/formSteps';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import AppBackButton from '@/components/app/AppBackButton.vue';
import HabitForm from '@/components/features/habits/HabitForm.vue';
import AppConfirmModal from '@/components/app/AppConfirmModal.vue';
import OnboardingTour from '@/components/features/onboarding/OnboardingTour.vue';
import type { HabitDraft, HabitFormSubmitPayload } from '@/types';

const habitStore = useHabitStore();
const { triggerHaptic } = useHaptics();

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
  if (!habit.value.id) return;
  detailsLoaded = withLoading(async () => {
    const fetchedHabit = await habitStore.getHabitDetails(habit.value.id!);
    habit.value = fetchedHabit;
  }, 'Erro ao carregar detalhes do hábito.');
});

const onboardingTourRef = ref<InstanceType<typeof OnboardingTour> | null>(null);

onIonViewDidEnter(async () => {
  if (detailsLoaded) {
    try {
      await detailsLoaded;
    } catch {
      // Ignora o erro
    }
  }
  await onboardingTourRef.value?.startIfNotSeen();
});

const habitFormRef = ref<InstanceType<typeof HabitForm> | null>(null);
const dialogRef = ref<InstanceType<typeof AppConfirmModal> | null>(null);

const handleFormError = (message: string) => {
  showToast('info', message);
};

const createHabit = async (formData: HabitFormSubmitPayload) => {
  await withLoading(async () => {
    await habitStore.createHabit(formData.title, formData.weekDays, formData.reminder_time);
    await triggerHaptic('success');
    habitFormRef.value?.clearFormData();
    showToast('success', 'Hábito criado com sucesso!');
  }, 'Erro ao criar hábito.');
};

const updateHabit = async (formData: HabitFormSubmitPayload) => {
  if (!habit.value.id) return;
  await withLoading(async () => {
    await habitStore.updateHabit(habit.value.id!, formData.title, formData.weekDays, formData.reminder_time);
    await triggerHaptic('success');
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
    await triggerHaptic('medium');
    router.go(-1);
  }, 'Erro ao excluir hábito.');
};
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppBackButton />
    </AppHeader>

    <ion-content>
      <AppContainer>
        <AppHeading :title="pageTitle" />

        <HabitForm
          v-if="!route.params.id || habit.title"
          ref="habitFormRef"
          :habit="habit"
          :is-loading="isLoading"
          @on-submit="handleHabit"
          @on-error="handleFormError"
        />

        <BaseButton
          v-if="route.params.id && habit.title"
          color="danger"
          class="ion-margin-top"
          @click="handleDelete"
        >
          Excluir
        </BaseButton>
      </AppContainer>

      <AppConfirmModal
        ref="dialogRef"
        message="Deseja realmente excluir este hábito?"
        @on-confirm="deleteHabit"
      />
    </ion-content>

    <OnboardingTour
      ref="onboardingTourRef"
      step-key="form"
      :steps="formSteps"
      :enabled="!route.params.id"
    />
  </ion-page>
</template>
