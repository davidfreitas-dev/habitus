<script setup lang="ts">
import { reactive, watch } from 'vue';
import { IonPage, IonContent, onIonViewWillEnter } from '@ionic/vue';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useProfileStore } from '@/stores/profileStore';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
import { emailRules, getFieldError } from '@/utils/validators';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import AppBackButton from '@/components/app/AppBackButton.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';

const profileStore = useProfileStore();

const formData = reactive({
  name: '',
  email: '',
});

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

const rules = {
  name: {
    required: helpers.withMessage('Informe seu nome', required),
  },
  email: emailRules(),
};

const v$ = useVuelidate(rules, formData);

const loadProfileData = () => {
  if (profileStore.user) {
    formData.name = profileStore.user.name || '';
    formData.email = profileStore.user.email || '';
    v$.value.$reset();
  }
};

onIonViewWillEnter(async () => {
  await withLoading(async () => {
    await profileStore.fetchProfile();
    loadProfileData();
  }, 'Erro ao carregar dados do perfil.');
});

watch(() => profileStore.user, (newUser) => {
  if (newUser) {
    loadProfileData();
  }
}, { deep: true });

const updateProfile = async () => {
  await withLoading(async () => {
    await profileStore.updateProfile({
      name: formData.name,
      email: formData.email,
    });
    showToast('success', 'Perfil atualizado com sucesso!');
  }, 'Erro ao atualizar dados do perfil.');
};

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha todos os campos corretamente');
    return;
  }

  updateProfile();
};
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppBackButton />
    </AppHeader>

    <ion-content>
      <AppContainer>
        <AppHeading title="Edição de Perfil" />

        <form @submit.prevent="submitForm">
          <div>
            <BaseInput
              v-model="formData.name"
              type="text"
              label="Nome"
              placeholder="Digite seu nome"
              :error-text="getFieldError(v$.name)"
              @blur="v$.name.$touch()"
            />

            <BaseInput
              v-model="formData.email"
              type="email"
              label="E-mail"
              placeholder="Digite seu e-mail"
              :error-text="getFieldError(v$.email)"
              @blur="v$.email.$touch()"
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
