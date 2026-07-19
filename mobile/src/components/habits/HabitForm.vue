<script setup>
import { ref, watch } from 'vue';
import { IonDatetime, IonDatetimeButton, IonModal, IonList, IonItem, IonLabel } from '@ionic/vue';
import Input from '@/components/ui/Input.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';

const emit = defineEmits(['onError', 'onSubmit']);

const props = defineProps({
  habit: {
    type: Object,
    default: () => ({
      id: null,
      title: '',
      week_days: '',
    }),
  },
});

const getLocalISOString = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60000;
  return new Date(now - offset).toISOString();
};

const formData = ref({
  title: '',
  weekDays: [],
  reminderEnabled: false,
  reminderTime: getLocalISOString(),
});

watch(
  () => props.habit,
  (newHabit) => {
    if (newHabit) {
      formData.value.title = newHabit.title || '';
      formData.value.weekDays = [];
      if (newHabit.week_days) {
        if (Array.isArray(newHabit.week_days)) {
          formData.value.weekDays = newHabit.week_days.map(Number);
        } else if (typeof newHabit.week_days === 'string' && newHabit.week_days.length > 0) {
          formData.value.weekDays = newHabit.week_days.split(',').map(Number);
        }
      }

      if (newHabit.reminder_time) {
        formData.value.reminderEnabled = true;
        formData.value.reminderTime = newHabit.reminder_time;
      } else {
        formData.value.reminderEnabled = false;
        formData.value.reminderTime = getLocalISOString();
      }
    }
  },
  { immediate: true }
);

const clearFormData = () => {
  formData.value.title = '';
  formData.value.weekDays = [];
  formData.value.reminderEnabled = false;
  formData.value.reminderTime = getLocalISOString();
};

defineExpose({ clearFormData });

const isLoading = ref(false);

const isDayChecked = (index) => {
  return formData.value.weekDays.includes(index);
};

const toggleWeekDay = (index) => {
  const days = formData.value.weekDays;

  if (days.includes(index)) {
    formData.value.weekDays = days.filter((day) => day !== index);
    return;
  }

  days.push(index);
};

const submitForm = () => {
  if (!formData.value.title || !formData.value.weekDays.length) {
    emit('onError', 'Informe um título para o hábito e selecione os dias.');
    return;
  }

  let reminderTimeValue = formData.value.reminderTime;

  // Extract only HH:mm if it's an ISO string
  if (reminderTimeValue && reminderTimeValue.includes('T')) {
    reminderTimeValue = reminderTimeValue.split('T')[1].substring(0, 5);
  } else if (reminderTimeValue && reminderTimeValue.length > 5) {
    reminderTimeValue = reminderTimeValue.substring(0, 5);
  }

  emit('onSubmit', {
    ...formData.value,
    reminder_time: formData.value.reminderEnabled ? reminderTimeValue : null
  });
};

const availableWeekDays = [
  'Domingo',
  'Segunda-feira',
  'Terça-feira',
  'Quarta-feira',
  'Quinta-feira',
  'Sexta-feira',
  'Sábado',
];
</script>

<template>
  <form>    
    <p>Qual seu comprometimento?</p>
    <Input
      v-model="formData.title"
      placeholder="Exercícios, dormir bem, etc..."
    /> 

    <div id="onboarding-recurrence">
      <p>Qual a recorrência?</p>
      <Checkbox
        v-for="(weekDay, index) in availableWeekDays"
        :key="index"
        :label="weekDay"
        :is-checked="isDayChecked(index)"
        @handle-checkbox-change="toggleWeekDay(index)"
      />
    </div>

    <div id="onboarding-reminder">
      <ion-item lines="none" class="ion-no-padding">
        <ion-label class="ion-no-margin">
          <b>Ativar lembrete</b>
        </ion-label>
        <Toggle v-model:checked="formData.reminderEnabled" />
      </ion-item>
      <ion-item
        v-if="formData.reminderEnabled"
        lines="none"
        class="ion-no-padding"
      >
        <ion-label class="ion-no-margin">
          Horário
        </ion-label>
        <ion-datetime-button datetime="reminder-datetime" />
        <ion-modal :keep-contents-mounted="true">
          <ion-datetime
            id="reminder-datetime"
            v-model="formData.reminderTime"
            presentation="time"
            locale="pt-BR"
          />
        </ion-modal>
      </ion-item>
    </div>

    <Button
      color="primary"
      class="ion-margin-top"
      :is-loading="isLoading"
      @click="submitForm"
    >
      Confirmar
    </Button>
  </form>
</template>

<style scoped>
p {
  color: var(--color-text-primary);
  font-weight: 700;
  margin-top: 1.5rem;
}

ion-item {
  color: var(--color-text-accent);
  font-size: 1.1rem;
  --inner-padding-end: 0;
}

ion-datetime-button::part(native) {
  background: var(--color-background-secondary);
  color: var(--color-text-accent);
  border-radius: 8px;
  font-size: 1rem;
  padding: 6px 12px;
}

ion-modal {
  --background: var(--color-background-elevated);
  --backdrop-opacity: 0.7;
  --border-radius: 16px;
}

ion-modal ion-datetime {
  --background: var(--color-background-elevated);
  --background-rgb: var(--color-background-rgb);
  --wheel-highlight-background: var(--color-background-elevated);
}

ion-modal ion-datetime::part(wheel-item) {
  color: var(--color-text-primary);
}

ion-modal ion-datetime::part(wheel-item active) {
  color: var(--color-success);
}
</style>