<script setup lang="ts">
import { useRouter } from 'vue-router';
import { ref, reactive, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import {
  fullNameRules,
  emailRules,
  passwordRules,
  capitalizeName,
  getFieldError,
} from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import ButtonGoogle from '@/components/auth/ButtonGoogle.vue';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
const formData = reactive({
  name: '',
  email: '',
  password: '',
  website: '', // honeypot
  agreeToTerms: false
});

const { showToast } = useToast();
const router = useRouter();

const signUp = async () => {
  isLoadingEmail.value = true;

  try {
    if (formData.website) {
      throw new Error('Bot detectado');
    }

    formData.name = capitalizeName(formData.name);
    await authStore.register(formData);
    router.push('/');
  } catch (err: unknown) {
    console.error('Registration failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao criar conta.'));
  } finally {
    isLoadingEmail.value = false;
  }
};

const rules = computed(() => ({
  name: fullNameRules(),
  email: emailRules(),
  password: passwordRules(),
}));

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha todos os campos corretamente');
    return;
  }

  signUp();
};

onIonViewDidLeave(() => {
  formData.name = '';
  formData.email = '';
  formData.password = '';
  formData.website = '';
  v$.value.$reset();
});
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <Input
        v-model="formData.name"
        type="text"
        label="Seu nome e sobrenome"
        placeholder="Fulano de Tal"
        :error-text="getFieldError(v$.name)"
        @blur="v$.name.$touch()"
      />

      <Input
        v-model="formData.email"
        type="text"
        label="Seu melhor e-mail"
        placeholder="exemplo@email.com"
        :error-text="getFieldError(v$.email)"
        @blur="v$.email.$touch()"
      />

      <Input
        v-model="formData.password"
        type="password"
        label="Sua senha"
        placeholder="Digite sua senha"
        :error-text="getFieldError(v$.password)"
        @blur="v$.password.$touch()"
      />

      <div class="hp-field">
        <Input
          v-model="formData.website"
          type="text"
          label="Website"
          tabindex="-1"
          autocomplete="off"
        />
      </div>

      <div class="terms-checkbox">
        <Checkbox
          size="small"
          :is-checked="formData.agreeToTerms"
          @handle-checkbox-change="formData.agreeToTerms = !formData.agreeToTerms"
          @handle-item="formData.agreeToTerms = !formData.agreeToTerms"
        >
          <span class="terms-text">
            Li e concordo com os
            <router-link to="/about">
              Termos de Uso
            </router-link> e a
            <router-link to="/about">
              Política de Privacidade
            </router-link>
          </span>
        </Checkbox>
      </div>

      <div class="ion-margin-top">
        <Button
          type="submit"
          color="primary"
          :is-loading="isLoadingEmail"
          :is-disabled="v$.$invalid || !formData.agreeToTerms"
        >
          Criar conta
        </Button>

        <div class="separator">
          <span>ou</span>
        </div>

        <ButtonGoogle :is-disabled="isLoadingEmail" label="Entrar com Google" />
      </div>
    </form>

    <template #footer>
      Já tem uma conta? <router-link to="/signin">
        Fazer login
      </router-link>
    </template>
  </AuthLayout>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
}

.hp-field {
  opacity: 0;
  position: absolute;
  top: -9999px;
  left: -9999px;
}

.terms-checkbox {
  margin-top: 1.25rem;
}

.terms-text {
  font-size: 0.85rem;
  color: var(--color-text-primary);
}

.terms-text a {
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  color: var(--color-primary-hover);
  margin: 0 !important;
  display: inline;
}
</style>
