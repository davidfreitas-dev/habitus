<script setup>
import { reactive, watch } from 'vue';
import { IonPage, IonContent, onIonViewWillEnter } from '@ionic/vue';
import { useVuelidate } from '@vuelidate/core';
import { required, email, helpers } from '@vuelidate/validators';
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

const formData = reactive({
  name: '',
  email: '',
});

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

const rules = {
  name: {
    required: helpers.withMessage('Informe seu nome', required)
  },
  email: {
    required: helpers.withMessage('Informe seu e-mail', required),
    email: helpers.withMessage('Informe um e-mail válido', email)
  }
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

// Watch for changes in profileStore.user to update formData
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

        <form>
          <div>
            <Input
              v-model="formData.name"
              type="text"
              label="Nome"
              placeholder="Digite seu nome"
              :error-text="v$.name.$errors[0]?.$message"
              @blur="v$.name.$touch()"
            />

            <Input
              v-model="formData.email"
              type="email"
              label="E-mail"
              placeholder="Digite seu e-mail"
              :error-text="v$.email.$errors[0]?.$message"
              @blur="v$.email.$touch()"
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