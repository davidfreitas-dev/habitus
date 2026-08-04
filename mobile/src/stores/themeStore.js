import { ref } from 'vue';
import { defineStore } from 'pinia';

export const useThemeStore = defineStore(
  'theme',
  () => {
    const isDarkMode = ref(false);
    const isInitialLoad = ref(true);

    function setDarkMode(value) {
      isDarkMode.value = value;
      
      document.body.classList.toggle('dark', value);

      if (isInitialLoad.value) {
        isInitialLoad.value = false;
      }
    }

    return {
      isDarkMode,
      isInitialLoad,
      setDarkMode,
    };
  },
  {
    persist: true,
  }
);