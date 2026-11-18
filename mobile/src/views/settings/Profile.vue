<script setup lang="ts">
import { reactive, watch } from 'vue';
import { IonPage, IonContent, onIonViewWillEnter } from '@ionic/vue';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useProfileStore } from '@/stores/profileStore';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
import { emailRules, getFieldError } from '@/utils/validators';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

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
    <Header>
      <BackButton />
    </Header>

    <ion-content>
      <Container>
        <Heading title="Edição de Perfil" />

        <form @submit.prevent="submitForm">
          <div>
            <Input
              v-model="formData.name"
              type="text"
              label="Nome"
              placeholder="Digite seu nome"
              :error-text="getFieldError(v$.name)"
              @blur="v$.name.$touch()"
            />

            <Input
              v-model="formData.email"
              type="email"
              label="E-mail"
              placeholder="Digite seu e-mail"
              :error-text="getFieldError(v$.email)"
              @blur="v$.email.$touch()"
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
