<script setup lang="ts">
import { reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { IonContent, IonPage, onIonViewDidLeave } from '@ionic/vue';
import { useProfileStore } from '@/stores/profileStore';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
import {
  requiredPasswordRules,
  passwordRules,
  confirmPasswordRules,
  getFieldError,
} from '@/utils/validators';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const profileStore = useProfileStore();
const router = useRouter();

const formData = reactive({
  currentPassword: '',
  newPassword: '',
  confNewPassword: ''
});

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

const resetData = () => {
  formData.currentPassword = '';
  formData.newPassword = '';
  formData.confNewPassword = '';
  v$.value.$reset();
};

onIonViewDidLeave(() => {
  resetData();
});

const rules = computed(() => ({
  currentPassword: requiredPasswordRules('Informe sua senha atual'),
  newPassword: passwordRules('Informe a nova senha'),
  confNewPassword: confirmPasswordRules(
    computed(() => formData.newPassword),
    'Confirme a nova senha',
    'A confirmação não coincide com a nova senha'
  ),
}));

const v$ = useVuelidate(rules, formData);

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

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha todos os campos corretamente');
    return;
  }

  updatePassword();
};
</script>

<template>
  <ion-page>
    <Header>
      <BackButton />
    </Header>

    <ion-content>
      <Container>
        <Heading title="Alteração de Senha" />

        <form @submit.prevent="submitForm">
          <div>
            <Input
              v-model="formData.currentPassword"
              type="password"
              label="Digite a senha atual"
              placeholder="Senha atual"
              :error-text="getFieldError(v$.currentPassword)"
              @blur="v$.currentPassword.$touch()"
            />

            <Input
              v-model="formData.newPassword"
              type="password"
              label="Digite a nova senha"
              placeholder="Nova senha"
              :error-text="getFieldError(v$.newPassword)"
              @blur="v$.newPassword.$touch()"
            />

            <Input
              v-model="formData.confNewPassword"
              type="password"
              label="Confirme a nova senha"
              placeholder="Repita a nova senha"
              :error-text="getFieldError(v$.confNewPassword)"
              @blur="v$.confNewPassword.$touch()"
            />
          </div>

          <Button
            type="submit"
            color="primary"
            :is-disabled="v$.$invalid"
            :is-loading="isLoading"
          >
            Confirmar
          </Button>
        </form>
      </Container>
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
