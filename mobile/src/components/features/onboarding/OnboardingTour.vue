<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { useVOnboarding, VOnboardingStep, VOnboardingWrapper, type StepEntity } from 'v-onboarding';
import { useOnboarding, type OnboardingStepKey } from '@/composables/useOnboarding';
import OnboardingStep from '@/components/features/onboarding/OnboardingStep.vue';

const props = withDefaults(
  defineProps<{
    stepKey: OnboardingStepKey;
    steps: StepEntity[];
    enabled?: boolean;
    options?: Record<string, unknown>;
  }>(),
  {
    enabled: true,
    options: () => ({}),
  }
);

const { isStepSeen, markStepSeen } = useOnboarding();
const onboardingWrapper = ref<InstanceType<typeof VOnboardingWrapper> | null>(null);
const { start, finish } = useVOnboarding(onboardingWrapper);

const onComplete = () => {
  markStepSeen(props.stepKey);
};

const onExit = () => {
  finish();
};

const startIfNotSeen = async (): Promise<void> => {
  if (!props.enabled || !props.steps.length) return;
  if (await isStepSeen(props.stepKey)) return;
  await nextTick();
  try {
    start();
  } catch (err) {
    console.warn(`[OnboardingTour] Failed to start step '${props.stepKey}':`, err);
  }
};

defineExpose({
  startIfNotSeen,
  finish,
});
</script>

<template>
  <VOnboardingWrapper
    ref="onboardingWrapper"
    :steps="steps"
    :options="options"
    @finish="onComplete"
    @exit="onExit"
  >
    <template #default="{ step, isLast, next, exit }">
      <VOnboardingStep>
        <OnboardingStep
          :step="step"
          :index="steps.indexOf(step)"
          :is-last="isLast"
          :total="steps.length"
          @next="isLast ? exit() : next()"
          @skip="exit()"
        />
      </VOnboardingStep>
    </template>
  </VOnboardingWrapper>
</template>
