import { reactive, type ComputedRef } from 'vue';
import { useVuelidate, type ValidationArgs } from '@vuelidate/core';
import { onIonViewDidLeave } from '@ionic/vue';
import { useToast } from '@/composables/useToast';

interface UseValidatedFormOptions {
  invalidMessage?: string;
  resetOnLeave?: boolean;
}

/**
 * Encapsulates Vuelidate setup, submit validation with toast feedback,
 * and automatic form reset on Ionic page leave.
 */
export function useValidatedForm<
  T extends Record<string, unknown>,
  R extends ValidationArgs<Partial<T>> = ValidationArgs<Partial<T>>,
>(
  initialState: () => T,
  rules: R | ComputedRef<R>,
  options: UseValidatedFormOptions = {}
) {
  const {
    invalidMessage = 'Preencha todos os campos corretamente',
    resetOnLeave = true,
  } = options;

  const { showToast } = useToast();
  const formData = reactive<T>(initialState()) as T;
  const v$ = useVuelidate(rules as ValidationArgs<T>, formData);

  const resetForm = () => {
    Object.assign(formData, initialState());
    v$.value.$reset();
  };

  const handleSubmit = async (onValid: () => void | Promise<void>) => {
    const isFormCorrect = await v$.value.$validate();
    if (!isFormCorrect) {
      showToast('info', invalidMessage);
      return;
    }
    await onValid();
  };

  if (resetOnLeave) {
    onIonViewDidLeave(() => {
      resetForm();
    });
  }

  return {
    formData,
    v$,
    resetForm,
    handleSubmit,
  };
}
