<script setup lang="ts">
import { ref } from 'vue';
import { IonModal, IonContent } from '@ionic/vue';
import Button from '@/components/ui/Button.vue';

withDefaults(
  defineProps<{
    message?: string;
  }>(),
  {
    message: '',
  }
);

interface IonModalElement {
  $el?: {
    present: () => Promise<void>;
    dismiss: () => Promise<boolean>;
  };
}

const modal = ref<IonModalElement | null>(null);

const setOpen = (status: boolean) => {
  if (status) {
    modal.value?.$el?.present();
  } else {
    modal.value?.$el?.dismiss();
  }
};

defineExpose({ setOpen });

const closeModal = () => {
  modal.value?.$el?.dismiss();
};

const emit = defineEmits<{
  (e: 'on-confirm'): void;
}>();

const confirmAction = () => {
  emit('on-confirm');
  closeModal();
};
</script>

<template>
  <ion-modal
    ref="modal"
    :initial-breakpoint="0.35"
    :breakpoints="[0, 0.35]"
  >
    <ion-content class="ion-padding-top">
      <div class="ion-text-center ion-padding-horizontal">
        <p><b>{{ message }}</b></p>
      </div>
      
      <div class="ion-padding">
        <Button
          color="primary"
          class="ion-margin-bottom"
          @click="confirmAction"
        >
          Confirmar
        </Button>
        <Button @click="closeModal">
          Cancelar
        </Button>
      </div>
    </ion-content>
  </ion-modal>
</template>

<style scoped>
ion-content {
  --background: var(--color-background-secondary)
}
</style>
