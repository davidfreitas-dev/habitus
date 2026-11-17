export function useCelebrationSound() {
  let audioCtx: AudioContext | null = null;

  const getAudioContext = (): AudioContext | null => {
    try {
      const AudioContextClass =
        window.AudioContext ||
        (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;

      if (!AudioContextClass) return null;

      if (!audioCtx) {
        audioCtx = new AudioContextClass();
      }

      if (audioCtx.state === 'suspended') {
        void audioCtx.resume();
      }

      return audioCtx;
    } catch {
      return null;
    }
  };

  const playCelebrationSound = () => {
    const ctx = getAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;

    // Arpejo triumfal e harmonioso (C5, E5, G5, C6) com fade suave
    const notes = [
      { freq: 523.25, start: 0, duration: 0.25 },     // Dó (C5)
      { freq: 659.25, start: 0.08, duration: 0.28 },  // Mi (E5)
      { freq: 783.99, start: 0.16, duration: 0.32 },  // Sol (G5)
      { freq: 1046.50, start: 0.24, duration: 0.65 }, // Dó agudo (C6)
    ];

    notes.forEach(({ freq, start, duration }) => {
      try {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, now + start);

        gain.gain.setValueAtTime(0, now + start);
        gain.gain.linearRampToValueAtTime(0.2, now + start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + start + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start(now + start);
        osc.stop(now + start + duration);
      } catch {
        // Fallback defensivo silencioso
      }
    });
  };

  return { playCelebrationSound };
}
