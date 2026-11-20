<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { IonContent, IonPage } from '@ionic/vue';
import { useProfileStore } from '@/stores/profileStore';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
import { useValidatedForm } from '@/composables/useValidatedForm';
import {
  requiredPasswordRules,
  passwordRules,
  confirmPasswordRules,
  getFieldError,
} from '@/utils/validators';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import AppBackButton from '@/components/app/AppBackButton.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';

const profileStore = useProfileStore();
const router = useRouter();

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

const newPasswordRef = ref('');
const rules = computed(() => ({
  currentPassword: requiredPasswordRules('Informe sua senha atual'),
  newPassword: passwordRules('Informe a nova senha'),
  confNewPassword: confirmPasswordRules(
    newPasswordRef,
    'Confirme a nova senha',
    'A confirmação não coincide com a nova senha'
  ),
}));

const { formData, v$, handleSubmit } = useValidatedForm(
  () => ({
    currentPassword: '',
    newPassword: '',
    confNewPassword: '',
  }),
  rules
);

watch(() => formData.newPassword, (val) => { newPasswordRef.value = val; }, { immediate: true });

const updatePassword = async () => {
  await withLoading(async () => {
    const response = await profileStore.changePassword(
      formData.currentPassword,
      formData.newPassword,
      formData.confNewPassword
    );
    showToast('success', response.message || 'Senha alterada com sucesso!');
    router.push('/tabs/options');
  }, 'Erro ao alterar a senha.');
};

const submitForm = () => handleSubmit(updatePassword);
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppBackButton />
    </AppHeader>

    <ion-content>
      <AppContainer>
        <AppHeading title="Alteração de Senha" />

        <form @submit.prevent="submitForm">
          <div>
            <BaseInput
              v-model="formData.currentPassword"
              type="password"
              label="Digite a senha atual"
              placeholder="Senha atual"
              :error-text="getFieldError(v$.currentPassword)"
              @blur="v$.currentPassword.$touch()"
            />

            <BaseInput
              v-model="formData.newPassword"
              type="password"
              label="Digite a nova senha"
              placeholder="Nova senha"
              :error-text="getFieldError(v$.newPassword)"
              @blur="v$.newPassword.$touch()"
            />

            <BaseInput
              v-model="formData.confNewPassword"
              type="password"
              label="Confirme a nova senha"
              placeholder="Repita a nova senha"
              :error-text="getFieldError(v$.confNewPassword)"
              @blur="v$.confNewPassword.$touch()"
            />
          </div>

          <BaseButton
            type="submit"
            color="primary"
            :is-disabled="v$.$invalid"
            :is-loading="isLoading"
          >
            Confirmar
          </BaseButton>
        </form>
      </AppContainer>
    </ion-content>
  </ion-page>
</template>

<style scoped>
form div {
  display: flex;
  flex-direction: column;
  margin-bottom: 2rem;
}
</style>
