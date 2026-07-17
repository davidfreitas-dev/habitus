<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, minLength, sameAs, helpers } from '@vuelidate/validators';
import { IonContent, IonPage, onIonViewDidLeave } from '@ionic/vue';
import { useProfileStore } from '@/stores/profile';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
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

const rules = {
  currentPassword: {
    required: helpers.withMessage('Informe sua senha atual', required)
  },
  newPassword: {
    required: helpers.withMessage('Informe a nova senha', required),
    minLength: helpers.withMessage('A senha deve ter no mínimo 6 caracteres', minLength(6))
  },
  confNewPassword: {
    required: helpers.withMessage('Confirme a nova senha', required),
    sameAsPassword: helpers.withMessage('A confirmação não coincide com a nova senha', sameAs(() => formData.newPassword))
  }
};

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

        <form>
          <div>
            <Input
              v-model="formData.currentPassword"
              type="password"
              label="Digite a senha atual"
              placeholder="Senha atual"
              :error-text="v$.currentPassword.$errors[0]?.$message"
              @blur="v$.currentPassword.$touch()"
            />

            <Input
              v-model="formData.newPassword"
              type="password"
              label="Digite a nova senha"
              placeholder="Nova senha"
              :error-text="v$.newPassword.$errors[0]?.$message"
              @blur="v$.newPassword.$touch()"
            />

            <Input
              v-model="formData.confNewPassword"
              type="password"
              label="Confirme a nova senha"
              placeholder="Repita a nova senha"
              :error-text="v$.confNewPassword.$errors[0]?.$message"
              @blur="v$.confNewPassword.$touch()"
            />
          </div>

          <Button
            color="primary"
            :is-disabled="v$.$invalid"
            :is-loading="isLoading"
            @click="submitForm"
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

a {
  font-size: .85rem;
  text-align: center;
  text-decoration: none;
  letter-spacing: .25px;
  color: var(--color-secondary);
}
</style>